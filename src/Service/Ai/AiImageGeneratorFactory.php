<?php

namespace App\Service\Ai;

use App\Entity\AiProviderConfig;
use App\Repository\AiProviderConfigRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Factory pour créer des instances de générateurs d'images IA
 * 
 * Cette factory permet de créer dynamiquement le bon adapter en fonction
 * de la configuration active dans la base de données.
 */
class AiImageGeneratorFactory
{
    public function __construct(
        private AiProviderConfigRepository $configRepository,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $encryptionKey
    ) {
    }

    /**
     * Crée une instance du générateur IA configuré
     */
    public function create(): ?AiImageGeneratorInterface
    {
        $config = $this->configRepository->findActive();

        if (!$config) {
            return null;
        }

        return $this->createFromConfig($config);
    }

    /**
     * Crée une instance à partir d'une configuration spécifique
     */
    public function createFromConfig(AiProviderConfig $config): AiImageGeneratorInterface
    {
        // Déchiffrer la clé API
        $apiKey = $this->decrypt($config->getApiKey());

        return match ($config->getProvider()) {
            'nanobanana' => new NanoBananaImageGenerator(
                $this->httpClient,
                $this->logger,
                $apiKey,
                $config->getModel()
            ),
            // Ajouter d'autres providers ici
            // 'midjourney' => new MidjourneyImageGenerator(...),
            // 'stable_diffusion' => new StableDiffusionImageGenerator(...),
            default => throw new \InvalidArgumentException(
                sprintf('Provider "%s" non supporté', $config->getProvider())
            ),
        };
    }

    /**
     * Chiffre une clé API
     */
    public function encrypt(string $value): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Déchiffre une clé API
     */
    private function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
    }
}
