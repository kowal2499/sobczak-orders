<?php

namespace App\Module\Agreement\ActivityLog;

enum AgreementActivityLogType: string
{
    case AGREEMENT_CREATED = 'agreement.created';
    case AGREEMENT_LINE_CREATED = 'agreement_line.created';
}
