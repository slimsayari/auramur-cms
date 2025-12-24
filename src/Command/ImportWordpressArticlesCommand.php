<?php

namespace App\Command;

use App\Service\WordpressArticleImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:wordpress-articles',
    description: 'Importe des articles depuis WordPress (JSON, REST API ou XML)',
)]
class ImportWordpressArticlesCommand extends Command
{
    public function __construct(
        private WordpressArticleImporter $importer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, 'Source de l\'import (fichier JSON ou URL de l\'API REST WordPress)')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type de source (json ou rest_api)', 'json')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre maximum d\'articles à importer', 100)
            ->setHelp(<<<'HELP'
Cette commande permet d'importer des articles depuis WordPress.

Exemples d'utilisation :

1. Import depuis un fichier JSON :
   <info>php bin/console app:import:wordpress-articles wordpress-export.json</info>

2. Import depuis l'API REST WordPress :
   <info>php bin/console app:import:wordpress-articles https://example.com --type=rest_api --limit=50</info>

Format JSON attendu :
[
  {
    "title": "Mon article",
    "content": "<p>Contenu de l'article...</p>",
    "excerpt": "Résumé de l'article",
    "slug": "mon-article",
    "published_at": "2024-01-01 12:00:00",
    "author": "John Doe",
    "featured_image": "https://example.com/image.jpg",
    "categories": ["Actualités", "Tech"],
    "tags": ["wordpress", "migration"],
    "seo": {
      "title": "Mon article - Site",
      "description": "Description SEO",
      "canonical": "https://example.com/mon-article",
      "noindex": false,
      "nofollow": false
    }
  }
]
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('source');
        $type = $input->getOption('type');
        $limit = (int) $input->getOption('limit');

        $io->title('Import d\'articles WordPress');
        $io->text(sprintf('Source: <info>%s</info>', $source));
        $io->text(sprintf('Type: <info>%s</info>', $type));

        try {
            if ($type === 'rest_api') {
                $io->text(sprintf('Limite: <info>%d articles</info>', $limit));
                $io->newLine();
                $io->text('Récupération des articles depuis l\'API REST WordPress...');
                
                $log = $this->importer->importFromRestApi($source, $limit);
            } else {
                // Import depuis JSON
                if (!file_exists($source)) {
                    $io->error(sprintf('Le fichier "%s" n\'existe pas.', $source));
                    return Command::FAILURE;
                }

                $io->text('Lecture du fichier JSON...');
                $jsonContent = file_get_contents($source);
                $articlesData = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $io->error('Erreur lors du parsing JSON: ' . json_last_error_msg());
                    return Command::FAILURE;
                }

                if (!is_array($articlesData)) {
                    $io->error('Le fichier JSON doit contenir un tableau d\'articles.');
                    return Command::FAILURE;
                }

                $io->text(sprintf('Nombre d\'articles trouvés: <info>%d</info>', count($articlesData)));
                $io->newLine();
                $io->text('Import en cours...');

                $log = $this->importer->importFromArray($articlesData, 'json');
            }

            $io->newLine();
            $io->success('Import terminé !');

            // Afficher les statistiques
            $io->section('Statistiques');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Statut', $log->getStatus()],
                    ['Articles importés', $log->getArticlesImported()],
                    ['Images importées', $log->getImagesImported()],
                    ['Catégories créées', $log->getCategoriesImported()],
                    ['Tags créés', $log->getTagsImported()],
                    ['Erreurs', count($log->getErrors())],
                ]
            );

            // Afficher les erreurs s'il y en a
            if (!empty($log->getErrors())) {
                $io->section('Erreurs');
                $io->listing($log->getErrors());
            }

            return $log->getStatus() === 'success' ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'import: ' . $e->getMessage());
            $io->text('Stack trace:');
            $io->text($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
