<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Property;
use App\Models\Unit;

class UnitController extends Controller {
    public function create(string $propertyId): void {
        $property = Property::findById((int)$propertyId);
        if (!$property) {
            $this->setFlash('error', 'Property not found.');
            $this->redirect('/properties');
        }

        $this->render('units.create', [
            'title'    => 'Add Unit to ' . $property['name'],
            'property' => $property
        ]);
    }

    public function store(): void {
        $data = $this->sanitize($_POST);

        if (empty($data['property_id']) || empty($data['unit_number']) || !isset($data['target_rent'])) {
            $this->setFlash('error', 'Unit number and monthly rent are required.');
            $this->redirect('/properties/' . ($data['property_id'] ?? ''));
        }

        if (isset($_POST['appliances']) && is_array($_POST['appliances'])) {
            $data['appliances'] = json_encode(array_values($_POST['appliances']));
        } else {
            $data['appliances'] = null;
        }

        Unit::create($data);
        $this->setFlash('success', 'Unit ' . htmlspecialchars($data['unit_number']) . ' added successfully.');
        $this->redirect('/properties/' . $data['property_id']);
    }

    public function edit(string $id): void {
        $unit = Unit::findById((int)$id);
        if (!$unit) {
            $this->setFlash('error', 'Unit not found.');
            $this->redirect('/properties');
        }

        $property = Property::findById((int)$unit['property_id']);

        $this->render('units.edit', [
            'title'    => 'Edit Unit ' . $unit['unit_number'],
            'unit'     => $unit,
            'property' => $property
        ]);
    }

    public function update(string $id): void {
        $unit = Unit::findById((int)$id);
        if (!$unit) {
            $this->setFlash('error', 'Unit not found.');
            $this->redirect('/properties');
        }

        $data = $this->sanitize($_POST);

        if (empty($data['unit_number']) || !isset($data['target_rent'])) {
            $this->setFlash('error', 'Unit number and monthly rent are required.');
            $this->redirect('/units/' . $id . '/edit');
        }

        if (isset($_POST['appliances']) && is_array($_POST['appliances'])) {
            $data['appliances'] = json_encode(array_values($_POST['appliances']));
        } else {
            $data['appliances'] = null;
        }

        // Existing photos array (respect new sorted order if provided via form)
        $existingPhotos = [];
        if (!empty($_POST['existing_photos']) && is_array($_POST['existing_photos'])) {
            $existingPhotos = array_values($_POST['existing_photos']);
        } elseif (!empty($unit['photos'])) {
            $decoded = json_decode($unit['photos'], true);
            if (is_array($decoded)) {
                $existingPhotos = $decoded;
            }
        }

        // Process deleted photos
        if (!empty($_POST['delete_photos']) && is_array($_POST['delete_photos'])) {
            $toDelete = $_POST['delete_photos'];
            $keptPhotos = [];
            foreach ($existingPhotos as $photo) {
                if (in_array($photo, $toDelete, true)) {
                    $filePath = dirname(__DIR__, 2) . '/public' . $photo;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                } else {
                    $keptPhotos[] = $photo;
                }
            }
            $existingPhotos = $keptPhotos;
        }

        // Upload any newly added photos
        $newPhotos = $this->handlePhotosUpload();
        if (!empty($newPhotos)) {
            $existingPhotos = array_merge($existingPhotos, $newPhotos);
        }

        $data['photos'] = !empty($existingPhotos) ? json_encode(array_values($existingPhotos)) : null;

        Unit::update((int)$id, $data);
        $this->setFlash('success', 'Unit ' . htmlspecialchars($data['unit_number']) . ' updated successfully.');
        $this->redirect('/units/' . $id . '/edit');
    }

    public function deletePhoto(string $id): void {
        $unit = Unit::findById((int)$id);
        if (!$unit) {
            $this->json(['success' => false, 'message' => 'Unit not found.'], 404);
            return;
        }

        $photo = $_POST['photo'] ?? '';
        if (empty($photo)) {
            $this->json(['success' => false, 'message' => 'Photo path is required.'], 400);
            return;
        }

        $photos = [];
        if (!empty($unit['photos'])) {
            $decoded = json_decode($unit['photos'], true);
            if (is_array($decoded)) {
                $photos = $decoded;
            }
        }

        $newPhotos = [];
        $found = false;
        foreach ($photos as $p) {
            if ($p === $photo) {
                $found = true;
                $filePath = dirname(__DIR__, 2) . '/public' . $photo;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            } else {
                $newPhotos[] = $p;
            }
        }

        if ($found) {
            $unit['photos'] = !empty($newPhotos) ? json_encode(array_values($newPhotos)) : null;
            Unit::update((int)$id, $unit);
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false, 'message' => 'Photo not found on unit.'], 404);
        }
    }

    public function delete(string $id): void {
        $unit = Unit::findById((int)$id);
        if ($unit) {
            // Delete all associated unit photos from disk
            if (!empty($unit['photos'])) {
                $photos = json_decode($unit['photos'], true);
                if (is_array($photos)) {
                    foreach ($photos as $photo) {
                        $filePath = dirname(__DIR__, 2) . '/public' . $photo;
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }
            }

            Unit::delete((int)$id);
            $this->setFlash('success', 'Unit deleted successfully.');
            $this->redirect('/properties/' . $unit['property_id']);
        }
        $this->redirect('/properties');
    }

    /**
     * Handle multiple photos upload from $_FILES['unit_photos'].
     * Returns an array of web-relative paths.
     */
    private function handlePhotosUpload(): array {
        if (empty($_FILES['unit_photos']) || !is_array($_FILES['unit_photos']['name'])) {
            return [];
        }

        $uploaded = [];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $maxSize = 5 * 1024 * 1024; // 5 MB per photo
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/units';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $names = $_FILES['unit_photos']['name'];
        $types = $_FILES['unit_photos']['type'];
        $tmpNames = $_FILES['unit_photos']['tmp_name'];
        $errors = $_FILES['unit_photos']['error'];
        $sizes = $_FILES['unit_photos']['size'];

        foreach ($names as $idx => $name) {
            if (empty($name) || $errors[$idx] !== UPLOAD_ERR_OK) {
                continue;
            }
            if (!in_array($types[$idx], $allowedTypes, true)) {
                continue;
            }
            if ($sizes[$idx] > $maxSize) {
                continue;
            }

            $ext = $extMap[$types[$idx]] ?? 'jpg';
            $filename = uniqid('unit_', true) . '.' . $ext;
            $destination = $uploadDir . '/' . $filename;

            if (move_uploaded_file($tmpNames[$idx], $destination)) {
                $uploaded[] = '/uploads/units/' . $filename;
            }
        }

        return $uploaded;
    }
}
