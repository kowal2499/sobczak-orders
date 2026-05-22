<?php

namespace App\Module\ActivityLog\ValueObject;

enum LogPriority: string
{
    case normal = 'normal';
    case high = 'high';
}
