<?php
// config/app.php - Application General Settings & Paths

define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('VIEW_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'views');

// Environment & Development Test Mode Gate
// Set APP_ENV to 'production' in production environments to completely disable Test Mode
$appEnv = getenv('APP_ENV') ?: 'development'; // 'development', 'testing', or 'production'
if (!defined('APP_ENV')) {
    define('APP_ENV', $appEnv);
}
if (!defined('TEST_MODE_AVAILABLE')) {
    define('TEST_MODE_AVAILABLE', in_array(APP_ENV, ['development', 'testing', 'local'], true));
}
// PHP 7.x backward compatibility polyfills
if (!function_exists('str_starts_with')) {
    function str_starts_with(?string $haystack, ?string $needle): bool {
        if ($haystack === null || $needle === null) return false;
        return strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(?string $haystack, ?string $needle): bool {
        if ($haystack === null || $needle === null) return false;
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(?string $haystack, ?string $needle): bool {
        if ($haystack === null || $needle === null) return false;
        $needleLen = strlen($needle);
        return $needleLen === 0 || (strlen($haystack) >= $needleLen && substr_compare($haystack, $needle, -$needleLen) === 0);
    }
}

return [
    'app_name'    => 'PropPulse PMS',
    'app_env'     => APP_ENV, // 'development' or 'production'
    'test_mode'   => TEST_MODE_AVAILABLE,
    'base_url'    => '', // Auto-detected by router if empty
    'storage'     => [
        'root'        => STORAGE_PATH,
        'documents'   => STORAGE_PATH . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'leases',
        'photos'      => STORAGE_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . 'maintenance',
        'max_size_mb' => 10,
        'allowed_mimes' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp'
        ]
    ]
];
