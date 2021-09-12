<?php

namespace App\Tests\End2End\Handler;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\StatusLog;
use App\Entity\User;
use App\Message\AgreementLine\UpdateCompletionFlagCommand;
use App\MessageHandler\AgreementLine\UpdateCompletionFlagCommandHandler;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;
use App\Tests\Utilities\ProductionFixtureHelpers;

class UpdateCompletionHandlerCommandHandlerTest extends ApiTestCase
{
    /** @var mixed */
    private $user;
    /** @var ProductionFixtureHelpers */
    private $productionHelpers;
    /** @var AgreementLineChainFactory */
    private $agreementLineChanFactory;
    /** @var UpdateCompletionFlagCommandHandler */
    private $handlerUnderTest;
    /** @var EntityFactory */
    private $factory;
    /** @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository */
    private $agreementLineRepository;
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = $this->getManager();
        $this->factory = new EntityFactory($this->em);
        $this->agreementLineRepository = $this->em->getRepository(AgreementLine::class);
        $this->productionHelpers = new ProductionFixtureHelpers($this->factory);
        $this->agreementLineChanFactory = new AgreementLineChainFactory($this->factory);

        $this->user = $this->factory->make(User::class, [
            'roles' => ['ROLE_ADMIN']
        ]);

        $this->handlerUnderTest = new UpdateCompletionFlagCommandHandler($this->em);
        $this->em->flush();
    }

    public function testShouldNotUpdateCompletionDateIfThereIsNoProductionTasks()
    {
        // Given
        $agreementLine = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_WAITING]);
        $agreementLineId = $agreementLine->getId();
        $command = new UpdateCompletionFlagCommand($agreementLineId);
        $handler = $this->handlerUnderTest;

        // When & Then
        $handler($command);

        $agreementUnderTest = $this->agreementLineRepository->find($agreementLineId);
        $this->assertNull($agreementUnderTest->getProductionCompletionDate());
    }

    public function testShouldSetCompletionDateAccordingToDpt05TaskCompletionDate()
    {
        // Given
        $agreementLine = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_WAITING]);

        $productions = $this->productionHelpers->makeProductionTasks(
            $this->productionHelpers->getArrayOfProps($agreementLine, ['createdAt' => new \DateTime('2021-09-10')])
        );
        $log1 = $this->factory->make(StatusLog::class, [
            'production' => $productions[4],
            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
            'createdAt' => new \DateTime('2021-09-19 14:15:05'),
            'user' => $this->user
        ]);
        $log2 = $this->factory->make(StatusLog::class, [
            'production' => $productions[4],
            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'createdAt' => new \DateTime('2021-09-20 14:15:05'),
            'user' => $this->user
        ]);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->flush();
        $this->em->clear();

        $command = new UpdateCompletionFlagCommand($agreementLine->getId());
        $handler = $this->handlerUnderTest;

        // When & Then
        $handler($command);
        /** @var AgreementLine $agreementUnderTest */
        $agreementUnderTest = $this->agreementLineRepository->find($agreementLine->getId());

        $completionDate = $agreementUnderTest->getProductionCompletionDate()
            ? $agreementUnderTest->getProductionCompletionDate()->format('Y-m-d H:i:s')
            : null;

        $this->assertEquals('2021-09-20 14:15:05', $completionDate);
    }

    public function testShouldSetCompletionDateToNullIfStatusChangedBackToOtherThanCompleted()
    {
        // Given
        $agreementLine = $this->agreementLineChanFactory->make([], [
            'status' => AgreementLine::STATUS_WAITING,
            'productionCompletionDate' => new \DateTime('2021-09-20 14:15:05')
        ]);

        $productions = $this->productionHelpers->makeProductionTasks(
            $this->productionHelpers->getArrayOfProps($agreementLine, ['createdAt' => new \DateTime('2021-09-10')])
        );
        $log1 = $this->factory->make(StatusLog::class, [
            'production' => $productions[4],
            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'createdAt' => new \DateTime('2021-09-20 14:15:05'),
            'user' => $this->user
        ]);
        $log2 = $this->factory->make(StatusLog::class, [
            'production' => $productions[4],
            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
            'createdAt' => new \DateTime('2021-09-21 12:15:05'),
            'user' => $this->user
        ]);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->flush();
        $this->em->clear();

        $command = new UpdateCompletionFlagCommand($agreementLine->getId());
        $handler = $this->handlerUnderTest;

        // When & Then
        $handler($command);
        /** @var AgreementLine $agreementUnderTest */
        $agreementUnderTest = $this->agreementLineRepository->find($agreementLine->getId());

        $this->assertNull($agreementUnderTest->getProductionCompletionDate());

    }

}