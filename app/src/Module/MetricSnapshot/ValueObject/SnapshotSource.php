<?php

namespace App\Module\MetricSnapshot\ValueObject;

enum SnapshotSource: string
{
    case CRON = 'cron';
    case MANUAL = 'manual';
    case BACKFILL = 'backfill';
}
