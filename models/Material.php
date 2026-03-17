<?php
declare(strict_types=1);

class Material {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM materiales");
        return $stmt->fetchAll();
    }
}
