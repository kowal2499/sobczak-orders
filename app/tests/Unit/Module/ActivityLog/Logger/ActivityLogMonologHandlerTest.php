<?php

namespace App\Tests\Unit\Module\ActivityLog\Logger;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\ActivityLog\Logger\ActivityLogMonologHandler;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\System\CommandBus;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

class ActivityLogMonologHandlerTest extends TestCase
{
    public function testDispatchesCommandFromRecord(): void
    {
        $commandBus = $this->createMock(CommandBus::class);
        $captured = null;

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (object $cmd) use (&$captured): Envelope {
                $captured = $cmd;
                return new Envelope($cmd);
            });

        $handler = new ActivityLogMonologHandler($commandBus, Logger::DEBUG);
        $handler->handle($this->makeRecord(
            level: Logger::INFO,
            levelName: 'INFO',
            message: 'agreement created',
            context: [
                'type' => 'agreement.created',
                'agreementId' => 123,
                'customerId' => 'C-42',
            ],
        ));

        $this->assertInstanceOf(AddActivityLogCommand::class, $captured);
        $this->assertSame('agreement.created', $captured->type);
        $this->assertSame('agreement created', $captured->message);
        $this->assertSame(LogLevel::INFO, $captured->level);
        $this->assertSame(LogPriority::normal, $captured->priority);
        $this->assertSame(
            ['agreementId' => '123', 'customerId' => 'C-42'],
            $captured->contextData,
        );
    }

    public function testFallsBackOnInvalidPriorityAndUsesDefaultType(): void
    {
        $commandBus = $this->createMock(CommandBus::class);
        $captured = null;

        $commandBus->method('dispatch')
            ->willReturnCallback(function (object $cmd) use (&$captured): Envelope {
                $captured = $cmd;
                return new Envelope($cmd);
            });

        $handler = new ActivityLogMonologHandler($commandBus, Logger::DEBUG);
        $handler->handle($this->makeRecord(
            level: Logger::WARNING,
            levelName: 'WARNING',
            message: 'something happened',
            context: ['priority' => 'NOT_A_PRIORITY'],
        ));

        $this->assertInstanceOf(AddActivityLogCommand::class, $captured);
        $this->assertSame('default', $captured->type);
        $this->assertSame(LogLevel::WARNING, $captured->level);
        $this->assertSame(LogPriority::normal, $captured->priority);
        $this->assertSame([], $captured->contextData);
    }

    public function testPropagatesImpersonateUserIdAsContextField(): void
    {
        $commandBus = $this->createMock(CommandBus::class);
        $captured = null;

        $commandBus->method('dispatch')
            ->willReturnCallback(function (object $cmd) use (&$captured): Envelope {
                $captured = $cmd;
                return new Envelope($cmd);
            });

        $handler = new ActivityLogMonologHandler($commandBus, Logger::DEBUG);
        $handler->handle($this->makeRecord(
            level: Logger::INFO,
            levelName: 'INFO',
            message: 'impersonated action',
            context: [
                'type' => 'agreement.created',
                'impersonateUserId' => 99,
            ],
        ));

        $this->assertInstanceOf(AddActivityLogCommand::class, $captured);
        $this->assertSame(
            [AddActivityLogCommand::IMPERSONATE_KEY => '99'],
            $captured->contextData,
            'impersonateUserId must reach the command handler via contextData',
        );
    }

    private function makeRecord(int $level, string $levelName, string $message, array $context = []): array
    {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'channel' => 'activity_log',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];
    }
}
