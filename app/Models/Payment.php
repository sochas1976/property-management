<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Payment {
    public static function create(array $data): int {
        $db = Database::getConnection();
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO payments (invoice_id, amount_paid, payment_date, payment_method, reference_number, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $data['invoice_id'],
                $data['amount_paid'],
                $data['payment_date'],
                $data['payment_method'] ?? 'bank_transfer',
                $data['reference_number'] ?: null,
                $data['notes'] ?: null
            ]);
            $paymentId = (int)$db->lastInsertId();

            // Check if invoice is now fully paid
            $invStmt = $db->prepare("SELECT amount FROM invoices WHERE id = ?");
            $invStmt->execute([$data['invoice_id']]);
            $invoice = $invStmt->fetch();

            $sumStmt = $db->prepare("SELECT SUM(amount_paid) FROM payments WHERE invoice_id = ?");
            $sumStmt->execute([$data['invoice_id']]);
            $totalPaid = (float)$sumStmt->fetchColumn();

            $newStatus = ($totalPaid >= (float)$invoice['amount']) ? 'paid' : 'partially_paid';
            $updStmt = $db->prepare("UPDATE invoices SET status = ? WHERE id = ?");
            $updStmt->execute([$newStatus, $data['invoice_id']]);

            $db->commit();
            return $paymentId;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function recent(int $limit = 10): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT pm.*, 
                   i.invoice_number, 
                   p.name AS property_name, 
                   u.unit_number,
                   CONCAT(t.first_name, ' ', t.last_name) AS tenant_name
            FROM payments pm
            JOIN invoices i ON pm.invoice_id = i.id
            JOIN leases l ON i.lease_id = l.id
            JOIN units u ON l.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            JOIN tenants t ON l.tenant_id = t.id
            ORDER BY pm.payment_date DESC, pm.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
