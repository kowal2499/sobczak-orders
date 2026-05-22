<?php

namespace App\Module\ActivityLog\Factory;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\ActivityLog\DTO\LogDataDTO;

final class AddActivityLogCommandFactory
{
    public static function createFromDTO(
        string $type,
        LogDataDTO $data,
        ?int $authorUserId = null,
    ): AddActivityLogCommand {
        return new AddActivityLogCommand(
            message: $data->message,
            type: $type,
            contextData: $data->getFieldsAsPlainArray(),
            level: null,
            authorUserId: $authorUserId,
            createdDate: $data->createdDate,
            priority: $data->priority,
            contentParams: $data->contentParams,
        );
    }
}
