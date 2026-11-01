<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/** Base común de modelos, con acceso PDO y consultas preparadas. */
class BaseModel
{
    protected PDO $db;

    public function __construct(?PDO $connection = null)
    {
        $this->db = $connection ?? db();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    public function connection(): PDO { return $this->db; }
}
