<?php

namespace App\Command;

use App\Service\WooProductImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:woocommerce',
    description: 'Import products from WooCommerce (JSON or CSV)',
)]
class ImportWooCommerceCommand extends Command
{
    public function __construct(
        private WooProductImporter $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to JSON or CSV file')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'File format (json|csv)', 'json')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate import without persisting data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $format = $input->getOption('format');
        $dryRun = $input->getOption('dry-run');

        // Vérifier que le fichier existe
        if (!file_exists($filePath)) {
            $io->error("Le fichier {$filePath} n'existe pas.");
            return Command::FAILURE;
        }

        $io->title('Import WooCommerce');
        $io->info("Fichier: {$filePath}");
        $io->info("Format: {$format}");

        if ($dryRun) {
            $io->warning('Mode DRY-RUN activé - Aucune donnée ne sera persistée');
        }

        try {
            $io->section('Import en cours...');
            $progressBar = $io->createProgressBar();
            $progressBar->start();

            // Lancer l'import
            if ($format === 'csv') {
                $log = $this->importer->importFromCsv($filePath);
            } else {
                $data = json_decode(file_get_contents($filePath), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $io->error('Erreur de parsing JSON: ' . json_last_error_msg());
                    return Command::FAILURE;
                }
                $log = $this->importer->importFromJson($data);
            }

            $progressBar->finish();
            $io->newLine(2);

            // Afficher le résumé
            $io->section('Résumé de l\'import');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Statut', $log->getStatus()],
                    ['Produits importés', $log->getProductsImported()],
                    ['Variantes importées', $log->getVariantsImported()],
                    ['Images importées', $log->getImagesImported()],
                    ['Durée', $log->getCompletedAt() ? $log->getCompletedAt()->diff($log->getImportedAt())->format('%i min %s sec') : 'En cours'],
                ]
            );

            // Afficher les erreurs
            if (!empty($log->getErrors())) {
                $io->section('Erreurs rencontrées');
                foreach ($log->getErrors() as $error) {
                    $io->error($error);
                }
            }

            if ($log->getStatus() === 'success') {
                $io->success('Import terminé avec succès !');
                return Command::SUCCESS;
            } else {
                $io->warning('Import terminé avec des erreurs.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'import: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
