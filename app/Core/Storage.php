<?php
namespace App\Core;

use Exception;

class Storage {
    /** @var array */
    private static $allowedMimes = [
        'pdf'  => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc'  => 'application/msword',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    /** @var int */
    private static $maxSizeBytes = 10485760; // 10 MB

    public static function store(array $file, string $subfolder = 'documents'): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . $file['error']);
        }

        if ($file['size'] > self::$maxSizeBytes) {
            throw new Exception("File size exceeds 10MB limit.");
        }

        // Validate real MIME type using PHP fileinfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $matchedExt = null;
        foreach (self::$allowedMimes as $ext => $mime) {
            if ($realMime === $mime) {
                $matchedExt = $ext;
                break;
            }
        }

        if ($matchedExt === null) {
            throw new Exception("Invalid file type: {$realMime}. Only PDF, Word (.docx), and images (JPG, PNG, WebP) are allowed.");
        }

        // Target directory under protected storage
        $targetDir = STORAGE_PATH . DIRECTORY_SEPARATOR . $subfolder;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Generate collision-resistant unique filename
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $matchedExt;
        $destination = $targetDir . DIRECTORY_SEPARATOR . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to move uploaded file to permanent local storage.");
        }

        return [
            'original_name'   => basename($file['name']),
            'stored_filename' => $uniqueName,
            'file_path'       => $destination,
            'mime_type'       => $realMime,
            'file_size'       => $file['size']
        ];
    }

    public static function stream(string $filePath, string $originalName, string $mimeType, bool $download = false): void {
        if (!file_exists($filePath)) {
            http_response_code(404);
            die("Requested document file not found on disk.");
        }

        // Prevent header injection
        $safeName = str_replace(['"', "'", "\r", "\n"], '', $originalName);
        $disposition = $download ? 'attachment' : 'inline';

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('X-Content-Type-Options: nosniff');

        // Clear any previous output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        readfile($filePath);
        exit;
    }
}
