<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    /** @var PDO|null */
    private static $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require ROOT_PATH . '/config/database.php';
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                // Return clean, informative error message for XAMPP users
                die("<div style='font-family:sans-serif;padding:30px;max-width:700px;margin:40px auto;border:1px solid #f87171;border-radius:8px;background:#fef2f2;color:#991b1b;'>"
                    . "<h2 style='margin-top:0;'>Database Connection Error</h2>"
                    . "<p>Could not connect to MySQL server at <code>{$config['host']}:{$config['port']}</code>.</p>"
                    . "<p><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>"
                    . "<hr style='border:0;border-top:1px solid #fca5a5;margin:15px 0;'>"
                    . "<p><strong>Troubleshooting for XAMPP:</strong></p>"
                    . "<ol style='padding-left:20px;'>"
                    . "<li>Ensure <strong>MySQL</strong> is started in your XAMPP Control Panel.</li>"
                    . "<li>Open <strong>phpMyAdmin</strong> (<a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a>) and import <code>database/schema.sql</code> and <code>database/seed.sql</code>.</li>"
                    . "<li>Check your credentials in <code>config/database.php</code>.</li>"
                    . "</ol>"
                    . "</div>");
            }
        }
        return self::$instance;
    }
}
