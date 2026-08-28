<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Person;
use App\Support\NotFoundException;
use PDO;

final class PersonRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return Person[] */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM people ORDER BY id ASC');

        return array_map(
            static fn (array $row) => Person::fromRow($row),
            $stmt->fetchAll()
        );
    }

    public function find(int $id): ?Person
    {
        $stmt = $this->db->prepare('SELECT * FROM people WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Person::fromRow($row) : null;
    }

    public function findOrFail(int $id): Person
    {
        return $this->find($id) ?? throw new NotFoundException("Person {$id} not found");
    }

    public function create(string $name): Person
    {
        $stmt = $this->db->prepare('INSERT INTO people (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);

        return $this->findOrFail((int) $this->db->lastInsertId());
    }

    public function update(int $id, string $name): Person
    {
        $this->findOrFail($id);

        $stmt = $this->db->prepare('UPDATE people SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);

        return $this->findOrFail($id);
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id);

        $stmt = $this->db->prepare('DELETE FROM people WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
