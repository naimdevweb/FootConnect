<?php

namespace App\Command;

use App\Services\DataRetentionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:data-retention:cleanup',
    description: 'Nettoie les données selon la politique de conservation',
)]
class DataRetentionCleanupCommand extends Command
{
    public function __construct(
        private readonly DataRetentionService $dataRetentionService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Nettoyage des données selon la politique de conservation');
        
        try {
            // 1. Nettoyage des comptes inactifs
            $io->section('Nettoyage des comptes inactifs');
            $inactiveCount = $this->dataRetentionService->cleanInactiveAccounts();
            $io->success(sprintf('%d comptes inactifs ont été anonymisés', $inactiveCount));
            
            // 2. Suppression définitive des comptes supprimés après la période de grâce
            $io->section('Purge des comptes supprimés');
            $deletedCount = $this->dataRetentionService->purgeDeletedAccounts();
            $io->success(sprintf('%d comptes supprimés ont été définitivement effacés', $deletedCount));
            
            $io->success('Nettoyage des données terminé avec succès');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Une erreur est survenue lors du nettoyage des données: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}