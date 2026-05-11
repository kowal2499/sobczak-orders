<?php

namespace App\Module\Production\Service;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Module\Production\Service\ProductionDateStrategy\ProductionDateStrategyResolver;
use App\Module\Production\ValueObject\DepartmentEnum;
use Doctrine\ORM\EntityManagerInterface;

class GhostProductionTaskService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductionDateStrategyResolver $resolver,
    ) {
    }

    /**
     * Creates ghost production tasks for an AgreementLine that has none yet.
     * No-op if any non-ghost production exists for the line, or if confirmedDate is null.
     */
    public function createForAgreementLine(AgreementLine $line): void
    {
        $confirmedDate = $line->getConfirmedDate();
        if ($confirmedDate === null) {
            return;
        }

        $productionDeptSlugs = array_map(
            fn(DepartmentEnum $d) => $d->value,
            DepartmentEnum::getProductionDepartments()
        );

        foreach ($line->getProductions() as $existing) {
            if (!in_array($existing->getDepartmentSlug(), $productionDeptSlugs, true)) {
                continue;
            }
            if (!$existing->isGhost()) {
                return;
            }
        }

        $strategy = $this->resolver->resolve($line);
        $schedule = $strategy->calculate($confirmedDate);

        $existingByDept = [];
        foreach ($line->getProductions() as $existing) {
            if (!in_array($existing->getDepartmentSlug(), $productionDeptSlugs, true)) {
                continue;
            }
            if ($existing->isGhost()) {
                $existingByDept[$existing->getDepartmentSlug()] = $existing;
            }
        }

        foreach (DepartmentEnum::getProductionDepartments() as $dept) {
            $dates = $schedule[$dept->value] ?? null;
            if ($dates === null) {
                continue;
            }

            if (isset($existingByDept[$dept->value])) {
                continue;
            }

            $production = new Production();
            $production
                ->setAgreementLine($line)
                ->setTitle($dept->getName())
                ->setDepartmentSlug($dept->value)
                ->setIsGhost(true)
                ->setStatus((string) TaskTypes::TYPE_DEFAULT_STATUS_AWAITS)
                ->setDateStart(\DateTime::createFromImmutable($dates['dateStart']))
                ->setDateEnd(\DateTime::createFromImmutable($dates['dateEnd']))
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());

            $line->addProduction($production);
            $this->em->persist($production);
        }
    }

    /**
     * Regenerates ghost task dates for an AgreementLine after confirmedDate changed.
     * Only acts if the line is still pending (all productions are ghosts).
     * If the line has any non-ghost production, this is a no-op.
     */
    public function regenerateForAgreementLine(AgreementLine $line): void
    {
        $confirmedDate = $line->getConfirmedDate();
        if ($confirmedDate === null) {
            return;
        }

        $productionDeptSlugs = array_map(
            fn(DepartmentEnum $d) => $d->value,
            DepartmentEnum::getProductionDepartments()
        );

        $ghostByDept = [];
        foreach ($line->getProductions() as $existing) {
            if (!in_array($existing->getDepartmentSlug(), $productionDeptSlugs, true)) {
                continue;
            }
            if (!$existing->isGhost()) {
                return;
            }
            $ghostByDept[$existing->getDepartmentSlug()] = $existing;
        }

        if (empty($ghostByDept)) {
            $this->createForAgreementLine($line);
            return;
        }

        $strategy = $this->resolver->resolve($line);
        $schedule = $strategy->calculate($confirmedDate);

        foreach (DepartmentEnum::getProductionDepartments() as $dept) {
            $dates = $schedule[$dept->value] ?? null;
            if ($dates === null) {
                continue;
            }

            $production = $ghostByDept[$dept->value] ?? null;
            if ($production === null) {
                $production = new Production();
                $production
                    ->setAgreementLine($line)
                    ->setTitle($dept->getName())
                    ->setDepartmentSlug($dept->value)
                    ->setIsGhost(true)
                    ->setStatus((string) TaskTypes::TYPE_DEFAULT_STATUS_AWAITS)
                    ->setCreatedAt(new \DateTime());
                $line->addProduction($production);
                $this->em->persist($production);
            }

            $production
                ->setDateStart(\DateTime::createFromImmutable($dates['dateStart']))
                ->setDateEnd(\DateTime::createFromImmutable($dates['dateEnd']))
                ->setUpdatedAt(new \DateTime());
        }
    }
}
