<?php

namespace App\Module\ModuleRegistry\CLI;

use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\ValueObject\GrantOption;
use App\Module\Authorization\ValueObject\GrantOptionsCollection;
use App\Module\Authorization\ValueObject\GrantType;
use App\Module\ModuleRegistry\Entity\Module;
use App\Module\ModuleRegistry\Repository\ModuleRepository;
use App\Service\ModuleConfigurationLoader;
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
        private readonly ModuleConfigurationLoader $configurationLoader,
        private readonly ModuleRepository $moduleRepository,
        private readonly AuthGrantRepository $grantRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->block('Rejestracja modułów na podstawie konfiguracji');

        $rawModules = $this->configurationLoader->load();
        $io->progressStart(count($rawModules));

        foreach ($rawModules as $raw) {
            $namespace = $raw['namespace'];
            $dbModule = $this->moduleRepository->findOneByNamespace($namespace);

            if (null === $dbModule) {
                $dbModule = new Module();
                $dbModule->setNamespace($namespace);
                $this->moduleRepository->add($dbModule, false);
            }

            $dbModule->setDescription($raw['description'] ?? '');
            $dbModule->setActive($raw['active'] ?? false);


            foreach ($raw['grants'] ?? [] as $grantData) {
                /** @var AuthGrant|null $dbGrant */
                $dbGrant = $this->grantRepository->findOneBySlug($grantData['slug']);
                if (null === $dbGrant) {
                    $dbGrant = new AuthGrant($grantData['slug'], $dbModule);
                    $this->grantRepository->add($dbGrant, false);
                }

                $dbGrant->setName($grantData['name'] ?? '');
                $dbGrant->setDescription($grantData['description'] ?? '');
                $dbGrant->setType(GrantType::from($grantData['type'] ?? 'boolean'));
                $options = $grantData['options'] ?? [];
                $grantOptions = new GrantOptionsCollection(...array_map(
                    fn($opt) => new GrantOption($opt['label'], $opt['optionSlug']),
                    $options
                ));
                $dbGrant->setOptions($grantOptions);

            }
            $io->progressAdvance();
        }
        $this->entityManager->flush();
        $io->progressFinish();
        
        $io->success('Zakończono rejestrację modułów');
        return self::SUCCESS;
    }

}