<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Unit {
    public static function allByProperty(int $propertyId): array {
        self::syncOccupancyStatus();
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
            ORDER BY u.sort_order ASC, u.unit_number ASC
        ");
        $stmt->execute([$propertyId]);
        return $stmt->fetchAll();
    }

    public static function all(): array {
        self::syncOccupancyStatus();
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
            ORDER BY p.name ASC, u.sort_order ASC, u.unit_number ASC
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
            ORDER BY p.name ASC, u.sort_order ASC, u.unit_number ASC
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

    /**
     * Persist a new display order for the units of a property.
     * $orderedIds is the list of unit IDs in the desired order.
     */
    public static function reorder(int $propertyId, array $orderedIds): bool {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE units SET sort_order = ? WHERE id = ? AND property_id = ?");
            $position = 1;
            foreach ($orderedIds as $unitId) {
                $stmt->execute([$position++, (int)$unitId, $propertyId]);
            }
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function create(array $data): int {
        $db = Database::getConnection();

        // New units are appended to the end of the property's custom order
        $orderStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM units WHERE property_id = ?");
        $orderStmt->execute([$data['property_id']]);
        $nextOrder = (int)$orderStmt->fetchColumn();

        $stmt = $db->prepare("
            INSERT INTO units (sort_order, property_id, unit_number, floor, bedrooms, bathrooms, square_feet, target_rent, security_deposit, status, adults_only, furnished, pets, smoking, parking, available_date, tenant_insurance, utilities, notes, appliances, photos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nextOrder,
            $data['property_id'],
            $data['unit_number'],
            $data['floor'] ?? 1,
            $data['bedrooms'] ?? 1,
            $data['bathrooms'] ?? 1.0,
            $data['square_feet'] ?: null,
            $data['target_rent'] ?? 0.00,
            $data['security_deposit'] ?? 0.00,
            $data['status'] ?? 'vacant',
            $data['adults_only'] ?? 'yes',
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
            $data['adults_only'] ?? 'yes',
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

    public static function syncOccupancyStatus(?int $unitId = null): void {
        $db = Database::getConnection();
        if ($unitId !== null) {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM leases
                WHERE unit_id = ? AND status IN ('active', 'expiring_soon')
            ");
            $stmt->execute([$unitId]);
            $hasActiveLease = ((int)$stmt->fetchColumn()) > 0;

            if ($hasActiveLease) {
                self::updateStatus($unitId, 'occupied');
            } else {
                $uStmt = $db->prepare("SELECT status FROM units WHERE id = ?");
                $uStmt->execute([$unitId]);
                $currentStatus = $uStmt->fetchColumn();
                if ($currentStatus === 'occupied') {
                    self::updateStatus($unitId, 'vacant');
                }
            }
            return;
        }

        // Mark any unit with an active or expiring_soon lease as 'occupied'
        $db->exec("
            UPDATE units
            SET status = 'occupied'
            WHERE id IN (
                SELECT DISTINCT unit_id FROM leases
                WHERE unit_id IS NOT NULL AND status IN ('active', 'expiring_soon')
            )
            AND status != 'occupied'
        ");

        // Mark any unit currently 'occupied' without an active/expiring_soon lease as 'vacant'
        $db->exec("
            UPDATE units
            SET status = 'vacant'
            WHERE status = 'occupied'
            AND id NOT IN (
                SELECT DISTINCT unit_id FROM leases
                WHERE unit_id IS NOT NULL AND status IN ('active', 'expiring_soon')
            )
        ");
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM units WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
