<?php

namespace App\System\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ReleaseSessionLockSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest() || !self::isMarked($event->getController())) {
            return;
        }

        // The firewall is lazy: the token is read from the session on first access, so load it while it is open.
        $this->tokenStorage->getToken();

        $request = $event->getRequest();
        if ($request->hasSession() && $request->getSession()->isStarted()) {
            $request->getSession()->save();
        }
    }

    private static function isMarked(callable $controller): bool
    {
        if (\is_array($controller)) {
            [$target, $method] = $controller;
        } elseif (\is_object($controller) && !$controller instanceof \Closure) {
            [$target, $method] = [$controller, '__invoke'];
        } else {
            return false;
        }

        return (new \ReflectionMethod($target, $method))->getAttributes(ReleaseSessionLock::class)
            || (new \ReflectionClass($target))->getAttributes(ReleaseSessionLock::class);
    }
}
