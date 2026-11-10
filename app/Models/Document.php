<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Document {
    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO documents (related_type, related_id, original_name, stored_filename, file_path, mime_type, file_size, uploaded_by_user_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['related_type'],
            $data['related_id'],
            $data['original_name'],
            $data['stored_filename'],
            $data['file_path'],
            $data['mime_type'],
            $data['file_size'],
            $data['uploaded_by_user_id'] ?: null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM documents WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getByRelated(string $relatedType, int $relatedId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM documents WHERE related_type = ? AND related_id = ? ORDER BY id DESC");
        $stmt->execute([$relatedType, $relatedId]);
        return $stmt->fetchAll();
    }

    public static function delete(int $id): bool {
        $doc = self::findById($id);
        if ($doc && file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
