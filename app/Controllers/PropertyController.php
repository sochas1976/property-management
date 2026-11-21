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

        // Handle photo upload (supports multiple property_photos[] or single photo)
        $uploadedPhotos = $this->handlePhotosUpload();
        if (empty($uploadedPhotos)) {
            $singlePhoto = $this->handlePhotoUpload();
            if ($singlePhoto) {
                $uploadedPhotos = [$singlePhoto];
            }
        }
        $data['photos'] = !empty($uploadedPhotos) ? json_encode(array_values($uploadedPhotos)) : null;
        $data['photo_path'] = !empty($uploadedPhotos) ? $uploadedPhotos[0] : null;

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

        // Existing photos array (respect new sorted order if provided via form)
        $existingPhotos = [];
        if (isset($_POST['existing_photos']) && is_array($_POST['existing_photos'])) {
            $existingPhotos = array_values($_POST['existing_photos']);
        } elseif (!empty($property['photos'])) {
            $decoded = json_decode($property['photos'], true);
            if (is_array($decoded)) {
                $existingPhotos = $decoded;
            }
        } elseif (!empty($property['photo_path'])) {
            $existingPhotos = [$property['photo_path']];
        }

        // Process deleted photos if submitted via form
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

        // Upload any newly added photos (supports multiple property_photos[] or single photo)
        $newPhotos = $this->handlePhotosUpload();
        if (!empty($newPhotos)) {
            $existingPhotos = array_merge($existingPhotos, $newPhotos);
        } else {
            $singlePhoto = $this->handlePhotoUpload();
            if ($singlePhoto) {
                $existingPhotos[] = $singlePhoto;
            }
        }

        $existingPhotos = array_values(array_filter($existingPhotos));
        $data['photos'] = !empty($existingPhotos) ? json_encode($existingPhotos) : null;
        $data['photo_path'] = !empty($existingPhotos) ? $existingPhotos[0] : null;

        Property::update((int)$id, $data);
        $this->setFlash('success', 'Property updated successfully.');
        $this->redirect('/properties/' . $id);
    }

    public function deletePhoto(string $id): void {
        $property = Property::findById((int)$id);
        if (!$property) {
            $this->json(['success' => false, 'message' => 'Property not found.'], 404);
            return;
        }

        $photo = $_POST['photo'] ?? '';
        if (empty($photo)) {
            $this->json(['success' => false, 'message' => 'Photo path is required.'], 400);
            return;
        }

        $photos = [];
        if (!empty($property['photos'])) {
            $decoded = json_decode($property['photos'], true);
            if (is_array($decoded)) {
                $photos = $decoded;
            }
        }
        if (empty($photos) && !empty($property['photo_path'])) {
            $photos = [$property['photo_path']];
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
            $newPhotos = array_values($newPhotos);
            $property['photos'] = !empty($newPhotos) ? json_encode($newPhotos) : null;
            $property['photo_path'] = !empty($newPhotos) ? $newPhotos[0] : null;
            Property::update((int)$id, $property);
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false, 'message' => 'Photo not found on property.'], 404);
        }
    }

    public function delete(string $id): void {
        // Delete photo files if they exist
        $property = Property::findById((int)$id);
        if ($property) {
            $photosToDelete = [];
            if (!empty($property['photos'])) {
                $decoded = json_decode($property['photos'], true);
                if (is_array($decoded)) {
                    $photosToDelete = $decoded;
                }
            }
            if (!empty($property['photo_path']) && !in_array($property['photo_path'], $photosToDelete, true)) {
                $photosToDelete[] = $property['photo_path'];
            }
            foreach ($photosToDelete as $photo) {
                $file = dirname(__DIR__, 2) . '/public' . $photo;
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        }

        Property::delete((int)$id);
        $this->setFlash('success', 'Property deleted successfully.');
        $this->redirect('/properties');
    }

    /**
     * Handle multiple property photos upload from $_FILES['property_photos'].
     * Returns an array of web-relative paths.
     */
    private function handlePhotosUpload(): array {
        if (empty($_FILES['property_photos']) || !is_array($_FILES['property_photos']['name'])) {
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
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/properties';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $names = $_FILES['property_photos']['name'];
        $types = $_FILES['property_photos']['type'];
        $tmpNames = $_FILES['property_photos']['tmp_name'];
        $errors = $_FILES['property_photos']['error'];
        $sizes = $_FILES['property_photos']['size'];

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
            $filename = uniqid('prop_', true) . '.' . $ext;
            $destination = $uploadDir . '/' . $filename;

            if (move_uploaded_file($tmpNames[$idx], $destination)) {
                $uploaded[] = '/uploads/properties/' . $filename;
            }
        }

        return $uploaded;
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
