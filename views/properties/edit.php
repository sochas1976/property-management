<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/properties/' . $property['id']) ?>">&larr; Back to <?= htmlspecialchars($property['name']) ?></a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Edit Property</h2>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/properties/' . $property['id'] . '/update') ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Property Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($property['name']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="property_type">Property Type *</label>
                    <select id="property_type" name="property_type" class="form-control">
                        <option value="multi_family" <?= $property['property_type'] === 'multi_family' ? 'selected' : '' ?>>Multi-Family Residential</option>
                        <option value="single_family" <?= $property['property_type'] === 'single_family' ? 'selected' : '' ?>>Single Family Home</option>
                        <option value="apartment" <?= $property['property_type'] === 'apartment' ? 'selected' : '' ?>>Apartment Complex</option>
                        <option value="commercial" <?= $property['property_type'] === 'commercial' ? 'selected' : '' ?>>Commercial / Office</option>
                        <option value="condo" <?= $property['property_type'] === 'condo' ? 'selected' : '' ?>>Condominium</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="manager_id">Assigned Property Manager</label>
                    <select id="manager_id" name="manager_id" class="form-control">
                        <option value="">-- Select Manager --</option>
                        <?php foreach ($managers as $mgr): ?>
                            <option value="<?= $mgr['id'] ?>" <?= ($property['manager_id'] == $mgr['id']) ? 'selected' : '' ?>><?= htmlspecialchars($mgr['full_name']) ?> (<?= htmlspecialchars($mgr['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="address_line1">Street Address *</label>
                <input type="text" id="address_line1" name="address_line1" class="form-control" value="<?= htmlspecialchars($property['address_line1']) ?>" required>
            </div>

            <div class="form-group">
                <label for="address_line2">Address Line 2</label>
                <input type="text" id="address_line2" name="address_line2" class="form-control" value="<?= htmlspecialchars($property['address_line2'] ?? '') ?>">
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($property['city']) ?>" required>
                </div>
                <div class="form-group col-4">
                    <label for="state">State / Province *</label>
                    <input type="text" id="state" name="state" class="form-control" value="<?= htmlspecialchars($property['state']) ?>" required>
                </div>
                <div class="form-group col-4">
                    <label for="zip_code">ZIP / Postal Code *</label>
                    <input type="text" id="zip_code" name="zip_code" class="form-control" value="<?= htmlspecialchars($property['zip_code']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="photo">Property Photo</label>
                <?php if (!empty($property['photo_path'])): ?>
                    <div class="photo-preview-row" style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <img src="<?= Router::url($property['photo_path']) ?>" alt="Current photo" class="property-thumbnail" style="width: 80px; height: 80px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 400; color: var(--text-secondary);">
                            <input type="checkbox" name="remove_photo" value="1"> Remove current photo
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" id="photo" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <small class="text-muted">Max 5 MB. Accepts JPG, PNG, WebP, GIF. Leave empty to keep current photo.</small>
            </div>

            <div class="form-group">
                <label for="description">Property Description / Notes</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($property['description'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/properties/' . $property['id']) ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
