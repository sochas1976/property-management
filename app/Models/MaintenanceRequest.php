<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class MaintenanceRequest {
    public static function all(?string $status = null): array {
        $db = Database::getConnection();
        $sql = "
            SELECT m.*, 
                   u.unit_number, 
                   p.name AS property_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
                   t.phone AS tenant_phone,
                   d.id AS document_id,
                   d.stored_filename,
                   d.original_name AS document_name
            FROM maintenance_requests m
            JOIN units u ON m.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            LEFT JOIN tenants t ON m.tenant_id = t.id
            LEFT JOIN documents d ON d.related_type = 'maintenance' AND d.related_id = m.id
        ";
        
        $params = [];
        if ($status !== null && $status !== 'all') {
            $sql .= " WHERE m.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY CASE m.priority 
                    WHEN 'emergency' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                  END ASC, m.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT m.*, 
                   u.unit_number, 
                   p.name AS property_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
                   t.phone AS tenant_phone,
                   t.email AS tenant_email,
                   d.id AS document_id,
                   d.original_name AS document_name,
                   d.file_path,
                   d.mime_type
            FROM maintenance_requests m
            JOIN units u ON m.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            LEFT JOIN tenants t ON m.tenant_id = t.id
            LEFT JOIN documents d ON d.related_type = 'maintenance' AND d.related_id = m.id
            WHERE m.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO maintenance_requests (unit_id, tenant_id, title, description, category, priority, status, assigned_to, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'new', ?, NOW())
        ");
        $stmt->execute([
            $data['unit_id'],
            $data['tenant_id'] ?: null,
            $data['title'],
            $data['description'],
            $data['category'] ?? 'other',
            $data['priority'] ?? 'medium',
            $data['assigned_to'] ?: null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function updateStatus(int $id, string $status, ?string $assignedTo = null, ?string $notes = null): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE maintenance_requests
            SET status = ?, 
                assigned_to = COALESCE(?, assigned_to),
                resolution_notes = COALESCE(?, resolution_notes)
            WHERE id = ?
        ");
        return $stmt->execute([$status, $assignedTo, $notes, $id]);
    }

    public static function getOpenCount(): int {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM maintenance_requests WHERE status IN ('new', 'assigned', 'in_progress')");
        return (int)$stmt->fetchColumn();
    }
}
