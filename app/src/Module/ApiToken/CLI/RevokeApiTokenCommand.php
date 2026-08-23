<?php

namespace App\Module\ApiToken\CLI;

use App\Module\ApiToken\Repository\ApiTokenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:revoke',
    description: 'Unieważnia token API o podanym identyfikatorze.',
)]
class RevokeApiTokenCommand extends Command
{
    public function __construct(
        private readonly ApiTokenRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Identyfikator tokenu z app:api-token:list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = (int) $input->getArgument('id');
        $token = $this->repository->find($id);

        if (null === $token) {
            $io->error(sprintf('Nie ma tokenu o identyfikatorze %d.', $id));

            return Command::FAILURE;
        }

        $description = sprintf('%s (%s)', $token->getName(), $token->getUser()->getEmail());
        $this->repository->remove($token);

        $io->success(sprintf('Token „%s" został unieważniony.', $description));

        return Command::SUCCESS;
    }
}
