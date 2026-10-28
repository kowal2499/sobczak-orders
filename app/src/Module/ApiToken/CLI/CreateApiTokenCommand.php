<?php

namespace App\Module\ApiToken\CLI;

use App\Module\ApiToken\Service\ApiTokenIssuer;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:create',
    description: 'Wydaje token API dla użytkownika o podanym adresie e-mail.',
)]
class CreateApiTokenCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ApiTokenIssuer $issuer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail użytkownika, dla którego wydajemy token')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Nazwa tokenu, np. „Postman - laptop"', 'Token API')
            ->addOption(
                'ttl',
                null,
                InputOption::VALUE_REQUIRED,
                'Ważność w dniach; 0 oznacza token bezterminowy',
                (string) ApiTokenIssuer::DEFAULT_TTL_DAYS
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $name = trim((string) $input->getOption('name'));
        $ttlDays = (int) $input->getOption('ttl');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (null === $user) {
            $io->error(sprintf('Nie ma użytkownika o adresie „%s".', $email));

            return Command::FAILURE;
        }

        if (!$user->isActive()) {
            $io->error(sprintf('Konto „%s" jest nieaktywne - token byłby bezużyteczny.', $email));

            return Command::FAILURE;
        }

        if ('' === $name) {
            $io->error('Nazwa tokenu nie może być pusta.');

            return Command::FAILURE;
        }

        if ($ttlDays < 0) {
            $io->error('Ważność nie może być ujemna. Użyj 0, żeby wydać token bezterminowy.');

            return Command::FAILURE;
        }

        $issued = $this->issuer->issue($user, $name, 0 === $ttlDays ? null : $ttlDays);
        $expiresAt = $issued->token->getExpiresAt();

        $io->success(sprintf(
            'Token wydany dla %s (%s), %s.',
            $email,
            $name,
            null === $expiresAt ? 'bezterminowy' : 'wygasa ' . $expiresAt->format('Y-m-d')
        ));

        $io->writeln('  ' . $issued->plainToken);
        $io->newLine();
        $io->warning('Skopiuj token teraz - nie będzie już nigdzie widoczny.');
        $io->writeln('  Użycie: nagłówek <info>Authorization: Bearer \<token></info>');
        $io->newLine();

        return Command::SUCCESS;
    }
}
