<?php

namespace App\Service;

use App\Entity\Translation;
use App\Repository\TranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class TranslationService
{
    private const DEFAULT_LOCALE = 'fr';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslationRepository $translationRepository,
    ) {}

    public function setTranslation(
        string $entityType,
        Uuid $entityId,
        string $field,
        string $value,
        string $locale = self::DEFAULT_LOCALE
    ): Translation {
        // Chercher la traduction existante
        $translation = $this->translationRepository->findByEntityAndField(
            $entityType,
            $entityId,
            $field,
            $locale
        );

        if (!$translation) {
            $translation = new Translation();
            $translation->setEntityType($entityType);
            $translation->setEntityId($entityId);
            $translation->setField($field);
            $translation->setLocale($locale);
        }

        $translation->setValue($value);
        $translation->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($translation);
        $this->entityManager->flush();

        return $translation;
    }

    public function getTranslation(
        string $entityType,
        Uuid $entityId,
        string $field,
        string $locale = self::DEFAULT_LOCALE
    ): ?string {
        $translation = $this->translationRepository->findByEntityAndField(
            $entityType,
            $entityId,
            $field,
            $locale
        );

        return $translation?->getValue();
    }

    public function getTranslations(
        string $entityType,
        Uuid $entityId,
        string $locale = self::DEFAULT_LOCALE
    ): array {
        $translations = $this->translationRepository->findByEntity($entityType, $entityId, $locale);

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->getField()] = $translation->getValue();
        }

        return $result;
    }

    public function setMultipleTranslations(
        string $entityType,
        Uuid $entityId,
        array $fields,
        string $locale = self::DEFAULT_LOCALE
    ): void {
        foreach ($fields as $field => $value) {
            $this->setTranslation($entityType, $entityId, $field, $value, $locale);
        }
    }

    public function deleteTranslation(
        string $entityType,
        Uuid $entityId,
        string $field,
        string $locale = self::DEFAULT_LOCALE
    ): void {
        $translation = $this->translationRepository->findByEntityAndField(
            $entityType,
            $entityId,
            $field,
            $locale
        );

        if ($translation) {
            $this->entityManager->remove($translation);
            $this->entityManager->flush();
        }
    }

    public function getAvailableLocales(string $entityType, Uuid $entityId): array
    {
        return $this->translationRepository->getAvailableLocales($entityType, $entityId);
    }

    public function copyTranslations(
        string $entityType,
        Uuid $sourceEntityId,
        Uuid $targetEntityId,
        string $locale = self::DEFAULT_LOCALE
    ): void {
        $translations = $this->translationRepository->findByEntity($entityType, $sourceEntityId, $locale);

        foreach ($translations as $translation) {
            $this->setTranslation(
                $entityType,
                $targetEntityId,
                $translation->getField(),
                $translation->getValue(),
                $locale
            );
        }
    }
}
