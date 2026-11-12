<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Tenant {
    public static function all(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT t.*, 
                   l.id AS active_lease_id, 
                   l.unit_id,
                   u.unit_number, 
                   p.id AS property_id,
                   p.name AS property_name
            FROM tenants t
            LEFT JOIN leases l ON t.id = l.tenant_id AND l.status IN ('active', 'expiring_soon')
            LEFT JOIN units u ON l.unit_id = u.id
            LEFT JOIN properties p ON u.property_id = p.id
            ORDER BY t.last_name ASC, t.first_name ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT t.*, 
                   l.id AS active_lease_id, 
                   l.unit_id,
                   u.unit_number, 
                   p.id AS property_id,
                   p.name AS property_name
            FROM tenants t
            LEFT JOIN leases l ON t.id = l.tenant_id AND l.status IN ('active', 'expiring_soon')
            LEFT JOIN units u ON l.unit_id = u.id
            LEFT JOIN properties p ON u.property_id = p.id
            WHERE t.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $tenant = $stmt->fetch();
        return $tenant ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO tenants (user_id, first_name, last_name, email, phone, emergency_contact, emergency_phone, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'] ?: null,
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['emergency_contact'] ?: null,
            $data['emergency_phone'] ?: null,
            $data['notes'] ?: null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE tenants
            SET first_name = ?, last_name = ?, email = ?, phone = ?,
                emergency_contact = ?, emergency_phone = ?, notes = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['emergency_contact'] ?: null,
            $data['emergency_phone'] ?: null,
            $data['notes'] ?: null,
            $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM tenants WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
