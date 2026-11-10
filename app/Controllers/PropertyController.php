<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;

class PropertyController extends Controller {
    public function index(): void {
        $properties = Property::all();
        $this->render('properties.index', [
            'title'      => 'Properties',
            'properties' => $properties
        ]);
    }

    public function create(): void {
        $managers = User::all();
        $this->render('properties.create', [
            'title'    => 'Add New Property',
            'managers' => $managers
        ]);
    }

    public function store(): void {
        $data = $this->sanitize($_POST);

        if (empty($data['name']) || empty($data['address_line1']) || empty($data['city']) || empty($data['state']) || empty($data['zip_code'])) {
            $this->setFlash('error', 'Please fill in all required property address fields.');
            $this->redirect('/properties/create');
        }

        // Handle photo upload
        $data['photo_path'] = $this->handlePhotoUpload();

        $propertyId = Property::create($data);
        $this->setFlash('success', 'Property "' . htmlspecialchars($data['name']) . '" created successfully.');
        $this->redirect('/properties/' . $propertyId);
    }

    public function view(string $id): void {
        $property = Property::findById((int)$id);
        if (!$property) {
            $this->setFlash('error', 'Property not found.');
            $this->redirect('/properties');
        }

        $units = Unit::allByProperty((int)$id);

        $this->render('properties.view', [
            'title'    => $property['name'],
            'property' => $property,
            'units'    => $units
        ]);
    }

    public function edit(string $id): void {
        $property = Property::findById((int)$id);
        if (!$property) {
            $this->setFlash('error', 'Property not found.');
            $this->redirect('/properties');
        }

        $managers = User::all();
        $this->render('properties.edit', [
            'title'    => 'Edit ' . $property['name'],
            'property' => $property,
            'managers' => $managers
        ]);
    }

    public function update(string $id): void {
        $property = Property::findById((int)$id);
        if (!$property) {
            $this->setFlash('error', 'Property not found.');
            $this->redirect('/properties');
        }

        $data = $this->sanitize($_POST);

        if (empty($data['name']) || empty($data['address_line1']) || empty($data['city']) || empty($data['state']) || empty($data['zip_code'])) {
            $this->setFlash('error', 'Please fill in all required property address fields.');
            $this->redirect('/properties/' . $id . '/edit');
        }

        // Handle photo upload — keep existing if no new file uploaded
        $newPhoto = $this->handlePhotoUpload();
        if ($newPhoto) {
            // Delete old photo if it exists
            if (!empty($property['photo_path'])) {
                $oldFile = dirname(__DIR__, 2) . '/public' . $property['photo_path'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            $data['photo_path'] = $newPhoto;
        } else {
            $data['photo_path'] = $property['photo_path'];
        }

        // Handle photo removal
        if (!empty($data['remove_photo']) && $data['remove_photo'] === '1') {
            if (!empty($property['photo_path'])) {
                $oldFile = dirname(__DIR__, 2) . '/public' . $property['photo_path'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            $data['photo_path'] = null;
        }

        Property::update((int)$id, $data);
        $this->setFlash('success', 'Property updated successfully.');
        $this->redirect('/properties/' . $id);
    }

    public function delete(string $id): void {
        // Delete photo file if exists
        $property = Property::findById((int)$id);
        if ($property && !empty($property['photo_path'])) {
            $file = dirname(__DIR__, 2) . '/public' . $property['photo_path'];
            if (file_exists($file)) {
                unlink($file);
            }
        }

        Property::delete((int)$id);
        $this->setFlash('success', 'Property deleted successfully.');
        $this->redirect('/properties');
    }

    /**
     * Handle property photo upload from $_FILES.
     * Returns the web-relative path (e.g. /uploads/properties/abc123.jpg) or null.
     */
    private function handlePhotoUpload(): ?string {
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }
        if ($file['size'] > $maxSize) {
            return null;
        }

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $ext = $extMap[$file['type']] ?? 'jpg';

        $filename = uniqid('prop_', true) . '.' . $ext;
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/properties';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/properties/' . $filename;
        }

        return null;
    }
}
