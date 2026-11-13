<?php
// public/index.php - Front Controller

require_once dirname(__DIR__) . '/config/app.php';

// PSR-4 Autoloader for App\ namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . DIRECTORY_SEPARATOR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Router;
use App\Core\Auth;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PropertyController;
use App\Controllers\UnitController;
use App\Controllers\TenantController;
use App\Controllers\ApplicantController;
use App\Controllers\LeaseController;
use App\Controllers\MaintenanceController;
use App\Controllers\FinanceController;
use App\Controllers\DocumentController;

Auth::start();

// --- ROUTE DEFINITIONS ---

// Root Redirect
Router::get('/', function() {
    if (Auth::check()) {
        header('Location: ' . Router::url('/dashboard'));
    } else {
        header('Location: ' . Router::url('/login'));
    }
    exit;
});

// Authentication
Router::get('/login', [AuthController::class, 'loginForm']);
Router::post('/login', [AuthController::class, 'login']);
Router::get('/logout', [AuthController::class, 'logout']);

// Dashboard
Router::get('/dashboard', [DashboardController::class, 'index'], ['auth']);

// Properties
Router::get('/properties', [PropertyController::class, 'index'], ['auth']);
Router::get('/properties/create', [PropertyController::class, 'create'], ['auth']);
Router::post('/properties', [PropertyController::class, 'store'], ['auth']);
Router::get('/properties/{id}', [PropertyController::class, 'view'], ['auth']);
Router::get('/properties/{id}/edit', [PropertyController::class, 'edit'], ['auth']);
Router::post('/properties/{id}/update', [PropertyController::class, 'update'], ['auth']);
Router::post('/properties/{id}/delete', [PropertyController::class, 'delete'], ['auth']);
Router::get('/properties/{id}/units/create', [UnitController::class, 'create'], ['auth']);

// Units
Router::post('/units', [UnitController::class, 'store'], ['auth']);
Router::get('/units/{id}/edit', [UnitController::class, 'edit'], ['auth']);
Router::post('/units/{id}/update', [UnitController::class, 'update'], ['auth']);
Router::post('/units/{id}/delete-photo', [UnitController::class, 'deletePhoto'], ['auth']);
Router::post('/units/{id}/delete', [UnitController::class, 'delete'], ['auth']);

// Tenants
Router::get('/tenants', [TenantController::class, 'index'], ['auth']);
Router::get('/tenants/create', [TenantController::class, 'create'], ['auth']);
Router::post('/tenants', [TenantController::class, 'store'], ['auth']);
Router::get('/tenants/{id}', [TenantController::class, 'view'], ['auth']);
Router::get('/tenants/{id}/edit', [TenantController::class, 'edit'], ['auth']);
Router::post('/tenants/{id}/update', [TenantController::class, 'update'], ['auth']);
Router::post('/tenants/{id}/delete', [TenantController::class, 'delete'], ['auth']);

// Applicants & Applications
Router::get('/applicants', [ApplicantController::class, 'index'], ['auth']);
Router::get('/applicants/history', [ApplicantController::class, 'history'], ['auth']);
Router::get('/applicants/create', [ApplicantController::class, 'create'], ['auth']);
Router::post('/applicants/parse-pdf', [ApplicantController::class, 'parsePdf'], ['auth']);
Router::post('/applicants', [ApplicantController::class, 'store'], ['auth']);
Router::get('/applicants/{id}', [ApplicantController::class, 'view'], ['auth']);
Router::get('/applicants/{id}/edit', [ApplicantController::class, 'edit'], ['auth']);
Router::post('/applicants/{id}/update', [ApplicantController::class, 'update'], ['auth']);
Router::post('/applicants/{id}/upload-document', [ApplicantController::class, 'uploadDocument'], ['auth']);
Router::post('/applicants/{id}/delete-document', [ApplicantController::class, 'deleteDocument'], ['auth']);
Router::post('/applicants/{id}/status', [ApplicantController::class, 'updateStatus'], ['auth']);
Router::post('/applicants/{id}/create-lease', [ApplicantController::class, 'createLease'], ['auth']);
Router::post('/applicants/{id}/cancel-lease', [ApplicantController::class, 'cancelLease'], ['auth']);
Router::post('/applicants/{id}/send-lease', [ApplicantController::class, 'sendLease'], ['auth']);
Router::post('/applicants/{id}/recall-lease', [ApplicantController::class, 'recallLease'], ['auth']);
Router::post('/applicants/{id}/sign-lease', [ApplicantController::class, 'signLease'], ['auth']);
Router::post('/applicants/{id}/convert', [ApplicantController::class, 'convert'], ['auth']);
Router::post('/applicants/{id}/delete', [ApplicantController::class, 'delete'], ['auth']);

// Leases
Router::get('/leases', [LeaseController::class, 'index'], ['auth']);
Router::get('/leases/templates', [LeaseController::class, 'templates'], ['auth']);
Router::post('/leases/templates/upload', [LeaseController::class, 'uploadTemplate'], ['auth']);
Router::post('/leases/templates/{templateId}/save-mapping', [LeaseController::class, 'saveTemplateMapping'], ['auth']);
Router::post('/leases/templates/{templateId}/delete', [LeaseController::class, 'deleteTemplate'], ['auth']);
Router::get('/leases/create', [LeaseController::class, 'create'], ['auth']);
Router::post('/leases', [LeaseController::class, 'store'], ['auth']);
Router::get('/leases/{id}', [LeaseController::class, 'view'], ['auth']);
Router::post('/leases/{id}/status', [LeaseController::class, 'updateStatus'], ['auth']);
Router::post('/leases/{id}/upload-template', [LeaseController::class, 'uploadTemplate'], ['auth']);
Router::post('/leases/{id}/generate-document', [LeaseController::class, 'generateDocument'], ['auth']);
Router::post('/leases/{id}/delete-template/{templateId}', [LeaseController::class, 'deleteTemplate'], ['auth']);

// Maintenance & Work Orders
Router::get('/maintenance', [MaintenanceController::class, 'index'], ['auth']);
Router::get('/maintenance/create', [MaintenanceController::class, 'create'], ['auth']);
Router::post('/maintenance', [MaintenanceController::class, 'store'], ['auth']);
Router::get('/maintenance/{id}', [MaintenanceController::class, 'view'], ['auth']);
Router::post('/maintenance/{id}/status', [MaintenanceController::class, 'updateStatus'], ['auth']);

// Billing & Finances
Router::get('/finances', [FinanceController::class, 'index'], ['auth']);
Router::post('/finances/invoice', [FinanceController::class, 'createInvoice'], ['auth']);
Router::post('/finances/payment', [FinanceController::class, 'recordPayment'], ['auth']);

// Local Document Storage Vault
Router::get('/documents/{id}', [DocumentController::class, 'view'], ['auth']);
Router::get('/documents/{id}/download', [DocumentController::class, 'download'], ['auth']);
Router::post('/documents/{id}/delete', [DocumentController::class, 'delete'], ['auth']);

// Dispatch Incoming Request
Router::dispatch();
