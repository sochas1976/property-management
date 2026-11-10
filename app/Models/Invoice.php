<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Invoice {
    public static function all(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT i.*, 
                   l.rent_amount,
                   u.unit_number,
                   p.name AS property_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
                   COALESCE(SUM(pm.amount_paid), 0) AS total_paid
            FROM invoices i
            JOIN leases l ON i.lease_id = l.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            JOIN tenants t ON l.tenant_id = t.id
            LEFT JOIN payments pm ON i.id = pm.invoice_id
            GROUP BY i.id
            ORDER BY i.due_date DESC, i.id DESC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT i.*, 
                   l.rent_amount,
                   u.unit_number,
                   p.name AS property_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
                   t.email AS tenant_email,
                   COALESCE(SUM(pm.amount_paid), 0) AS total_paid
            FROM invoices i
            JOIN leases l ON i.lease_id = l.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            JOIN tenants t ON l.tenant_id = t.id
            LEFT JOIN payments pm ON i.id = pm.invoice_id
            WHERE i.id = ?
            GROUP BY i.id
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $invoiceNum = 'INV-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $db->prepare("
            INSERT INTO invoices (lease_id, invoice_number, description, amount, due_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $data['lease_id'],
            $invoiceNum,
            $data['description'] ?? 'Monthly Rent',
            $data['amount'],
            $data['due_date']
        ]);
        return (int)$db->lastInsertId();
    }

    public static function getPending(): array {
        $db = Database::getConnection();
        $sql = "
            SELECT i.*, 
                   u.unit_number,
                   p.name AS property_name,
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name
            FROM invoices i
            JOIN leases l ON i.lease_id = l.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            JOIN tenants t ON l.tenant_id = t.id
            WHERE i.status IN ('pending', 'partially_paid')
            ORDER BY i.due_date ASC
        ";
        return $db->query($sql)->fetchAll();
    }

    public static function getTotals(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT 
                SUM(amount) AS total_billed,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS total_collected,
                SUM(CASE WHEN status IN ('pending', 'partially_paid') THEN amount ELSE 0 END) AS total_pending
            FROM invoices
        ");
        $totals = $stmt->fetch();
        return [
            'total_billed'    => (float)($totals['total_billed'] ?? 0),
            'total_collected' => (float)($totals['total_collected'] ?? 0),
            'total_pending'   => (float)($totals['total_pending'] ?? 0),
        ];
    }
}
