<?php

namespace App\Module\WorkConfiguration\ValueObject;

enum ScheduleDayType: string
{
    case Working = 'working';
    case Holiday = 'holiday';
    case Other = 'other';
}
