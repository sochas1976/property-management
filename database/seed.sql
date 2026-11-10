-- PropPulse PMS Seed Data
USE `property_management_db`;

-- 1. Seed Users (Default password for all users is: password)
-- Password hash generated via password_hash('password', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `role`, `phone`, `created_at`) VALUES
(1, 'admin@proppulse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alex Rivera', 'admin', '(555) 100-2000', NOW()),
(2, 'manager@proppulse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marcus Vance', 'manager', '(555) 100-3000', NOW()),
(3, 'tenant.john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'tenant', '(555) 234-5678', NOW()),
(4, 'tenant.sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Jenkins', 'tenant', '(555) 987-6543', NOW())
ON DUPLICATE KEY UPDATE `email`=`email`;

-- 2. Seed Properties
INSERT INTO `properties` (`id`, `manager_id`, `name`, `address_line1`, `address_line2`, `city`, `state`, `zip_code`, `property_type`, `description`, `created_at`) VALUES
(1, 1, 'Sunset Heights Apartments', '1240 Ocean Avenue', 'Bldg A', 'Santa Monica', 'CA', '90401', 'multi_family', 'Luxury coastal residential community with updated amenities and ocean views.', NOW()),
(2, 1, 'Oakridge Executive Park', '450 Commerce Boulevard', NULL, 'Austin', 'TX', '78701', 'commercial', 'Prime downtown commercial office spaces with high-speed fiber and dedicated parking.', NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 3. Seed Units
INSERT INTO `units` (`id`, `property_id`, `unit_number`, `floor`, `bedrooms`, `bathrooms`, `square_feet`, `target_rent`, `security_deposit`, `status`, `notes`, `created_at`) VALUES
(1, 1, '101', 1, 2, 2.0, 980, 2600.00, 2600.00, 'occupied', 'Corner unit with balcony and in-unit washer/dryer.', NOW()),
(2, 1, '102', 1, 1, 1.0, 680, 1950.00, 1950.00, 'vacant', 'Freshly painted, hardwood floors, ready for immediate move-in.', NOW()),
(3, 1, '201', 2, 3, 2.0, 1350, 3400.00, 3400.00, 'occupied', 'Top floor penthouse layout with panoramic sunset view.', NOW()),
(4, 2, 'Suite 300', 3, 0, 1.0, 2200, 4800.00, 4800.00, 'maintenance', 'Open plan commercial office currently undergoing HVAC upgrade.', NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 4. Seed Tenants
INSERT INTO `tenants` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `emergency_contact`, `emergency_phone`, `notes`, `created_at`) VALUES
(1, 3, 'John', 'Doe', 'tenant.john@example.com', '(555) 234-5678', 'Emily Doe (Sister)', '(555) 234-9999', 'Prefers email communication for all notices.', NOW()),
(2, 4, 'Sarah', 'Jenkins', 'tenant.sarah@example.com', '(555) 987-6543', 'David Jenkins (Father)', '(555) 987-0000', 'Has registered service animal (labrador).', NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 5. Seed Leases
INSERT INTO `leases` (`id`, `unit_id`, `tenant_id`, `start_date`, `end_date`, `rent_amount`, `deposit_held`, `payment_frequency`, `status`, `terms`, `created_at`) VALUES
(1, 1, 1, '2026-01-01', '2026-12-31', 2600.00, 2600.00, 'monthly', 'active', 'Standard 12-month residential lease. Rent due on the 1st of each month.', NOW()),
(2, 3, 2, '2026-03-01', '2027-02-28', 3400.00, 3400.00, 'monthly', 'active', '12-month lease with designated garage space #14 included.', NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 6. Seed Invoices
INSERT INTO `invoices` (`id`, `lease_id`, `invoice_number`, `description`, `amount`, `due_date`, `status`, `created_at`) VALUES
(1, 1, 'INV-2026-001', 'Rent - September 2026', 2600.00, '2026-09-01', 'paid', NOW()),
(2, 2, 'INV-2026-002', 'Rent - September 2026', 3400.00, '2026-09-01', 'paid', NOW()),
(3, 1, 'INV-2026-003', 'Rent - October 2026', 2600.00, '2026-10-01', 'pending', NOW()),
(4, 2, 'INV-2026-004', 'Rent - October 2026', 3400.00, '2026-10-01', 'pending', NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 7. Seed Payments
INSERT INTO `payments` (`id`, `invoice_id`, `amount_paid`, `payment_date`, `payment_method`, `reference_number`, `notes`, `created_at`) VALUES
(1, 1, 2600.00, '2026-09-01', 'bank_transfer', 'ACH-994821', 'Paid in full on time via direct ACH deposit', NOW()),
(2, 2, 3400.00, '2026-09-02', 'card', 'STRIPE-CHG-4402', 'Paid online via tenant portal', NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 8. Seed Maintenance Requests
INSERT INTO `maintenance_requests` (`id`, `unit_id`, `tenant_id`, `title`, `description`, `category`, `priority`, `status`, `assigned_to`, `created_at`) VALUES
(1, 1, 1, 'Kitchen sink drain slow and dripping', 'The pipe under the double sink in the kitchen is slowly dripping into the vanity bucket.', 'plumbing', 'medium', 'in_progress', 'Bob Handyman Services', NOW()),
(2, 4, NULL, 'Commercial HVAC quarterly filter replacement', 'Quarterly routine maintenance and coil cleaning for roof unit #3.', 'hvac', 'low', 'assigned', 'CoolAir Tech Inc.', NOW()),
(3, 3, 2, 'Master bedroom window latch stuck', 'The sliding window lock mechanism seems jammed.', 'structural', 'low', 'new', NULL, NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;
