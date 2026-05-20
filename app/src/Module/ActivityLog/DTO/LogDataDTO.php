<?php

namespace App\Module\ActivityLog\DTO;

use App\Module\ActivityLog\ValueObject\LogPriority;
use Symfony\Component\Validator\Constraints as Assert;

final class LogDataDTO
{
    /**
     * @param FieldDataDTO[] $fields
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 65535)]
        public readonly string $message,
        #[Assert\Valid]
        public readonly array $fields = [],
        public readonly ?\DateTimeInterface $createdDate = null,
        public readonly LogPriority $priority = LogPriority::normal,
    ) {
    }

    /**
     * Returns fields as a plain associative array [name => value].
     * If the same name appears multiple times, the first value wins.
     *
     * @return array<string, string>
     */
    public function getFieldsAsPlainArray(): array
    {
        $result = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field->name, $result)) {
                $result[$field->name] = $field->value;
            }
        }
        return $result;
    }
}
