<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Lease {
    public static function all(string $orderBy = 'l.end_date ASC'): array {
        $allowed = [
            'l.end_date ASC',
            'l.end_date DESC',
            'l.start_date ASC',
            'l.start_date DESC'
        ];
        if (!in_array($orderBy, $allowed, true)) {
            $orderBy = 'l.end_date ASC';
        }
        $db = Database::getConnection();
        $sql = "
            SELECT l.*, 
                   COALESCE(
                       CONCAT(t.first_name, ' ', t.last_name),
                       (SELECT GROUP_CONCAT(a.full_name SEPARATOR ' & ') FROM applicants a WHERE a.lease_id = l.id)
                   ) AS tenant_name,
                   COALESCE(
                       t.email,
                       (SELECT a.email FROM applicants a WHERE a.lease_id = l.id ORDER BY a.id ASC LIMIT 1)
                   ) AS tenant_email,
                   COALESCE(
                       t.phone,
                       (SELECT COALESCE(a.cell_phone, a.home_phone) FROM applicants a WHERE a.lease_id = l.id ORDER BY a.id ASC LIMIT 1)
                   ) AS tenant_phone,
                   u.unit_number,
                   p.name AS property_name,
                   (SELECT d.id FROM documents d WHERE d.related_type = 'lease' AND d.related_id = l.id ORDER BY d.id DESC LIMIT 1) AS document_id,
                   (SELECT d.original_name FROM documents d WHERE d.related_type = 'lease' AND d.related_id = l.id ORDER BY d.id DESC LIMIT 1) AS document_name,
                   (SELECT COUNT(*) FROM documents doc WHERE doc.related_type = 'lease' AND doc.related_id = l.id) AS document_count,
                   (SELECT a.status FROM applicants a WHERE a.lease_id = l.id ORDER BY a.id ASC LIMIT 1) AS applicant_status,
                   (SELECT COUNT(*) FROM applicants a WHERE a.lease_id = l.id) AS applicant_count,
                   (SELECT SUM(CASE WHEN a.lease_signed = 1 THEN 1 ELSE 0 END) FROM applicants a WHERE a.lease_id = l.id) AS signed_count
            FROM leases l
            LEFT JOIN tenants t ON l.tenant_id = t.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            ORDER BY {$orderBy}
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function getUpcomingExpirations(int $limit = 5): array {
        $db = Database::getConnection();
        $sql = "
            SELECT l.*, 
                   COALESCE(
                       CONCAT(t.first_name, ' ', t.last_name),
                       (SELECT GROUP_CONCAT(a.full_name SEPARATOR ' & ') FROM applicants a WHERE a.lease_id = l.id)
                   ) AS tenant_name,
                   COALESCE(
                       t.email,
                       (SELECT a.email FROM applicants a WHERE a.lease_id = l.id ORDER BY a.id ASC LIMIT 1)
                   ) AS tenant_email,
                   COALESCE(
                       t.phone,
                       (SELECT COALESCE(a.cell_phone, a.home_phone) FROM applicants a WHERE a.lease_id = l.id ORDER BY a.id ASC LIMIT 1)
                   ) AS tenant_phone,
                   u.unit_number,
                   p.name AS property_name,
                   d.id AS document_id,
                   d.original_name AS document_name
            FROM leases l
            LEFT JOIN tenants t ON l.tenant_id = t.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            LEFT JOIN documents d ON d.related_type = 'lease' AND d.related_id = l.id
            WHERE l.status IN ('active', 'expiring_soon')
            ORDER BY l.end_date ASC
            LIMIT " . (int)$limit . "
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT l.*, 
                   COALESCE(
                       CONCAT(t.first_name, ' ', t.last_name),
                       (SELECT GROUP_CONCAT(a.full_name SEPARATOR ' & ') FROM applicants a WHERE a.lease_id = l.id)
                   ) AS tenant_name,
                   COALESCE(
                       t.email,
                       (SELECT a.email FROM applicants a WHERE a.lease_id = l.id ORDER BY a.id ASC LIMIT 1)
                   ) AS tenant_email,
                   COALESCE(
                       t.phone,
                       (SELECT COALESCE(a.cell_phone, a.home_phone) FROM applicants a WHERE a.lease_id = l.id ORDER BY a.id ASC LIMIT 1)
                   ) AS tenant_phone,
                   u.unit_number,
                   u.property_id,
                   u.floor,
                   u.bedrooms,
                   u.bathrooms,
                   u.square_feet,
                   u.furnished,
                   u.pets,
                   u.smoking,
                   u.parking,
                   u.utilities,
                   u.appliances,
                   u.tenant_insurance,
                   u.notes AS unit_notes,
                   p.name AS property_name,
                   p.address_line1,
                   p.address_line2,
                   p.city, p.state, p.zip_code,
                   p.property_type,
                   d.id AS document_id,
                   d.original_name AS document_name
            FROM leases l
            LEFT JOIN tenants t ON l.tenant_id = t.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            LEFT JOIN documents d ON d.related_type = 'lease' AND d.related_id = l.id
            WHERE l.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $lease = $stmt->fetch();
        if (!$lease) {
            return null;
        }

        // Attach all linked applicant records for application data extraction
        $appStmt = $db->prepare("SELECT * FROM applicants WHERE lease_id = ? ORDER BY id ASC");
        $appStmt->execute([$id]);
        $applicants = $appStmt->fetchAll();

        if (empty($applicants) && !empty($lease['tenant_id'])) {
            $appStmt = $db->prepare("SELECT * FROM applicants WHERE tenant_id = ? ORDER BY id ASC");
            $appStmt->execute([(int)$lease['tenant_id']]);
            $applicants = $appStmt->fetchAll();
        }
        if (empty($applicants) && !empty($lease['unit_id'])) {
            $appStmt = $db->prepare("SELECT * FROM applicants WHERE target_unit_id = ? AND status IN ('approved', 'lease_created', 'lease_sent', 'converted') ORDER BY id ASC");
            $appStmt->execute([(int)$lease['unit_id']]);
            $applicants = $appStmt->fetchAll();
        }

        $lease['applicants'] = $applicants ?: [];
        return $lease;
    }

    public static function findActiveByUnit(int $unitId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT l.*, 
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
                   a.tenancy_type
            FROM leases l
            JOIN tenants t ON l.tenant_id = t.id
            LEFT JOIN applicants a ON a.tenant_id = l.tenant_id
            WHERE l.unit_id = ? AND l.status IN ('active', 'expiring_soon')
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
            !empty($data['tenant_id']) ? (int)$data['tenant_id'] : null,
            $data['start_date'],
            !empty($data['end_date']) ? $data['end_date'] : null,
            $data['rent_amount'],
            $data['deposit_held'] ?? 0.00,
            $data['payment_frequency'] ?? 'monthly',
            $data['status'] ?? 'active',
            $data['terms'] ?: null
        ]);

        $leaseId = (int)$db->lastInsertId();

        // If lease is active or expiring soon, automatically mark unit as occupied
        if (in_array($data['status'] ?? 'active', ['active', 'expiring_soon'], true)) {
            Unit::updateStatus((int)$data['unit_id'], 'occupied');
        }

        return $leaseId;
    }

    public static function activateWithTenant(int $leaseId, int $tenantId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE leases SET tenant_id = ?, status = 'active' WHERE id = ?");
        $success = $stmt->execute([$tenantId, $leaseId]);
        if ($success) {
            $lease = self::findById($leaseId);
            if ($lease && !empty($lease['unit_id'])) {
                Unit::updateStatus((int)$lease['unit_id'], 'occupied');
            }
        }
        return $success;
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
        } elseif ($success && in_array($status, ['active', 'expiring_soon'])) {
            $lease = self::findById($id);
            if ($lease) {
                Unit::updateStatus((int)$lease['unit_id'], 'occupied');
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
