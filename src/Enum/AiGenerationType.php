<?php

namespace App\Enum;

enum AiGenerationType: string
{
    case DESCRIPTION = 'description';
    case TITLE = 'title';
    case TAGS = 'tags';
    case SEO_META = 'seo_meta';
    case ARTICLE_CONTENT = 'article_content';

    public function label(): string
    {
        return match ($this) {
            self::DESCRIPTION => 'Description',
            self::TITLE => 'Titre',
            self::TAGS => 'Tags',
            self::SEO_META => 'Métadonnées SEO',
            self::ARTICLE_CONTENT => 'Contenu article',
        };
    }
}
