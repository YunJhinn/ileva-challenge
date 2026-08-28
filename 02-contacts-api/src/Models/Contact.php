<?php

declare(strict_types=1);

namespace App\Models;

/** Simple, immutable read model for a contact row. */
final class Contact
{
    public const TYPES = ['phone', 'email', 'whatsapp'];

    public function __construct(
        public readonly int $id,
        public readonly int $personId,
        public readonly string $type,
        public readonly string $value,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            personId: (int) $row['person_id'],
            type: (string) $row['type'],
            value: (string) $row['value'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->personId,
            'type' => $this->type,
            'value' => $this->value,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
