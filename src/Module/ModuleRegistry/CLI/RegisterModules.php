<?php

namespace App\Module\ModuleRegistry\CLI;

use App\Module\ModuleRegistry\Repository\ModuleRepository;
use App\Module\ModuleRegistry\Service\ModulesConfigurationLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:module:register', description: 'Register modules defined in YAML file')]
class RegisterModules extends Command
{

    public function __construct(
        private readonly ModulesConfigurationLoader $configurationLoader,
        private readonly ModuleRepository $moduleRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->block('Rejestracja modułów na podstawie konfiguracji');

        $modules = $this->configurationLoader->getModules();
        $io->progressStart(count($modules));

        foreach ($modules as $incomingModule) {
            $dbModule = $this->moduleRepository->findOneByNamespace($incomingModule->getNamespace());
            if (null === $dbModule) {
                $this->moduleRepository->add($incomingModule, false);
            } else {
                $dbModule->setDescription($incomingModule->getDescription());
                $dbModule->setActive($incomingModule->isActive());
            }
            $io->progressAdvance();
        }
        $io->progressFinish();

        $this->entityManager->flush();
        $io->success('Zakończono rejestrację modułów');
        return self::SUCCESS;
    }

}