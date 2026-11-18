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

    public static function allActive(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT t.*, 
                   l.id AS active_lease_id, 
                   l.unit_id,
                   l.status AS lease_status,
                   l.start_date AS lease_start_date,
                   l.end_date AS lease_end_date,
                   l.rent_amount,
                   u.unit_number, 
                   p.id AS property_id,
                   p.name AS property_name
            FROM tenants t
            INNER JOIN leases l ON t.id = l.tenant_id AND l.status IN ('active', 'expiring_soon')
            INNER JOIN units u ON l.unit_id = u.id
            INNER JOIN properties p ON u.property_id = p.id
            ORDER BY t.last_name ASC, t.first_name ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function allHistorical(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT t.*, 
                   l.id AS lease_id, 
                   l.unit_id,
                   l.status AS lease_status,
                   l.start_date AS lease_start_date,
                   l.end_date AS lease_end_date,
                   l.rent_amount,
                   u.unit_number, 
                   p.id AS property_id,
                   p.name AS property_name
            FROM tenants t
            INNER JOIN leases l ON t.id = l.tenant_id AND l.status IN ('expired', 'terminated')
            INNER JOIN units u ON l.unit_id = u.id
            INNER JOIN properties p ON u.property_id = p.id
            WHERE t.id NOT IN (
                SELECT DISTINCT tenant_id 
                FROM leases 
                WHERE tenant_id IS NOT NULL AND status IN ('active', 'expiring_soon')
            )
            ORDER BY l.end_date DESC, t.last_name ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function getLeases(int $tenantId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT l.*, u.unit_number, p.name AS property_name, p.id AS property_id
            FROM leases l
            LEFT JOIN units u ON l.unit_id = u.id
            LEFT JOIN properties p ON u.property_id = p.id
            WHERE l.tenant_id = ?
            ORDER BY l.start_date DESC
        ");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
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
