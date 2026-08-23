<?php

namespace App\Module\ApiToken\CLI;

use App\Module\ApiToken\Entity\ApiToken;
use App\Module\ApiToken\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:list',
    description: 'Wypisuje wydane tokeny API.',
)]
class ListApiTokensCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ApiTokenRepository $repository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'email',
            InputArgument::OPTIONAL,
            'Ogranicz listę do jednego użytkownika'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        if (null !== $email) {
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (null === $user) {
                $io->error(sprintf('Nie ma użytkownika o adresie „%s".', $email));

                return Command::FAILURE;
            }

            $tokens = $this->repository->findByUser($user);
        } else {
            $tokens = $this->repository->findBy([], ['createdAt' => 'DESC']);
        }

        if ([] === $tokens) {
            $io->writeln('Brak wydanych tokenów.');

            return Command::SUCCESS;
        }

        $io->table(
            ['ID', 'Użytkownik', 'Nazwa', 'Utworzony', 'Wygasa', 'Ostatnio użyty'],
            array_map(static fn (ApiToken $token) => [
                $token->getId(),
                $token->getUser()->getEmail(),
                $token->getName(),
                $token->getCreatedAt()->format('Y-m-d'),
                self::describeExpiry($token),
                $token->getLastUsedAt()?->format('Y-m-d H:i') ?? 'nigdy',
            ], $tokens)
        );

        return Command::SUCCESS;
    }

    private static function describeExpiry(ApiToken $token): string
    {
        $expiresAt = $token->getExpiresAt();

        if (null === $expiresAt) {
            return 'bezterminowy';
        }

        return $expiresAt->format('Y-m-d') . ($token->isExpired() ? ' (wygasł)' : '');
    }
}
