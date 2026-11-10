<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Tenant;

class TenantController extends Controller {
    public function index(): void {
        $tenants = Tenant::all();
        $this->render('tenants.index', [
            'title'   => 'Tenants Directory',
            'tenants' => $tenants
        ]);
    }

    public function create(): void {
        $this->render('tenants.create', [
            'title' => 'Register New Tenant'
        ]);
    }

    public function store(): void {
        $data = $this->sanitize($_POST);

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['phone'])) {
            $this->setFlash('error', 'First name, last name, email, and phone number are required.');
            $this->redirect('/tenants/create');
        }

        $tenantId = Tenant::create($data);
        $this->setFlash('success', 'Tenant ' . htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) . ' registered successfully.');
        $this->redirect('/tenants');
    }

    public function view(string $id): void {
        $tenant = Tenant::findById((int)$id);
        if (!$tenant) {
            $this->setFlash('error', 'Tenant not found.');
            $this->redirect('/tenants');
        }

        $this->render('tenants.view', [
            'title'  => $tenant['first_name'] . ' ' . $tenant['last_name'],
            'tenant' => $tenant
        ]);
    }

    public function edit(string $id): void {
        $tenant = Tenant::findById((int)$id);
        if (!$tenant) {
            $this->setFlash('error', 'Tenant not found.');
            $this->redirect('/tenants');
        }

        $this->render('tenants.edit', [
            'title'  => 'Edit Profile: ' . $tenant['first_name'] . ' ' . $tenant['last_name'],
            'tenant' => $tenant
        ]);
    }

    public function update(string $id): void {
        $tenant = Tenant::findById((int)$id);
        if (!$tenant) {
            $this->setFlash('error', 'Tenant not found.');
            $this->redirect('/tenants');
        }

        $data = $this->sanitize($_POST);

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['phone'])) {
            $this->setFlash('error', 'First name, last name, email, and phone number are required.');
            $this->redirect('/tenants/' . $id . '/edit');
        }

        Tenant::update((int)$id, $data);
        $this->setFlash('success', 'Tenant profile updated successfully.');
        $this->redirect('/tenants/' . $id);
    }

    public function delete(string $id): void {
        Tenant::delete((int)$id);
        $this->setFlash('success', 'Tenant profile removed.');
        $this->redirect('/tenants');
    }
}
