<?php

namespace App\Tests\End2End\Modules\Reports\Production;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;

class ProductionCompletedBetweenSearchTest extends BaseProductionReportsTestCase
{
    public function testShouldReturnOnlyLinesWithDefaultDepartmentProductionCompletedInRange(): void
    {
        // Given
        $ids = [
            'inRange' => $this->lineCompletedAt(new \DateTime('2026-05-15 10:00')),
            'firstSecond' => $this->lineCompletedAt(new \DateTime('2026-05-01 00:00:00')),
            'lastSecond' => $this->lineCompletedAt(new \DateTime('2026-05-31 23:59:59')),
            'before' => $this->lineCompletedAt(new \DateTime('2026-04-30 23:59:59')),
            'after' => $this->lineCompletedAt(new \DateTime('2026-06-01 00:00:00')),
            'notCompleted' => $this->lineCompletedAt(null),
            'otherDepartment' => $this->lineCompletedAt(
                new \DateTime('2026-05-15'),
                TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING
            ),
        ];

        // When
        $found = $this->search(['productionCompletedBetween' => [
            'start' => '2026-05-01',
            'end' => '2026-05-31',
            'departments' => [TaskTypes::TYPE_DEFAULT_SLUG_GLUING],
        ]]);

        // Then
        $this->assertContains($ids['inRange'], $found);
        $this->assertContains($ids['firstSecond'], $found);
        $this->assertContains($ids['lastSecond'], $found);
        $this->assertNotContains($ids['before'], $found);
        $this->assertNotContains($ids['after'], $found);
        $this->assertNotContains($ids['notCompleted'], $found);
        $this->assertNotContains($ids['otherDepartment'], $found);
    }

    public function testShouldMatchAnyOfTheGivenDepartments(): void
    {
        // Given
        $gluing = $this->lineCompletedAt(new \DateTime('2026-05-15'), TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
        $packing = $this->lineCompletedAt(new \DateTime('2026-05-15'), TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING);

        // When
        $found = $this->search(['productionCompletedBetween' => [
            'start' => '2026-05-01',
            'end' => '2026-05-31',
            'departments' => TaskTypes::getDefaultSlugs(),
        ]]);

        // Then
        $this->assertContains($gluing, $found);
        $this->assertContains($packing, $found);
    }

    public function testShouldIgnoreFilterWithInvalidRange(): void
    {
        // Given
        $line = $this->lineCompletedAt(new \DateTime('2020-01-15'));

        // When
        $found = $this->search(['productionCompletedBetween' => ['start' => 'nonsense', 'end' => '2026-05-31']]);

        // Then
        $this->assertContains($line, $found);
    }

    private function lineCompletedAt(
        ?\DateTimeInterface $completedAt,
        string $slug = TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
    ): int {
        return $this->makeAgreementLine(productions: [[
            'slug' => $slug,
            'isCompleted' => $completedAt !== null,
            'completedAt' => $completedAt,
        ]])->getId();
    }

    /**
     * @param array<string, mixed> $search
     * @return int[]
     */
    private function search(array $search): array
    {
        $this->getManager()->clear();

        return array_map(
            fn (AgreementLineRM $line) => $line->getAgreementLineId(),
            $this->get(AgreementLineRMRepository::class)->search(['search' => $search])->getResult()
        );
    }
}
