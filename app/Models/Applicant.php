<?php
namespace App\Models;

use App\Core\Database;
use App\Core\AppDate;
use PDO;

class Applicant {
    public static function all(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT a.*, 
                   p.name AS target_property_name,
                   p.photo_path AS target_property_photo,
                   u.unit_number AS target_unit_number,
                   t.first_name AS converted_tenant_first_name,
                   t.last_name AS converted_tenant_last_name,
                   COALESCE(
                       (SELECT l.start_date FROM leases l WHERE l.id = a.lease_id LIMIT 1),
                       (SELECT MIN(l.start_date) FROM leases l WHERE l.tenant_id = a.tenant_id)
                   ) AS lease_start_date,
                   COALESCE(
                       (SELECT l.end_date FROM leases l WHERE l.id = a.lease_id LIMIT 1),
                       (SELECT MAX(l.end_date) FROM leases l WHERE l.tenant_id = a.tenant_id)
                   ) AS lease_end_date,
                   COALESCE(
                       a.lease_id,
                       (SELECT l.id FROM leases l WHERE l.tenant_id = a.tenant_id ORDER BY l.id DESC LIMIT 1)
                   ) AS lease_id,
                   (SELECT COUNT(*) FROM applicants ca WHERE ca.lease_id = a.lease_id AND a.lease_id IS NOT NULL) AS lease_total_applicants,
                   (SELECT SUM(CASE WHEN ca.lease_signed = 1 THEN 1 ELSE 0 END) FROM applicants ca WHERE ca.lease_id = a.lease_id AND a.lease_id IS NOT NULL) AS lease_signed_applicants
            FROM applicants a
            LEFT JOIN properties p ON a.target_property_id = p.id
            LEFT JOIN units u ON a.target_unit_id = u.id
            LEFT JOIN tenants t ON a.tenant_id = t.id
            ORDER BY a.created_at DESC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function getEligibleForUnit(int $unitId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, 
                   p.name AS target_property_name,
                   u.unit_number AS target_unit_number
            FROM applicants a
            LEFT JOIN properties p ON a.target_property_id = p.id
            LEFT JOIN units u ON a.target_unit_id = u.id
            WHERE a.status IN ('pending', 'approved')
              AND (a.target_unit_id = ? OR a.target_unit_id IS NULL)
            ORDER BY a.full_name ASC
        ");
        $stmt->execute([$unitId]);
        return $stmt->fetchAll();
    }

    public static function allPendingAndApproved(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT a.*, 
                   p.name AS target_property_name,
                   u.unit_number AS target_unit_number
            FROM applicants a
            LEFT JOIN properties p ON a.target_property_id = p.id
            LEFT JOIN units u ON a.target_unit_id = u.id
            WHERE a.status IN ('pending', 'approved')
            ORDER BY a.full_name ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, 
                   p.name AS target_property_name,
                   p.photo_path AS target_property_photo,
                   u.unit_number AS target_unit_number,
                   t.first_name AS converted_tenant_first_name,
                   t.last_name AS converted_tenant_last_name,
                   COALESCE(
                       (SELECT l.start_date FROM leases l WHERE l.id = a.lease_id LIMIT 1),
                       (SELECT MIN(l.start_date) FROM leases l WHERE l.tenant_id = a.tenant_id)
                   ) AS lease_start_date,
                   COALESCE(
                       (SELECT l.end_date FROM leases l WHERE l.id = a.lease_id LIMIT 1),
                       (SELECT MAX(l.end_date) FROM leases l WHERE l.tenant_id = a.tenant_id)
                   ) AS lease_end_date,
                   COALESCE(
                       a.lease_id,
                       (SELECT l.id FROM leases l WHERE l.tenant_id = a.tenant_id ORDER BY l.id DESC LIMIT 1)
                   ) AS lease_id,
                   (SELECT COUNT(*) FROM applicants ca WHERE ca.lease_id = a.lease_id AND a.lease_id IS NOT NULL) AS lease_total_applicants,
                   (SELECT SUM(CASE WHEN ca.lease_signed = 1 THEN 1 ELSE 0 END) FROM applicants ca WHERE ca.lease_id = a.lease_id AND a.lease_id IS NOT NULL) AS lease_signed_applicants
            FROM applicants a
            LEFT JOIN properties p ON a.target_property_id = p.id
            LEFT JOIN units u ON a.target_unit_id = u.id
            LEFT JOIN tenants t ON a.tenant_id = t.id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getByLeaseId(int $leaseId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*,
                   t.first_name AS converted_tenant_first_name,
                   t.last_name AS converted_tenant_last_name
            FROM applicants a
            LEFT JOIN tenants t ON a.tenant_id = t.id
            WHERE a.lease_id = ?
            ORDER BY a.id ASC
        ");
        $stmt->execute([$leaseId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO applicants (
                date_requested, occupants_adults, occupants_children,
                tenancy_type, term_start_date, term_end_date,
                full_name, first_name, middle_name, last_name, birthday, home_phone, cell_phone,
                present_address, present_address_duration, previous_address, previous_address_duration,
                driver_license, email,
                current_employer, current_occupation, current_supervisor, current_employment_duration,
                current_employer_address, current_employer_phone,
                previous_employer, previous_occupation, previous_supervisor, previous_employment_duration,
                previous_employer_address, previous_employer_phone,
                monthly_income,
                current_landlord_name, current_landlord_phone, previous_landlord_name, previous_landlord_phone,
                emergency_contact_1_name, emergency_contact_1_address, emergency_contact_1_phone, emergency_contact_1_relationship,
                emergency_contact_2_name, emergency_contact_2_address, emergency_contact_2_phone, emergency_contact_2_relationship,
                credit_check_authorized, application_date, applicant_signature,
                status, target_property_id, target_unit_id, notes, documents
            ) VALUES (
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?
            )
        ");
        $stmt->execute([
            $data['date_requested'] ?: null,
            $data['occupants_adults'] ?: null,
            $data['occupants_children'] ?: null,
            $data['tenancy_type'] ?? 'periodic',
            $data['term_start_date'] ?: null,
            $data['term_end_date'] ?: null,
            $data['full_name'],
            $data['first_name'] ?: null,
            $data['middle_name'] ?: null,
            $data['last_name'] ?: null,
            $data['birthday'] ?: null,
            $data['home_phone'] ?: null,
            $data['cell_phone'] ?: null,
            $data['present_address'] ?: null,
            $data['present_address_duration'] ?: null,
            $data['previous_address'] ?: null,
            $data['previous_address_duration'] ?: null,
            $data['driver_license'] ?: null,
            $data['email'] ?: null,
            $data['current_employer'] ?: null,
            $data['current_occupation'] ?: null,
            $data['current_supervisor'] ?: null,
            $data['current_employment_duration'] ?: null,
            $data['current_employer_address'] ?: null,
            $data['current_employer_phone'] ?: null,
            $data['previous_employer'] ?: null,
            $data['previous_occupation'] ?: null,
            $data['previous_supervisor'] ?: null,
            $data['previous_employment_duration'] ?: null,
            $data['previous_employer_address'] ?: null,
            $data['previous_employer_phone'] ?: null,
            $data['monthly_income'] !== '' && $data['monthly_income'] !== null ? $data['monthly_income'] : null,
            $data['current_landlord_name'] ?: null,
            $data['current_landlord_phone'] ?: null,
            $data['previous_landlord_name'] ?: null,
            $data['previous_landlord_phone'] ?: null,
            $data['emergency_contact_1_name'] ?: null,
            $data['emergency_contact_1_address'] ?: null,
            $data['emergency_contact_1_phone'] ?: null,
            $data['emergency_contact_1_relationship'] ?: null,
            $data['emergency_contact_2_name'] ?: null,
            $data['emergency_contact_2_address'] ?: null,
            $data['emergency_contact_2_phone'] ?: null,
            $data['emergency_contact_2_relationship'] ?: null,
            !empty($data['credit_check_authorized']) ? 1 : 0,
            $data['application_date'] ?: null,
            $data['applicant_signature'] ?: null,
            $data['status'] ?? 'pending',
            !empty($data['target_property_id']) ? (int)$data['target_property_id'] : null,
            !empty($data['target_unit_id']) ? (int)$data['target_unit_id'] : null,
            $data['notes'] ?: null,
            !empty($data['documents']) ? $data['documents'] : null
        ]);

        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE applicants SET
                date_requested = ?, occupants_adults = ?, occupants_children = ?,
                tenancy_type = ?, term_start_date = ?, term_end_date = ?,
                full_name = ?, first_name = ?, middle_name = ?, last_name = ?, birthday = ?, home_phone = ?, cell_phone = ?,
                present_address = ?, present_address_duration = ?, previous_address = ?, previous_address_duration = ?,
                driver_license = ?, email = ?,
                current_employer = ?, current_occupation = ?, current_supervisor = ?, current_employment_duration = ?,
                current_employer_address = ?, current_employer_phone = ?,
                previous_employer = ?, previous_occupation = ?, previous_supervisor = ?, previous_employment_duration = ?,
                previous_employer_address = ?, previous_employer_phone = ?,
                monthly_income = ?,
                current_landlord_name = ?, current_landlord_phone = ?, previous_landlord_name = ?, previous_landlord_phone = ?,
                emergency_contact_1_name = ?, emergency_contact_1_address = ?, emergency_contact_1_phone = ?, emergency_contact_1_relationship = ?,
                emergency_contact_2_name = ?, emergency_contact_2_address = ?, emergency_contact_2_phone = ?, emergency_contact_2_relationship = ?,
                credit_check_authorized = ?, application_date = ?, applicant_signature = ?,
                status = ?, target_property_id = ?, target_unit_id = ?, notes = ?, documents = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['date_requested'] ?: null,
            $data['occupants_adults'] ?: null,
            $data['occupants_children'] ?: null,
            $data['tenancy_type'] ?? 'periodic',
            $data['term_start_date'] ?: null,
            $data['term_end_date'] ?: null,
            $data['full_name'],
            $data['first_name'] ?: null,
            $data['middle_name'] ?: null,
            $data['last_name'] ?: null,
            $data['birthday'] ?: null,
            $data['home_phone'] ?: null,
            $data['cell_phone'] ?: null,
            $data['present_address'] ?: null,
            $data['present_address_duration'] ?: null,
            $data['previous_address'] ?: null,
            $data['previous_address_duration'] ?: null,
            $data['driver_license'] ?: null,
            $data['email'] ?: null,
            $data['current_employer'] ?: null,
            $data['current_occupation'] ?: null,
            $data['current_supervisor'] ?: null,
            $data['current_employment_duration'] ?: null,
            $data['current_employer_address'] ?: null,
            $data['current_employer_phone'] ?: null,
            $data['previous_employer'] ?: null,
            $data['previous_occupation'] ?: null,
            $data['previous_supervisor'] ?: null,
            $data['previous_employment_duration'] ?: null,
            $data['previous_employer_address'] ?: null,
            $data['previous_employer_phone'] ?: null,
            $data['monthly_income'] !== '' && $data['monthly_income'] !== null ? $data['monthly_income'] : null,
            $data['current_landlord_name'] ?: null,
            $data['current_landlord_phone'] ?: null,
            $data['previous_landlord_name'] ?: null,
            $data['previous_landlord_phone'] ?: null,
            $data['emergency_contact_1_name'] ?: null,
            $data['emergency_contact_1_address'] ?: null,
            $data['emergency_contact_1_phone'] ?: null,
            $data['emergency_contact_1_relationship'] ?: null,
            $data['emergency_contact_2_name'] ?: null,
            $data['emergency_contact_2_address'] ?: null,
            $data['emergency_contact_2_phone'] ?: null,
            $data['emergency_contact_2_relationship'] ?: null,
            !empty($data['credit_check_authorized']) ? 1 : 0,
            $data['application_date'] ?: null,
            $data['applicant_signature'] ?: null,
            $data['status'] ?? 'pending',
            !empty($data['target_property_id']) ? (int)$data['target_property_id'] : null,
            !empty($data['target_unit_id']) ? (int)$data['target_unit_id'] : null,
            $data['notes'] ?: null,
            !empty($data['documents']) ? $data['documents'] : null,
            $id
        ]);
    }

    public static function updateDocuments(int $id, ?string $documentsJson): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE applicants SET documents = ? WHERE id = ?");
        return $stmt->execute([$documentsJson, $id]);
    }

    public static function updateStatus(int $id, string $status, ?string $statusDate = null): bool {
        $db = Database::getConnection();
        if ($statusDate === null) {
            $statusDate = AppDate::todayString() . ' ' . date('H:i:s');
        }
        $stmt = $db->prepare("UPDATE applicants SET status = ?, status_date = ? WHERE id = ?");
        return $stmt->execute([$status, $statusDate, $id]);
    }

    public static function assignToDraftLease(int $id, int $leaseId, int $unitId, int $propertyId): bool {
        $db = Database::getConnection();
        $statusDate = AppDate::todayString() . ' ' . date('H:i:s');
        $stmt = $db->prepare("
            UPDATE applicants
            SET status = 'lease_created',
                status_date = ?,
                lease_id = ?,
                lease_signed = 0,
                lease_signed_at = NULL,
                target_unit_id = ?,
                target_property_id = ?
            WHERE id = ?
        ");
        return $stmt->execute([$statusDate, $leaseId, $unitId, $propertyId, $id]);
    }

    public static function removeDraftLeaseByLeaseId(int $leaseId): bool {
        $db = Database::getConnection();
        $statusDate = AppDate::todayString() . ' ' . date('H:i:s');
        $stmt = $db->prepare("
            UPDATE applicants
            SET status = 'approved',
                status_date = ?,
                lease_id = NULL,
                lease_signed = 0,
                lease_signed_at = NULL
            WHERE lease_id = ?
              AND status IN ('lease_created', 'lease_sent')
        ");
        return $stmt->execute([$statusDate, $leaseId]);
    }

    public static function markLeaseSentByLeaseId(int $leaseId): bool {
        $db = Database::getConnection();
        $statusDate = AppDate::todayString() . ' ' . date('H:i:s');
        $stmt = $db->prepare("
            UPDATE applicants
            SET status = 'lease_sent',
                status_date = ?
            WHERE lease_id = ?
              AND status IN ('approved', 'lease_created', 'lease_sent')
        ");
        return $stmt->execute([$statusDate, $leaseId]);
    }

    public static function markLeaseSigned(int $id): bool {
        $db = Database::getConnection();
        $signedAt = AppDate::todayString() . ' ' . date('H:i:s');
        $stmt = $db->prepare("
            UPDATE applicants
            SET lease_signed = 1,
                lease_signed_at = COALESCE(lease_signed_at, ?)
            WHERE id = ?
        ");
        return $stmt->execute([$signedAt, $id]);
    }

    public static function markAllLeaseSignedByLeaseId(int $leaseId): bool {
        $db = Database::getConnection();
        $signedAt = AppDate::todayString() . ' ' . date('H:i:s');
        $stmt = $db->prepare("
            UPDATE applicants
            SET lease_signed = 1,
                lease_signed_at = COALESCE(lease_signed_at, ?)
            WHERE lease_id = ?
        ");
        return $stmt->execute([$signedAt, $leaseId]);
    }

    public static function markConverted(int $id, int $tenantId, ?int $unitId = null, ?int $leaseId = null): bool {
        $db = Database::getConnection();
        $statusDate = AppDate::todayString() . ' ' . date('H:i:s');
        $stmt = $db->prepare("
            UPDATE applicants
            SET status = 'converted',
                status_date = ?,
                tenant_id = ?,
                lease_id = COALESCE(?, lease_id),
                lease_signed = 1,
                lease_signed_at = COALESCE(lease_signed_at, ?),
                target_unit_id = COALESCE(?, target_unit_id)
            WHERE id = ?
        ");
        return $stmt->execute([$statusDate, $tenantId, $leaseId, $statusDate, $unitId, $id]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM applicants WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
