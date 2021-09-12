<?php

namespace App\Tests\Unit\MessageHandler\AgreementLine;

use App\Message\AgreementLine\UpdateCompletionFlagCommand;
use App\MessageHandler\AgreementLine\UpdateCompletionFlagCommandHandler;
use PHPUnit\Framework\TestCase;

class UpdateCompletionFlagCommandHandlerTest extends TestCase
{
    /** @var UpdateCompletionFlagCommandHandler */
    private $handlerUnderTest;

//    protected function setUp(): void
//    {
//        parent::setUp();
//        $this->handlerUnderTest = new UpdateCompletionFlagCommandHandler();
//    }
//
//    public function testShouldLaunchHandler()
//    {
//        $command = new UpdateCompletionFlagCommand(12);
//        $handler = $this->handlerUnderTest;
//        $handler($command);
//    }
}