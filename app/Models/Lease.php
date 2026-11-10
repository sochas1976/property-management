<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Lease {
    public static function all(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT l.*, 
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
                   t.email AS tenant_email,
                   t.phone AS tenant_phone,
                   u.unit_number,
                   p.name AS property_name,
                   d.id AS document_id,
                   d.original_name AS document_name
            FROM leases l
            JOIN tenants t ON l.tenant_id = t.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            LEFT JOIN documents d ON d.related_type = 'lease' AND d.related_id = l.id
            ORDER BY l.start_date DESC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT l.*, 
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
                   t.email AS tenant_email,
                   t.phone AS tenant_phone,
                   u.unit_number,
                   u.property_id,
                   p.name AS property_name,
                   p.address_line1,
                   p.city, p.state, p.zip_code,
                   d.id AS document_id,
                   d.original_name AS document_name
            FROM leases l
            JOIN tenants t ON l.tenant_id = t.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            LEFT JOIN documents d ON d.related_type = 'lease' AND d.related_id = l.id
            WHERE l.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $lease = $stmt->fetch();
        return $lease ?: null;
    }

    public static function findActiveByUnit(int $unitId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT l.*, 
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name
            FROM leases l
            JOIN tenants t ON l.tenant_id = t.id
            WHERE l.unit_id = ? AND l.status = 'active'
            ORDER BY l.start_date DESC
            LIMIT 1
        ");
        $stmt->execute([$unitId]);
        $lease = $stmt->fetch();
        return $lease ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO leases (unit_id, tenant_id, start_date, end_date, rent_amount, deposit_held, payment_frequency, status, terms)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['unit_id'],
            $data['tenant_id'],
            $data['start_date'],
            $data['end_date'],
            $data['rent_amount'],
            $data['deposit_held'] ?? 0.00,
            $data['payment_frequency'] ?? 'monthly',
            $data['status'] ?? 'active',
            $data['terms'] ?: null
        ]);

        $leaseId = (int)$db->lastInsertId();

        // If lease is active, automatically mark unit as occupied
        if (($data['status'] ?? 'active') === 'active') {
            Unit::updateStatus((int)$data['unit_id'], 'occupied');
        }

        return $leaseId;
    }

    public static function updateStatus(int $id, string $status): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE leases SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $id]);

        if ($success && in_array($status, ['terminated', 'expired'])) {
            $lease = self::findById($id);
            if ($lease) {
                Unit::updateStatus((int)$lease['unit_id'], 'vacant');
            }
        }

        return $success;
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $lease = self::findById($id);
        if ($lease) {
            Unit::updateStatus((int)$lease['unit_id'], 'vacant');
        }
        $stmt = $db->prepare("DELETE FROM leases WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
