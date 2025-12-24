# Architecture IA Découplée - CMS Auramur

## Principes de Conception

Le CMS Auramur **orchestre** l'IA mais **ne dépend pas** d'un provider spécifique. L'architecture utilise le pattern **Adapter** pour permettre l'interchangeabilité des providers IA.

---

## Entités

### 1. AiProviderConfig

Configuration des providers IA (NanoBanana, Midjourney, Stable Diffusion, etc.)

```php
class AiProviderConfig
{
    private Uuid $id;
    private string $name;              // "NanoBanana", "Midjourney", etc.
    private string $provider;          // Enum: nanobanana, midjourney, stable_diffusion
    private string $apiKey;            // Chiffré en base
    private ?string $apiSecret;        // Chiffré en base (optionnel)
    private ?string $model;            // Modèle par défaut
    private ?string $defaultPrompt;    // Prompt par défaut
    private ?string $imageSize;        // Taille par défaut (ex: "1024x1024")
    private bool $isActive;            // Provider actif ou non
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
}
```

### 2. AiImageGeneration

Historique des générations d'images IA

```php
class AiImageGeneration
{
    private Uuid $id;
    private string $provider;          // Provider utilisé
    private string $prompt;            // Prompt utilisé
    private ?string $negativePrompt;   // Prompt négatif (optionnel)
    private array $parameters;         // Paramètres JSON (model, size, etc.)
    private string $status;            // Enum: pending, generated, validated, rejected, failed
    private ?string $imageUrl;         // URL de l'image générée
    private ?string $localPath;        // Chemin local si téléchargée
    private ?string $entityType;       // "article" ou "product"
    private ?Uuid $entityId;           // ID de l'article ou produit
    private ?User $generatedBy;        // Utilisateur qui a déclenché
    private ?User $validatedBy;        // Utilisateur qui a validé
    private ?string $rejectionReason;  // Raison du rejet
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $validatedAt;
}
```

### 3. WordpressImportLog

Logs d'import d'articles WordPress

```php
class WordpressImportLog
{
    private Uuid $id;
    private string $source;            // "rest_api", "json", "xml"
    private string $status;            // "processing", "success", "failed"
    private int $articlesImported;
    private int $imagesImported;
    private int $categoriesImported;
    private int $tagsImported;
    private array $errors;             // Erreurs JSON
    private array $metadata;           // Métadonnées JSON
    private \DateTimeImmutable $importedAt;
    private ?\DateTimeImmutable $completedAt;
}
```

---

## Interfaces

### AiImageGeneratorInterface

Interface pour tous les providers IA

```php
interface AiImageGeneratorInterface
{
    /**
     * Génère une image à partir d'un prompt
     *
     * @param string $prompt Le prompt de génération
     * @param array $options Options supplémentaires (model, size, etc.)
     * @return AiImageGenerationResult
     * @throws AiGenerationException
     */
    public function generate(string $prompt, array $options = []): AiImageGenerationResult;

    /**
     * Vérifie le statut d'une génération en cours
     *
     * @param string $generationId ID de la génération
     * @return AiImageGenerationResult
     * @throws AiGenerationException
     */
    public function getStatus(string $generationId): AiImageGenerationResult;

    /**
     * Télécharge l'image générée
     *
     * @param string $imageUrl URL de l'image
     * @param string $destination Chemin de destination
     * @return string Chemin local de l'image
     * @throws AiGenerationException
     */
    public function downloadImage(string $imageUrl, string $destination): string;

    /**
     * Retourne le nom du provider
     *
     * @return string
     */
    public function getProviderName(): string;
}
```

### AiImageGenerationResult

DTO de résultat de génération

```php
class AiImageGenerationResult
{
    public string $generationId;
    public string $status;           // "pending", "completed", "failed"
    public ?string $imageUrl;
    public ?string $error;
    public array $metadata;
}
```

---

## Adapters

### NanoBananaImageGenerator

Implémentation pour NanoBanana

```php
class NanoBananaImageGenerator implements AiImageGeneratorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $baseUrl = 'https://api.nanobanana.com'
    ) {}

    public function generate(string $prompt, array $options = []): AiImageGenerationResult
    {
        // Appel API NanoBanana
        $response = $this->httpClient->request('POST', $this->baseUrl . '/generate', [
            'headers' => ['Authorization' => 'Bearer ' . $this->apiKey],
            'json' => [
                'prompt' => $prompt,
                'model' => $options['model'] ?? 'default',
                'size' => $options['size'] ?? '1024x1024',
            ],
        ]);

        // Transformer la réponse en AiImageGenerationResult
        return new AiImageGenerationResult(/* ... */);
    }

    public function getProviderName(): string
    {
        return 'nanobanana';
    }
}
```

---

## Services

### AiImageService

Service orchestrateur pour la génération d'images IA

```php
class AiImageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AiProviderConfigRepository $providerConfigRepository,
        private AiImageGeneratorFactory $generatorFactory,
        private string $uploadDirectory
    ) {}

    /**
     * Génère une image IA pour une entité
     */
    public function generateImageForEntity(
        string $entityType,
        Uuid $entityId,
        string $prompt,
        ?User $user = null,
        array $options = []
    ): AiImageGeneration {
        // 1. Récupérer le provider actif
        $providerConfig = $this->providerConfigRepository->findActiveProvider();
        
        // 2. Créer le generator
        $generator = $this->generatorFactory->create($providerConfig);
        
        // 3. Générer l'image
        $result = $generator->generate($prompt, $options);
        
        // 4. Créer l'entité AiImageGeneration
        $generation = new AiImageGeneration();
        $generation->setProvider($providerConfig->getProvider());
        $generation->setPrompt($prompt);
        $generation->setParameters($options);
        $generation->setStatus($result->status);
        $generation->setImageUrl($result->imageUrl);
        $generation->setEntityType($entityType);
        $generation->setEntityId($entityId);
        $generation->setGeneratedBy($user);
        
        // 5. Persister
        $this->entityManager->persist($generation);
        $this->entityManager->flush();
        
        return $generation;
    }

    /**
     * Valide une génération IA
     */
    public function validateGeneration(AiImageGeneration $generation, User $user): void
    {
        $generation->setStatus('validated');
        $generation->setValidatedBy($user);
        $generation->setValidatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
    }

    /**
     * Rejette une génération IA
     */
    public function rejectGeneration(AiImageGeneration $generation, string $reason, User $user): void
    {
        $generation->setStatus('rejected');
        $generation->setRejectionReason($reason);
        $generation->setValidatedBy($user);
        $generation->setValidatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();
    }
}
```

### AiImageGeneratorFactory

Factory pour créer les generators

```php
class AiImageGeneratorFactory
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CryptoService $cryptoService
    ) {}

    public function create(AiProviderConfig $config): AiImageGeneratorInterface
    {
        $apiKey = $this->cryptoService->decrypt($config->getApiKey());
        
        return match ($config->getProvider()) {
            'nanobanana' => new NanoBananaImageGenerator(
                $this->httpClient,
                $apiKey,
                $config->getModel()
            ),
            'midjourney' => new MidjourneyImageGenerator(
                $this->httpClient,
                $apiKey
            ),
            'stable_diffusion' => new StableDiffusionImageGenerator(
                $this->httpClient,
                $apiKey
            ),
            default => throw new \InvalidArgumentException("Provider {$config->getProvider()} not supported"),
        };
    }
}
```

### WordpressArticleImporter

Service d'import d'articles WordPress

```php
class WordpressArticleImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private SeoService $seoService,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
        private string $uploadDirectory
    ) {}

    public function importFromJson(array $data): WordpressImportLog
    {
        $log = new WordpressImportLog();
        $log->setSource('json');
        $log->setStatus('processing');
        
        try {
            foreach ($data as $articleData) {
                $this->importSingleArticle($articleData, $log);
            }
            
            $log->setStatus('success');
        } catch (\Exception $e) {
            $log->setStatus('failed');
            $log->addError($e->getMessage());
        }
        
        $log->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        
        return $log;
    }

    private function importSingleArticle(array $data, WordpressImportLog $log): void
    {
        // 1. Créer l'article
        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setExcerpt($data['excerpt'] ?? null);
        $article->setSlug($data['slug']);
        $article->setStatus(ContentStatus::DRAFT);
        
        // 2. Ajouter les catégories
        foreach ($data['categories'] ?? [] as $categoryName) {
            $category = $this->getOrCreateCategory($categoryName);
            $article->addCategory($category);
        }
        
        // 3. Ajouter les tags
        foreach ($data['tags'] ?? [] as $tagName) {
            $tag = $this->getOrCreateTag($tagName);
            $article->addTag($tag);
        }
        
        // 4. Télécharger l'image à la une
        if (!empty($data['featured_image'])) {
            $localPath = $this->downloadImage($data['featured_image']);
            $article->setFeaturedImage($localPath);
        }
        
        // 5. Créer le SEO
        $this->seoService->createOrUpdateArticleSeo($article, [
            'seoTitle' => $data['seo']['title'] ?? $data['title'],
            'metaDescription' => $data['seo']['description'] ?? $article->getExcerpt(),
            'slug' => $data['slug'],
        ]);
        
        $this->entityManager->persist($article);
        $log->incrementArticlesImported();
    }
}
```

---

## Sécurité

### CryptoService

Service de chiffrement des clés API

```php
class CryptoService
{
    public function __construct(
        private string $encryptionKey
    ) {}

    public function encrypt(string $data): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $data): string
    {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $this->encryptionKey, 0, $iv);
    }
}
```

---

## Admin UX

### Page AI Settings

- Accessible via `/admin/ai-settings`
- CRUD pour AiProviderConfig
- Champs :
  - Provider (select)
  - API Key (password input, chiffré)
  - Model (text)
  - Default Prompt (textarea)
  - Image Size (select)
  - Active (checkbox)

### Bouton "Générer Image IA"

Dans l'édition d'un article ou produit :
- Bouton "Générer Image IA"
- Modal avec :
  - Textarea pour le prompt
  - Bouton "Générer"
  - Prévisualisation du résultat
  - Boutons "Valider" / "Rejeter"

---

## Flux de Génération IA

1. **Admin déclenche** : Clic sur "Générer Image IA"
2. **CMS appelle** : `AiImageService::generateImageForEntity()`
3. **Factory crée** : Le generator approprié (NanoBanana, etc.)
4. **Generator appelle** : L'API du provider
5. **Résultat stocké** : Dans `AiImageGeneration` (status = "generated")
6. **Humain valide** : Clic sur "Valider" ou "Rejeter"
7. **Status mis à jour** : "validated" ou "rejected"

---

## Tests

### Tests Unitaires

- `WordpressArticleImporterTest` - Import d'articles
- `AiImageServiceTest` - Génération IA mockée
- `AiImageGeneratorFactoryTest` - Création de generators
- `CryptoServiceTest` - Chiffrement/déchiffrement

### Tests Fonctionnels

- `WordpressImportTest` - Import d'articles WP (happy path)
- `AiImageGenerationTest` - Génération IA mockée

---

## Avantages de cette Architecture

✅ **Découplage** : Le CMS ne dépend pas de NanoBanana  
✅ **Interchangeabilité** : Facile d'ajouter Midjourney, Stable Diffusion, etc.  
✅ **Sécurité** : Clés API chiffrées en base  
✅ **Traçabilité** : Historique complet des générations  
✅ **Testabilité** : Interfaces mockables  
✅ **Évolutivité** : Facile d'ajouter de nouveaux providers
