<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\Production;
use App\Module\Agreement\Event\AgreementLineWasUpdatedEvent;
use App\System\EventBus;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

/**
 * Bazowa klasa dla charakteryzujących testów mierników dashboardu.
 *
 * Każdy test ustawia dane wprost w metodzie testowej. Helper tworzy realne encje
 * (AgreementLine + Production), a następnie emituje AgreementLineWasUpdatedEvent —
 * zarejestrowany event handler synchronicznie przebudowuje AgreementLineRM. Dzięki temu
 * te same dane karmią obecną implementację (zapytania do encji) i przyszłą (read model).
 */
abstract class BaseProductionReportsTestCase extends ApiTestCase
{
    protected EntityFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->factory = new EntityFactory($this->getManager());
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    /**
     * Tworzy pełny łańcuch Customer→Agreement→AgreementLine (+ opcjonalnie Production)
     * i przebudowuje read model przez emisję eventu.
     *
     * @param array<int, array{
     *     slug?: string,
     *     status?: string,
     *     isCompleted?: bool,
     *     completedAt?: ?\DateTimeInterface,
     *     dateStart?: ?\DateTimeInterface,
     *     dateEnd?: ?\DateTimeInterface,
     *     isGhost?: bool,
     * }> $productions
     */
    protected function makeAgreementLine(
        ?Customer $customer = null,
        ?\DateTimeInterface $productionStartDate = null,
        ?\DateTimeInterface $productionCompletionDate = null,
        bool $deleted = false,
        float $factor = 1.0,
        int $status = AgreementLine::STATUS_MANUFACTURING,
        ?\DateTimeInterface $confirmedDate = null,
        array $productions = [],
    ): AgreementLine {
        $customer ??= $this->factory->make(Customer::class);
        $product = $this->factory->make(Product::class);
        $agreement = $this->factory->make(Agreement::class, ['customer' => $customer]);

        $line = $this->factory->make(AgreementLine::class, [
            'agreement' => $agreement,
            'product' => $product,
            'deleted' => $deleted,
            'factor' => $factor,
            'status' => $status,
            'confirmedDate' => $confirmedDate ?? new \DateTime(),
        ]);
        $line->setProductionStartDate($productionStartDate);
        $line->setProductionCompletionDate($productionCompletionDate);

        foreach ($productions as $p) {
            $production = $this->factory->make(Production::class, [
                'agreementLine' => $line,
                'departmentSlug' => $p['slug'] ?? \App\Entity\Definitions\TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
                'status' => $p['status'] ?? \App\Entity\Definitions\TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
                'isCompleted' => $p['isCompleted'] ?? false,
                'dateStart' => $p['dateStart'] ?? new \DateTime(),
                'dateEnd' => $p['dateEnd'] ?? new \DateTime(),
            ]);
            $production->setCompletedAt($p['completedAt'] ?? null);
            $production->setIsGhost($p['isGhost'] ?? false);
            $line->addProduction($production);
        }

        $this->factory->flush();

        $this->rebuildReadModel($line->getId());

        return $line;
    }

    /**
     * Przebudowuje AgreementLineRM przez emisję eventu (handler robi to synchronicznie).
     */
    protected function rebuildReadModel(int $agreementLineId): void
    {
        /** @var EventBus $eventBus */
        $eventBus = $this->get(EventBus::class);
        $eventBus->dispatch(new AgreementLineWasUpdatedEvent($agreementLineId));
    }
}
