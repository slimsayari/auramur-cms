<?php

namespace App\Enum;

enum ContentStatus: string
{
    case DRAFT = 'draft';
    case READY_FOR_REVIEW = 'ready_for_review';
    case VALIDATED = 'validated';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::READY_FOR_REVIEW => 'En révision',
            self::VALIDATED => 'Validé',
            self::PUBLISHED => 'Publié',
            self::ARCHIVED => 'Archivé',
        };
    }

    public function isPublic(): bool
    {
        return self::PUBLISHED === $this;
    }
}
