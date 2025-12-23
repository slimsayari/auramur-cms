<?php

namespace App\Command;

use App\Service\TypesenseExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export:typesense',
    description: 'Export products to Typesense search engine',
)]
class ExportTypesenseCommand extends Command
{
    public function __construct(
        private TypesenseExporter $exporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show JSON payload without sending to Typesense')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force export even if Typesense is not configured');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $io->title('Export Typesense');

        if ($dryRun) {
            $io->warning('Mode DRY-RUN activé - Les données ne seront pas envoyées à Typesense');
        }

        try {
            $io->section('Export en cours...');
            $progressBar = $io->createProgressBar();
            $progressBar->start();

            // Exporter les produits
            $result = $this->exporter->exportAllProducts($dryRun);

            $progressBar->finish();
            $io->newLine(2);

            // Afficher le résumé
            $io->section('Résumé de l\'export');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Produits exportés', $result['exported']],
                    ['Produits ignorés', $result['skipped']],
                    ['Erreurs', count($result['errors'])],
                    ['Durée', $result['duration'] . ' sec'],
                ]
            );

            // Afficher les erreurs
            if (!empty($result['errors'])) {
                $io->section('Erreurs rencontrées');
                foreach ($result['errors'] as $error) {
                    $io->error($error);
                }
            }

            // Afficher un exemple de payload en mode dry-run
            if ($dryRun && !empty($result['sample'])) {
                $io->section('Exemple de payload JSON');
                $io->writeln(json_encode($result['sample'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            if (empty($result['errors'])) {
                $io->success('Export terminé avec succès !');
                return Command::SUCCESS;
            } else {
                $io->warning('Export terminé avec des erreurs.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'export: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
