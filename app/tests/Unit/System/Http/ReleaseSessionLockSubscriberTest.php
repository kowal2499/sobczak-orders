<?php

namespace App\Tests\Unit\System\Http;

use App\System\Http\ReleaseSessionLock;
use App\System\Http\ReleaseSessionLockSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReleaseSessionLockSubscriberTest extends TestCase
{
    public function testShouldCloseSessionForMarkedMethod(): void
    {
        $session = $this->dispatch([new MarkedMethodController(), 'marked']);

        $this->assertFalse($session->isStarted());
    }

    public function testShouldCloseSessionForMethodOfMarkedClass(): void
    {
        $session = $this->dispatch([new MarkedClassController(), 'action']);

        $this->assertFalse($session->isStarted());
    }

    public function testShouldCloseSessionForMarkedInvokableController(): void
    {
        $session = $this->dispatch(new MarkedInvokableController());

        $this->assertFalse($session->isStarted());
    }

    public function testShouldKeepSessionOpenForUnmarkedMethod(): void
    {
        $session = $this->dispatch([new MarkedMethodController(), 'unmarked']);

        $this->assertTrue($session->isStarted());
    }

    public function testShouldKeepSessionOpenForClosure(): void
    {
        $session = $this->dispatch(fn () => null);

        $this->assertTrue($session->isStarted());
    }

    public function testShouldKeepSessionOpenForSubRequest(): void
    {
        $session = $this->dispatch([new MarkedClassController(), 'action'], HttpKernelInterface::SUB_REQUEST);

        $this->assertTrue($session->isStarted());
    }

    public function testShouldLoadTokenBeforeClosingSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturnCallback(function () use ($session) {
                $this->assertTrue($session->isStarted());

                return null;
            });

        $this->dispatch([new MarkedClassController(), 'action'], session: $session, tokenStorage: $tokenStorage);

        $this->assertFalse($session->isStarted());
    }

    private function dispatch(
        callable $controller,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        ?Session $session = null,
        ?TokenStorageInterface $tokenStorage = null,
    ): Session {
        if (!$session) {
            $session = new Session(new MockArraySessionStorage());
            $session->start();
        }
        $request = new Request();
        $request->setSession($session);

        $subscriber = new ReleaseSessionLockSubscriber($tokenStorage ?? $this->createMock(TokenStorageInterface::class));
        $subscriber->onKernelController(
            new ControllerEvent($this->createMock(HttpKernelInterface::class), $controller, $request, $requestType)
        );

        return $session;
    }
}

class MarkedMethodController
{
    #[ReleaseSessionLock]
    public function marked(): void
    {
    }

    public function unmarked(): void
    {
    }
}

#[ReleaseSessionLock]
class MarkedClassController
{
    public function action(): void
    {
    }
}

#[ReleaseSessionLock]
class MarkedInvokableController
{
    public function __invoke(): void
    {
    }
}
