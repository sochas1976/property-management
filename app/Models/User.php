<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User {
    public static function findByEmail(string $email): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, email, full_name, role, phone, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO users (email, password, full_name, role, phone, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['full_name'],
            $data['role'] ?? 'manager',
            $data['phone'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function all(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT id, email, full_name, role, phone, created_at FROM users ORDER BY full_name ASC");
        return $stmt->fetchAll();
    }
}
