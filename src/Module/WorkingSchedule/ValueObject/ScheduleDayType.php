<?php

namespace App\Module\WorkingSchedule\ValueObject;

enum ScheduleDayType: string
{
    case Working = 'working';
    case Holiday = 'holiday';
    case Other = 'other';
}
