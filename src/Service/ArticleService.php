<?php

namespace App\Service;

use App\DTO\ArticleCreateDTO;
use App\Entity\Article;
use App\Enum\ContentStatus;
use App\Repository\CategoryRepository;
use App\Repository\ArticleRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class ArticleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ArticleRepository $articleRepository,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
    ) {}

    public function createArticle(ArticleCreateDTO $dto): Article
    {
        $article = new Article();
        $article->setSlug($dto->slug);
        $article->setTitle($dto->title);
        $article->setContent($dto->content);
        $article->setExcerpt($dto->excerpt);
        $article->setFeaturedImageUrl($dto->featuredImageUrl);

        // Ajouter les catégories
        foreach ($dto->categoryIds as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $article->addCategory($category);
            }
        }

        // Ajouter les tags
        foreach ($dto->tagIds as $tagId) {
            $tag = $this->tagRepository->find($tagId);
            if ($tag) {
                $article->addTag($tag);
            }
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $article;
    }

    public function updateArticle(Article $article, ArticleCreateDTO $dto): Article
    {
        $article->setTitle($dto->title);
        $article->setContent($dto->content);
        $article->setExcerpt($dto->excerpt);
        $article->setFeaturedImageUrl($dto->featuredImageUrl);

        // Mettre à jour les catégories
        if (!empty($dto->categoryIds)) {
            $article->getCategories()->clear();
            foreach ($dto->categoryIds as $categoryId) {
                $category = $this->categoryRepository->find($categoryId);
                if ($category) {
                    $article->addCategory($category);
                }
            }
        }

        // Mettre à jour les tags
        if (!empty($dto->tagIds)) {
            $article->getTags()->clear();
            foreach ($dto->tagIds as $tagId) {
                $tag = $this->tagRepository->find($tagId);
                if ($tag) {
                    $article->addTag($tag);
                }
            }
        }

        $article->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $article;
    }

    public function publishArticle(Article $article): Article
    {
        $article->setStatus(ContentStatus::PUBLISHED);
        $article->setPublishedAt(new \DateTimeImmutable());
        $article->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $article;
    }

    public function deleteArticle(Article $article): void
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();
    }
}
