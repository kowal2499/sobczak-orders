<?php

namespace App\Module\Agreement\ActivityLog;

use App\Entity\AgreementLine;

enum AgreementActivityLogType: string
{
    case AGREEMENT_CREATED = 'agreement.created';
    case AGREEMENT_LINE_CREATED = 'agreement_line.created';
    case AGREEMENT_LINE_ARCHIVED = 'agreement_line.archived';
    case AGREEMENT_LINE_SENT_TO_WAREHOUSE = 'agreement_line.sent_to_warehouse';
    case AGREEMENT_LINE_RESTORED = 'agreement_line.restored';
    case AGREEMENT_LINE_DELETED = 'agreement_line.deleted';
    case AGREEMENT_LINE_PRODUCTION_STARTED = 'agreement_line.production_started';

    public static function forStatus(?int $status): ?self
    {
        return match ($status) {
            AgreementLine::STATUS_ARCHIVED  => self::AGREEMENT_LINE_ARCHIVED,
            AgreementLine::STATUS_WAREHOUSE => self::AGREEMENT_LINE_SENT_TO_WAREHOUSE,
            AgreementLine::STATUS_WAITING   => self::AGREEMENT_LINE_RESTORED,
            AgreementLine::STATUS_DELETED   => self::AGREEMENT_LINE_DELETED,
            default                         => null,
        };
    }
}
