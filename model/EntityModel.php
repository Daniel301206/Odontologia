<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

/** CRUD preparado para una entidad definida por una subclase. */
class EntityModel extends BaseModel
{
    protected string $table;
    protected string $primaryKey;
    protected array $allowedFields = [];

    public function all(): array
    {
        return $this->fetchAll("SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC");
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
    }

    public function create(array $data): int
    {
        $data = $this->filterFields($data);
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $statement = $this->db->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");
        $statement->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data = $this->filterFields($data);
        $assignments = implode(',', array_map(static fn($field) => "$field = ?", array_keys($data)));
        $statement = $this->db->prepare("UPDATE {$this->table} SET $assignments WHERE {$this->primaryKey} = ?");
        $statement->execute([...array_values($data), $id]);
    }

    public function delete(int $id): void
    {
        $statement = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $statement->execute([$id]);
    }

    private function filterFields(array $data): array
    {
        $filtered = array_intersect_key($data, array_flip($this->allowedFields));
        if (!$filtered) throw new InvalidArgumentException('No hay campos válidos para guardar.');
        return $filtered;
    }
}
