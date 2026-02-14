<?php

namespace App\Module\AgreementLine\Entity;

class AttachmentRM
{
    private ?int $id;
    private ?string $name;
    private ?string $originalName;
    private ?string $extension;
    private ?string $path;
    private ?string $thumbnail;

    public function __construct(
        ?int $id,
        ?string $name = null,
        ?string $originalName = null,
        ?string $extension = null,
        ?string $path = null,
        ?string $thumbnail = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->originalName = $originalName;
        $this->extension = $extension;
        $this->path = $path;
        $this->thumbnail = $thumbnail;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'originalName' => $this->originalName,
            'extension' => $this->extension,
            'path' => $this->path,
            'thumbnail' => $this->thumbnail,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? null,
            originalName: $data['originalName'] ?? null,
            extension: $data['extension'] ?? null,
            path: $data['path'] ?? null,
            thumbnail: $data['thumbnail'] ?? null,
        );
    }
}