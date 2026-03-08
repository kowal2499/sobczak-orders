<?php

namespace App\Module\Tag\CLI;

use App\Module\Tag\Command\CreateTagDefinitionCommand;
use App\Module\Tag\DTO\TagDefinitionDTO;
use App\Module\Tag\Repository\TagDefinitionRepository;
use App\System\CommandBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTagDefinitions extends Command
{
    protected static $defaultName = 'app:tag-definition:create';

    public function __construct(
        private readonly TagDefinitionRepository $tagDefinitionRepository,
        private readonly CommandBus $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Creates default tag definitions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(['', 'Creating default tag definitions', '================================']);

        $created = 0;
        $skipped = 0;

        foreach ($this->getDefaultTagDefinitions() as $dto) {
            if ($this->tagDefinitionRepository->findBySlug($dto->getSlug()) !== null) {
                $output->writeln(sprintf('  <comment>Skipped</comment>: %s (slug: %s)', $dto->getName(), $dto->getSlug()));
                $skipped++;
                continue;
            }

            $this->commandBus->dispatch(new CreateTagDefinitionCommand($dto));
            $output->writeln(sprintf('  <info>Created</info>: %s (slug: %s)', $dto->getName(), $dto->getSlug()));
            $created++;
        }

        $output->writeln(sprintf('%sCreated: %d, Skipped: %d', PHP_EOL, $created, $skipped));

        return Command::SUCCESS;
    }

    protected function getDefaultTagDefinitions(): array
    {
        return [
            new TagDefinitionDTO(
            'Dokumentacja wydana',
                'agreement-line',
                'fileEarmarkText',
                '#3e44ea',
                'dokumentacja-wydana'
            ),
            new TagDefinitionDTO(
                'Złożone warunkowo, przekroczone moce produkcyjne',
                'agreement-line',
                'calendar2Check',
                '#2ba40a',
                'capacity-exceeded'
            ),
        ];
    }

}