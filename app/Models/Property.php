<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Property {
    public static function all(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT p.*, u.full_name AS manager_name,
                   COUNT(un.id) AS total_units,
                   SUM(CASE WHEN un.status = 'occupied' THEN 1 ELSE 0 END) AS occupied_units,
                   SUM(CASE WHEN un.status = 'vacant' THEN 1 ELSE 0 END) AS vacant_units
            FROM properties p
            LEFT JOIN users u ON p.manager_id = u.id
            LEFT JOIN units un ON p.id = un.property_id
            GROUP BY p.id
            ORDER BY p.name ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, u.full_name AS manager_name
            FROM properties p
            LEFT JOIN users u ON p.manager_id = u.id
            WHERE p.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $prop = $stmt->fetch();
        return $prop ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO properties (manager_id, name, address_line1, address_line2, city, state, zip_code, property_type, description, photo_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['manager_id'] ?: null,
            $data['name'],
            $data['address_line1'],
            $data['address_line2'] ?: null,
            $data['city'],
            $data['state'],
            $data['zip_code'],
            $data['property_type'] ?? 'multi_family',
            $data['description'] ?: null,
            $data['photo_path'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE properties
            SET manager_id = ?, name = ?, address_line1 = ?, address_line2 = ?,
                city = ?, state = ?, zip_code = ?, property_type = ?, description = ?, photo_path = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['manager_id'] ?: null,
            $data['name'],
            $data['address_line1'],
            $data['address_line2'] ?: null,
            $data['city'],
            $data['state'],
            $data['zip_code'],
            $data['property_type'] ?? 'multi_family',
            $data['description'] ?: null,
            $data['photo_path'] ?? null,
            $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM properties WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
