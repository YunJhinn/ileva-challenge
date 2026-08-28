<?php

declare(strict_types=1);

namespace App\Models;

/** Simple, immutable read model for a person row, optionally with its contacts loaded. */
final class Person
{
    /** @param Contact[]|null $contacts */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?array $contacts = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }

    /** @param Contact[] $contacts */
    public function withContacts(array $contacts): self
    {
        return new self($this->id, $this->name, $this->createdAt, $this->updatedAt, $contacts);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->contacts !== null) {
            $data['contacts'] = array_map(static fn (Contact $c) => $c->toArray(), $this->contacts);
        }

        return $data;
    }
}
