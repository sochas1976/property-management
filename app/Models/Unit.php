<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Unit {
    public static function allByProperty(int $propertyId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, 
                   (SELECT l.id FROM leases l WHERE l.unit_id = u.id AND l.status IN ('active', 'expiring_soon') ORDER BY l.start_date DESC LIMIT 1) AS active_lease_id, 
                   (SELECT GROUP_CONCAT(CONCAT(t.first_name, ' ', t.last_name) SEPARATOR ', ')
                    FROM leases l
                    JOIN tenants t ON l.tenant_id = t.id
                    WHERE l.unit_id = u.id AND l.status IN ('active', 'expiring_soon')) AS current_tenant_name,
                   (SELECT l.tenant_id FROM leases l WHERE l.unit_id = u.id AND l.status IN ('active', 'expiring_soon') ORDER BY l.start_date DESC LIMIT 1) AS current_tenant_id
            FROM units u
            WHERE u.property_id = ?
            ORDER BY u.unit_number ASC
        ");
        $stmt->execute([$propertyId]);
        return $stmt->fetchAll();
    }

    public static function all(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT u.*, p.name AS property_name,
                   (SELECT l.id FROM leases l WHERE l.unit_id = u.id AND l.status IN ('active', 'expiring_soon') ORDER BY l.start_date DESC LIMIT 1) AS active_lease_id,
                   (SELECT GROUP_CONCAT(CONCAT(t.first_name, ' ', t.last_name) SEPARATOR ', ')
                    FROM leases l
                    JOIN tenants t ON l.tenant_id = t.id
                    WHERE l.unit_id = u.id AND l.status IN ('active', 'expiring_soon')) AS current_tenant_name,
                   (SELECT l.tenant_id FROM leases l WHERE l.unit_id = u.id AND l.status IN ('active', 'expiring_soon') ORDER BY l.start_date DESC LIMIT 1) AS current_tenant_id
            FROM units u
            JOIN properties p ON u.property_id = p.id
            ORDER BY p.name ASC, u.unit_number ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function getVacant(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT u.*, p.name AS property_name
            FROM units u
            JOIN properties p ON u.property_id = p.id
            WHERE u.status = 'vacant'
            ORDER BY p.name ASC, u.unit_number ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, p.name AS property_name
            FROM units u
            JOIN properties p ON u.property_id = p.id
            WHERE u.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $unit = $stmt->fetch();
        return $unit ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO units (property_id, unit_number, floor, bedrooms, bathrooms, square_feet, target_rent, security_deposit, status, adults_only, furnished, pets, smoking, parking, available_date, tenant_insurance, utilities, notes, appliances, photos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['property_id'],
            $data['unit_number'],
            $data['floor'] ?? 1,
            $data['bedrooms'] ?? 1,
            $data['bathrooms'] ?? 1.0,
            $data['square_feet'] ?: null,
            $data['target_rent'] ?? 0.00,
            $data['security_deposit'] ?? 0.00,
            $data['status'] ?? 'vacant',
            $data['adults_only'] ?? 'no',
            $data['furnished'] ?? 'no',
            $data['pets'] ?? 'not_allowed',
            $data['smoking'] ?? 'not_allowed',
            $data['parking'] ?? 'street',
            !empty($data['available_date']) ? $data['available_date'] : null,
            $data['tenant_insurance'] ?? 'not_required',
            $data['utilities'] ?: null,
            $data['notes'] ?: null,
            $data['appliances'] ?: null,
            $data['photos'] ?: null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE units
            SET unit_number = ?, floor = ?, bedrooms = ?, bathrooms = ?,
                square_feet = ?, target_rent = ?, security_deposit = ?, status = ?,
                adults_only = ?, furnished = ?, pets = ?, smoking = ?, parking = ?, available_date = ?,
                tenant_insurance = ?, utilities = ?,
                notes = ?, appliances = ?, photos = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['unit_number'],
            $data['floor'] ?? 1,
            $data['bedrooms'] ?? 1,
            $data['bathrooms'] ?? 1.0,
            $data['square_feet'] ?: null,
            $data['target_rent'] ?? 0.00,
            $data['security_deposit'] ?? 0.00,
            $data['status'] ?? 'vacant',
            $data['adults_only'] ?? 'no',
            $data['furnished'] ?? 'no',
            $data['pets'] ?? 'not_allowed',
            $data['smoking'] ?? 'not_allowed',
            $data['parking'] ?? 'street',
            !empty($data['available_date']) ? $data['available_date'] : null,
            $data['tenant_insurance'] ?? 'not_required',
            $data['utilities'] ?: null,
            $data['notes'] ?: null,
            $data['appliances'] ?: null,
            $data['photos'] ?: null,
            $id
        ]);
    }

    public static function updateStatus(int $id, string $status): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE units SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM units WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
