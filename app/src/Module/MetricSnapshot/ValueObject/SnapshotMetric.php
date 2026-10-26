<?php

namespace App\Module\MetricSnapshot\ValueObject;

enum SnapshotMetric: string
{
    case ORDERS_PENDING = 'orders_pending';
    case ORDERS_FINISHED = 'orders_finished';
    case DEPARTMENTS_BONUS = 'departments_bonus';
    case ON_TIME_IN = 'on_time_in';
    case ON_TIME_LATE = 'on_time_late';
    case CAPACITY = 'capacity';
    case WEEKLY_CAPACITY = 'weekly_capacity';
    case WEEKLY_CAPACITY_LIMIT = 'weekly_capacity_limit';
    case ATTENTION_LISTS = 'attention_lists';

    /**
     * Przepływowe są narastające od 1. dnia miesiąca (przyrost = różnica dni). Stanowe opisują
     * stan na dzień i nie zerują się na początku miesiąca; dla minionego dnia wyszłyby z
     * dzisiejszego stanu, więc uzupełnianie wstecz ich nie liczy.
     */
    public function isFlow(): bool
    {
        return match ($this) {
            self::ORDERS_FINISHED, self::DEPARTMENTS_BONUS, self::ON_TIME_IN, self::ON_TIME_LATE => true,
            default => false,
        };
    }

    /**
     * Mierniki liczone firmowo - zapisywane bez podziału na klienta i pokazywane każdemu
     * bez filtra ROLE_CUSTOMER.
     */
    public function isCompanyWide(): bool
    {
        return match ($this) {
            self::CAPACITY, self::WEEKLY_CAPACITY, self::WEEKLY_CAPACITY_LIMIT => true,
            default => false,
        };
    }
}
