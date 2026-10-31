<?php

namespace App\Module\Authorization\EventSubscriber;

use App\Entity\User;
use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\Authorization\Security\LoginFormAuthenticator;
use App\System\CommandBus;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class UserSessionLogSubscriber implements EventSubscriberInterface
{
    public const TYPE_LOGIN = 'user.login';
    public const TYPE_LOGOUT = 'user.logout';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        // Only the login form starts a working session. API tokens authenticate on every request, and a session
        // restored from the remember-me cookie is sent to the login form anyway (access_control requires
        // IS_AUTHENTICATED_FULLY), so counting either would inflate the numbers.
        $user = $event->getUser();
        if (!$event->getAuthenticator() instanceof LoginFormAuthenticator || !$user instanceof User) {
            return;
        }

        $this->log(self::TYPE_LOGIN, 'activity_log.user.logged_in', $user);
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if (!$user instanceof User) {
            return;
        }

        $this->log(self::TYPE_LOGOUT, 'activity_log.user.logged_out', $user);
    }

    private function log(string $type, string $message, User $user): void
    {
        // A failed journal entry must never block signing in or out.
        try {
            $this->commandBus->dispatch(new AddActivityLogCommand(
                message: $message,
                type: $type,
                contextData: ['userId' => (string) $user->getId()],
                authorUserId: $user->getId(),
            ));
        } catch (\Throwable $e) {
            $this->logger->error('Could not journal user session event', ['type' => $type, 'exception' => $e]);
        }
    }
}
