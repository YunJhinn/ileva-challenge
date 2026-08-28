<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Contact;
use App\Support\NotFoundException;
use PDO;

final class ContactRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return Contact[] */
    public function forPerson(int $personId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM contacts WHERE person_id = :person_id ORDER BY id ASC');
        $stmt->execute(['person_id' => $personId]);

        return array_map(
            static fn (array $row) => Contact::fromRow($row),
            $stmt->fetchAll()
        );
    }

    public function find(int $id): ?Contact
    {
        $stmt = $this->db->prepare('SELECT * FROM contacts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Contact::fromRow($row) : null;
    }

    public function findOrFail(int $id): Contact
    {
        return $this->find($id) ?? throw new NotFoundException("Contact {$id} not found");
    }

    public function create(int $personId, string $type, string $value): Contact
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contacts (person_id, type, value) VALUES (:person_id, :type, :value)'
        );
        $stmt->execute(['person_id' => $personId, 'type' => $type, 'value' => $value]);

        return $this->findOrFail((int) $this->db->lastInsertId());
    }

    public function update(int $id, string $type, string $value): Contact
    {
        $this->findOrFail($id);

        $stmt = $this->db->prepare('UPDATE contacts SET type = :type, value = :value WHERE id = :id');
        $stmt->execute(['type' => $type, 'value' => $value, 'id' => $id]);

        return $this->findOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id);

        $stmt = $this->db->prepare('DELETE FROM contacts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
