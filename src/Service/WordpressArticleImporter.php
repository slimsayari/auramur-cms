<?php

namespace App\Service;

use App\DTO\WordpressArticleImportDTO;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\WordpressImportLog;
use App\Enum\ContentStatus;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WordpressArticleImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private SeoService $seoService,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private string $uploadDirectory
    ) {
    }

    /**
     * Importe des articles depuis un tableau de données
     */
    public function importFromArray(array $articlesData, string $source = 'json'): WordpressImportLog
    {
        $log = new WordpressImportLog();
        $log->setSource($source);
        $log->setStatus('processing');
        $log->setMetadata([
            'total_articles' => count($articlesData),
            'started_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        try {
            foreach ($articlesData as $articleData) {
                try {
                    $this->importSingleArticle($articleData, $log);
                } catch (\Exception $e) {
                    $log->addError(sprintf(
                        'Erreur lors de l\'import de l\'article "%s": %s',
                        $articleData['title'] ?? 'Unknown',
                        $e->getMessage()
                    ));
                    $this->logger->error('WordPress import error', [
                        'article' => $articleData['title'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $log->setStatus('success');
        } catch (\Exception $e) {
            $log->setStatus('failed');
            $log->addError('Erreur fatale: ' . $e->getMessage());
            $this->logger->error('WordPress import fatal error', ['error' => $e->getMessage()]);
        }

        $log->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $log;
    }

    /**
     * Importe un seul article
     */
    private function importSingleArticle(array $data, WordpressImportLog $log): void
    {
        // 1. Créer le DTO et valider
        $dto = WordpressArticleImportDTO::fromArray($data);
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            throw new \InvalidArgumentException('Données invalides: ' . (string) $errors);
        }

        // 2. Vérifier si l'article existe déjà (par slug)
        $existingArticle = $this->entityManager->getRepository(Article::class)
            ->findOneBy(['slug' => $dto->slug]);

        if ($existingArticle) {
            $this->logger->info('Article already exists, skipping', ['slug' => $dto->slug]);
            return;
        }

        // 3. Créer l'article
        $article = new Article();
        $article->setTitle($dto->title);
        $article->setContent($dto->content);
        $article->setExcerpt($dto->excerpt);
        $article->setSlug($dto->slug);
        $article->setStatus(ContentStatus::DRAFT);

        // 4. Ajouter les catégories
        foreach ($dto->categories as $categoryName) {
            $category = $this->getOrCreateCategory($categoryName);
            $article->addCategory($category);
        }

        // 5. Ajouter les tags
        foreach ($dto->tags as $tagName) {
            $tag = $this->getOrCreateTag($tagName);
            $article->addTag($tag);
        }

        // 6. Télécharger l'image à la une
        if ($dto->featuredImage) {
            try {
                $localPath = $this->downloadImage($dto->featuredImage, $dto->slug);
                $article->setFeaturedImage($localPath);
                $log->incrementImagesImported();
            } catch (\Exception $e) {
                $this->logger->warning('Failed to download featured image', [
                    'url' => $dto->featuredImage,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 7. Créer le SEO
        $this->seoService->createOrUpdateArticleSeo($article, [
            'seoTitle' => $dto->seo['title'] ?? $dto->title,
            'metaDescription' => $dto->seo['description'] ?? $dto->excerpt ?? substr($dto->content, 0, 160),
            'slug' => $dto->slug,
            'canonicalUrl' => $dto->seo['canonical'] ?? null,
            'noindex' => $dto->seo['noindex'] ?? false,
            'nofollow' => $dto->seo['nofollow'] ?? false,
        ]);

        $this->entityManager->persist($article);
        $log->incrementArticlesImported();
        
        // Flush tous les 10 articles pour éviter les problèmes de mémoire
        if ($log->getArticlesImported() % 10 === 0) {
            $this->entityManager->flush();
        }
    }

    /**
     * Récupère ou crée une catégorie
     */
    private function getOrCreateCategory(string $name): Category
    {
        $category = $this->categoryRepository->findOneBy(['name' => $name]);

        if (!$category) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($this->seoService->generateSlug($name));
            $this->entityManager->persist($category);
        }

        return $category;
    }

    /**
     * Récupère ou crée un tag
     */
    private function getOrCreateTag(string $name): Tag
    {
        $tag = $this->tagRepository->findOneBy(['name' => $name]);

        if (!$tag) {
            $tag = new Tag();
            $tag->setName($name);
            $tag->setSlug($this->seoService->generateSlug($name));
            $this->entityManager->persist($tag);
        }

        return $tag;
    }

    /**
     * Télécharge une image depuis une URL
     */
    private function downloadImage(string $url, string $slug): string
    {
        $response = $this->httpClient->request('GET', $url);
        $content = $response->getContent();

        // Extraire l'extension depuis l'URL ou le Content-Type
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension)) {
            $contentType = $response->getHeaders()['content-type'][0] ?? '';
            $extension = match ($contentType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        }

        // Générer un nom de fichier unique
        $filename = sprintf('%s-%s.%s', $slug, uniqid(), $extension);
        $destination = $this->uploadDirectory . '/articles/' . $filename;

        // Créer le répertoire si nécessaire
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Sauvegarder le fichier
        $result = file_put_contents($destination, $content);
        if ($result === false) {
            throw new FileException('Failed to save image to ' . $destination);
        }

        return '/uploads/articles/' . $filename;
    }

    /**
     * Importe depuis l'API REST WordPress
     */
    public function importFromRestApi(string $wpUrl, int $limit = 100): WordpressImportLog
    {
        $articlesData = [];
        $page = 1;
        $perPage = 10;

        do {
            $response = $this->httpClient->request('GET', $wpUrl . '/wp-json/wp/v2/posts', [
                'query' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    '_embed' => 1, // Inclure les métadonnées (featured image, etc.)
                ],
            ]);

            $posts = $response->toArray();
            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post) {
                $articlesData[] = [
                    'title' => $post['title']['rendered'] ?? '',
                    'content' => $post['content']['rendered'] ?? '',
                    'excerpt' => $post['excerpt']['rendered'] ?? null,
                    'slug' => $post['slug'] ?? '',
                    'published_at' => $post['date'] ?? null,
                    'author' => $post['_embedded']['author'][0]['name'] ?? null,
                    'featured_image' => $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null,
                    'categories' => array_map(
                        fn($cat) => $cat['name'],
                        $post['_embedded']['wp:term'][0] ?? []
                    ),
                    'tags' => array_map(
                        fn($tag) => $tag['name'],
                        $post['_embedded']['wp:term'][1] ?? []
                    ),
                    'seo' => $post['yoast_head_json'] ?? [],
                ];
            }

            $page++;
        } while (count($articlesData) < $limit);

        return $this->importFromArray(array_slice($articlesData, 0, $limit), 'rest_api');
    }
}
