<?php

namespace App\Module\Production\Service\ProductionDateStrategy;

class FastStrategy implements ProductionDateStrategyInterface
{
    public const NAME = 'fast';

    public function __construct(private readonly DateShifter $shifter)
    {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function calculate(\DateTimeInterface $confirmedDate): array
    {
        $deadline = \DateTimeImmutable::createFromInterface($confirmedDate)->setTime(0, 0);

        $dpt01End = $this->shifter->shiftByDays(
            $this->shifter->shiftByWeekday($deadline, 'czwartek', 'before'),
            6,
            'before'
        );
        $dpt01Start = $this->shifter->shiftByDays($dpt01End, 2, 'before');

        $dpt02End = $this->shifter->shiftByDays(
            $this->shifter->shiftByWeekday($deadline, 'czwartek', 'before'),
            2,
            'before'
        );
        $dpt02Start = $dpt01End;

        $dpt06End = $this->shifter->shiftByDays(
            $this->shifter->shiftByWeekday($deadline, 'czwartek', 'before'),
            2,
            'before'
        );
        $dpt06Start = $dpt01End;

        $dpt03End = $this->shifter->shiftByWeekday($deadline, 'czwartek', 'before');
        $dpt03Start = $dpt06End;

        $dpt04End = $this->shifter->shiftByWeekday($deadline, 'czwartek', 'before');
        $dpt04Start = $dpt02End;

        $dpt05End = $this->shifter->shiftByWeekday($deadline, 'czwartek', 'before');
        $dpt05Start = $dpt02End;

        return [
            'dpt01' => ['dateStart' => $dpt01Start, 'dateEnd' => $dpt01End],
            'dpt02' => ['dateStart' => $dpt02Start, 'dateEnd' => $dpt02End],
            'dpt03' => ['dateStart' => $dpt03Start, 'dateEnd' => $dpt03End],
            'dpt04' => ['dateStart' => $dpt04Start, 'dateEnd' => $dpt04End],
            'dpt05' => ['dateStart' => $dpt05Start, 'dateEnd' => $dpt05End],
            'dpt06' => ['dateStart' => $dpt06Start, 'dateEnd' => $dpt06End],
        ];
    }

    public function getDefinition(): array
    {
        return [
            'name' => 'production.productionScheduler.strategyFast.name',
            'description' => 'production.productionScheduler.strategyFast.description',
            'default' => false,
            'deleted' => false,
            'strategy' => [
                'dpt01.dateEnd' => ['dependentOn' => 'deadlineDate', 'steps' => [
                    ['method' => 'shiftByWeekday', 'params' => ['day' => 'czwartek', 'direction' => 'before']],
                    ['method' => 'shiftByDays',    'params' => ['count' => 6,         'direction' => 'before']],
                ]],
                'dpt01.dateStart' => ['dependentOn' => 'dpt01.dateEnd', 'steps' => [
                    ['method' => 'shiftByDays', 'params' => ['count' => 2, 'direction' => 'before']],
                ]],
                'dpt02.dateEnd' => ['dependentOn' => 'deadlineDate', 'steps' => [
                    ['method' => 'shiftByWeekday', 'params' => ['day' => 'czwartek', 'direction' => 'before']],
                    ['method' => 'shiftByDays',    'params' => ['count' => 2,         'direction' => 'before']],
                ]],
                'dpt02.dateStart' => ['dependentOn' => 'dpt01.dateEnd', 'steps' => []],
                'dpt06.dateEnd' => ['dependentOn' => 'deadlineDate', 'steps' => [
                    ['method' => 'shiftByWeekday', 'params' => ['day' => 'czwartek', 'direction' => 'before']],
                    ['method' => 'shiftByDays',    'params' => ['count' => 2,         'direction' => 'before']],
                ]],
                'dpt06.dateStart' => ['dependentOn' => 'dpt01.dateEnd', 'steps' => []],
                'dpt03.dateEnd' => ['dependentOn' => 'deadlineDate', 'steps' => [
                    ['method' => 'shiftByWeekday', 'params' => ['day' => 'czwartek', 'direction' => 'before']],
                ]],
                'dpt03.dateStart' => ['dependentOn' => 'dpt06.dateEnd', 'steps' => []],
                'dpt04.dateEnd' => ['dependentOn' => 'deadlineDate', 'steps' => [
                    ['method' => 'shiftByWeekday', 'params' => ['day' => 'czwartek', 'direction' => 'before']],
                ]],
                'dpt04.dateStart' => ['dependentOn' => 'dpt02.dateEnd', 'steps' => []],
                'dpt05.dateEnd' => ['dependentOn' => 'deadlineDate', 'steps' => [
                    ['method' => 'shiftByWeekday', 'params' => ['day' => 'czwartek', 'direction' => 'before']],
                ]],
                'dpt05.dateStart' => ['dependentOn' => 'dpt02.dateEnd', 'steps' => []],
            ],
        ];
    }
}
