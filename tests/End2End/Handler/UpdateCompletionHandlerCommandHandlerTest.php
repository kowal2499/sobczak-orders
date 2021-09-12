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

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new EntityFactory($this->getManager());
        $this->productionHelpers = new ProductionFixtureHelpers($this->factory);
        $this->agreementLineChanFactory = new AgreementLineChainFactory($this->factory);

        $this->user = $this->factory->make(User::class, [
            'roles' => ['ROLE_ADMIN']
        ]);

        $this->handlerUnderTest = new UpdateCompletionFlagCommandHandler(
            $this->getManager()
        );
        $this->getManager()->flush();
    }

    public function testShould()
    {
        // Given
        $agreementLine1 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_WAITING]);

        $productions = $this->productionHelpers->makeProductionTasks(
            $this->productionHelpers->getArrayOfProps($agreementLine1, ['createdAt' => new \DateTime('2021-09-10')])
        );
        $log = $this->factory->make(StatusLog::class, [
            'production' => $productions[4],
            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
//            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'createdAt' => new \DateTime('2021-05-15'),
            'user' => $this->user
        ]);
        $this->getManager()->persist($log);

        $this->factory->flush();
        $this->getManager()->clear();

        $command = new UpdateCompletionFlagCommand($agreementLine1->getId());
        $handler = $this->handlerUnderTest;
        $handler($command);

        // Then
        $repository = $this->getManager()->getRepository(AgreementLine::class);
        /** @var AgreementLine $newAgreement */
        $newAgreement = $repository->find($agreementLine1->getId());

        $competionDate = $newAgreement->getProductionCompletionDate()
            ? $newAgreement->getProductionCompletionDate()->format('Y-m-d')
            : null;

        $this->assertEquals('2021-05-15', $competionDate);

    }

}