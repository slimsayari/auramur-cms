<?php

namespace App\Service\Ai;

use App\DTO\AiImageGenerationResult;
use App\Exception\AiGenerationException;

/**
 * Interface pour tous les providers de génération d'images IA
 * 
 * Cette interface permet de découpler le CMS des providers spécifiques (NanoBanana, Midjourney, etc.)
 * et de les rendre interchangeables.
 */
interface AiImageGeneratorInterface
{
    /**
     * Génère une image à partir d'un prompt
     *
     * @param string $prompt Le prompt de génération
     * @param array $options Options supplémentaires (model, size, negative_prompt, etc.)
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

    /**
     * Vérifie si le provider est configuré correctement
     *
     * @return bool
     */
    public function isConfigured(): bool;
}
