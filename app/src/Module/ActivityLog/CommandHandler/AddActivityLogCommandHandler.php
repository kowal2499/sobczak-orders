<?php

namespace App\Module\ActivityLog\CommandHandler;

use App\Entity\User;
use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Event\ActivityLogWasAddedEvent;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Repository\UserRepository;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class AddActivityLogCommandHandler
{
    private const IGNORED_FIELDS = [AddActivityLogCommand::IMPERSONATE_KEY];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ActivityLogRepository $activityLogRepository,
        private readonly UserRepository $userRepository,
        private readonly EventBus $eventBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(AddActivityLogCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $user = $this->resolveAuthor($command);
            $contextData = $this->stripReservedKeys($command->contextData);

            $log = new ActivityLog(
                $command->type,
                $command->message,
                $user,
                $command->level ?? LogLevel::INFO,
                $command->priority,
                $command->contentParams,
            );

            foreach ($contextData as $name => $value) {
                $log->addLogField((string) $name, (string) $value);
            }

            $this->activityLogRepository->save($log, true);

            if ($command->createdDate !== null) {
                $log->setCreatedAt($command->createdDate);
                $this->em->flush();
            }

            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }

        $this->eventBus->dispatch(new ActivityLogWasAddedEvent(
            $log->getId(),
            $log->getType(),
            $log->getCreatedAt(),
            $this->mapLogFields($log),
        ));
    }

    private function resolveAuthor(AddActivityLogCommand $command): ?User
    {
        $impersonateId = $this->extractImpersonateId($command->contextData);
        if ($impersonateId !== null) {
            $impersonated = $this->userRepository->find($impersonateId);
            if ($impersonated === null) {
                throw new \RuntimeException(sprintf('Impersonated user "%d" not found', $impersonateId));
            }
            return $impersonated;
        }

        if ($command->authorUserId !== null) {
            return $this->userRepository->find($command->authorUserId);
        }

        $current = $this->security->getUser();
        return $current instanceof User ? $current : null;
    }

    private function extractImpersonateId(array $contextData): ?int
    {
        if (!array_key_exists(AddActivityLogCommand::IMPERSONATE_KEY, $contextData)) {
            return null;
        }

        $raw = $contextData[AddActivityLogCommand::IMPERSONATE_KEY];
        if (!is_numeric($raw) || (int) $raw <= 0) {
            throw new \RuntimeException('impersonateUserId must be a positive integer');
        }

        return (int) $raw;
    }

    private function stripReservedKeys(array $contextData): array
    {
        foreach (self::IGNORED_FIELDS as $key) {
            unset($contextData[$key]);
        }
        return $contextData;
    }

    /**
     * @return array<string, string>
     */
    private function mapLogFields(ActivityLog $log): array
    {
        $out = [];
        foreach ($log->getLogFields() as $field) {
            $out[$field->getName()] = $field->getValue();
        }
        return $out;
    }
}
