-- PropPulse Property Management System Database Schema
-- Compatible with MySQL 5.7+ / 8.0+ and MariaDB 10.3+ (Standard XAMPP)

CREATE DATABASE IF NOT EXISTS `property_management_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `property_management_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'manager', 'tenant', 'vendor') NOT NULL DEFAULT 'manager',
    `phone` VARCHAR(30) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Properties Table
DROP TABLE IF EXISTS `properties`;
CREATE TABLE `properties` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `manager_id` INT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(150) NOT NULL,
    `address_line1` VARCHAR(255) NOT NULL,
    `address_line2` VARCHAR(100) DEFAULT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(50) NOT NULL,
    `zip_code` VARCHAR(20) NOT NULL,
    `property_type` ENUM('single_family', 'multi_family', 'apartment', 'commercial', 'condo') NOT NULL DEFAULT 'multi_family',
    `description` TEXT DEFAULT NULL,
    `photo_path` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_properties_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Units Table
DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL,
    `unit_number` VARCHAR(50) NOT NULL,
    `floor` INT DEFAULT 1,
    `bedrooms` INT UNSIGNED DEFAULT 1,
    `bathrooms` DECIMAL(3,1) DEFAULT 1.0,
    `square_feet` INT UNSIGNED DEFAULT NULL,
    `target_rent` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `security_deposit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('vacant', 'occupied', 'maintenance') NOT NULL DEFAULT 'vacant',
    `adults_only` ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    `furnished` ENUM('yes', 'no') NOT NULL DEFAULT 'no',
    `pets` ENUM('allowed', 'not_allowed') NOT NULL DEFAULT 'not_allowed',
    `smoking` ENUM('allowed', 'not_allowed') NOT NULL DEFAULT 'not_allowed',
    `parking` ENUM('garage', 'driveway', 'street') NOT NULL DEFAULT 'street',
    `available_date` DATE DEFAULT NULL,
    `tenant_insurance` ENUM('required', 'not_required') NOT NULL DEFAULT 'not_required',
    `utilities` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `appliances` TEXT DEFAULT NULL,
    `photos` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_units_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_unit_per_property` (`property_id`, `unit_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tenants Table
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `emergency_contact` VARCHAR(100) DEFAULT NULL,
    `emergency_phone` VARCHAR(30) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tenants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Leases Table
DROP TABLE IF EXISTS `leases`;
CREATE TABLE `leases` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `unit_id` INT UNSIGNED NOT NULL,
    `tenant_id` INT UNSIGNED DEFAULT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `rent_amount` DECIMAL(10,2) NOT NULL,
    `deposit_held` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_frequency` ENUM('monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'monthly',
    `status` ENUM('draft', 'active', 'expiring_soon', 'terminated', 'expired') NOT NULL DEFAULT 'active',
    `terms` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_leases_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_leases_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Invoices Table
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lease_id` INT UNSIGNED NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NOT NULL DEFAULT 'Monthly Rent',
    `amount` DECIMAL(10,2) NOT NULL,
    `due_date` DATE NOT NULL,
    `status` ENUM('pending', 'paid', 'partially_paid', 'overdue') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_invoices_lease` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Payments Table
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `amount_paid` DECIMAL(10,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `payment_method` ENUM('cash', 'check', 'bank_transfer', 'card', 'online') NOT NULL DEFAULT 'bank_transfer',
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Maintenance Requests Table
DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE `maintenance_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `unit_id` INT UNSIGNED NOT NULL,
    `tenant_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `category` ENUM('plumbing', 'electrical', 'hvac', 'appliance', 'structural', 'pest_control', 'other') NOT NULL DEFAULT 'other',
    `priority` ENUM('low', 'medium', 'high', 'emergency') NOT NULL DEFAULT 'medium',
    `status` ENUM('new', 'assigned', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'new',
    `assigned_to` VARCHAR(100) DEFAULT NULL,
    `resolution_notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_maint_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_maint_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Documents Table (Local Document Storage Catalog)
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `related_type` ENUM('lease', 'maintenance', 'tenant', 'property') NOT NULL,
    `related_id` INT UNSIGNED NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `uploaded_by_user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_related` (`related_type`, `related_id`),
    CONSTRAINT `fk_documents_user` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Applicants Table (Rental Applications)
DROP TABLE IF EXISTS `applicants`;
CREATE TABLE `applicants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Accommodation Information
    `date_requested` DATE DEFAULT NULL,
    `occupants_adults` VARCHAR(500) DEFAULT NULL,
    `occupants_children` VARCHAR(500) DEFAULT NULL,
    
    -- Term of Tenancy
    `tenancy_type` ENUM('fixed', 'periodic') NOT NULL DEFAULT 'periodic',
    `term_start_date` DATE DEFAULT NULL,
    `term_end_date` DATE DEFAULT NULL,
    
    -- Personal Information
    `full_name` VARCHAR(150) NOT NULL,
    `birthday` DATE DEFAULT NULL,
    `home_phone` VARCHAR(30) DEFAULT NULL,
    `cell_phone` VARCHAR(30) DEFAULT NULL,
    `present_address` VARCHAR(255) DEFAULT NULL,
    `present_address_duration` VARCHAR(100) DEFAULT NULL,
    `previous_address` VARCHAR(255) DEFAULT NULL,
    `previous_address_duration` VARCHAR(100) DEFAULT NULL,
    `driver_license` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(191) DEFAULT NULL,
    
    -- Employment Information
    `current_employer` VARCHAR(150) DEFAULT NULL,
    `current_occupation` VARCHAR(150) DEFAULT NULL,
    `current_supervisor` VARCHAR(150) DEFAULT NULL,
    `current_employment_duration` VARCHAR(100) DEFAULT NULL,
    `current_employer_address` VARCHAR(255) DEFAULT NULL,
    `current_employer_phone` VARCHAR(30) DEFAULT NULL,
    
    `previous_employer` VARCHAR(150) DEFAULT NULL,
    `previous_occupation` VARCHAR(150) DEFAULT NULL,
    `previous_supervisor` VARCHAR(150) DEFAULT NULL,
    `previous_employment_duration` VARCHAR(100) DEFAULT NULL,
    `previous_employer_address` VARCHAR(255) DEFAULT NULL,
    `previous_employer_phone` VARCHAR(30) DEFAULT NULL,
    
    -- Income Information
    `monthly_income` DECIMAL(10,2) DEFAULT NULL,
    
    -- Rental History
    `current_landlord_name` VARCHAR(150) DEFAULT NULL,
    `current_landlord_phone` VARCHAR(30) DEFAULT NULL,
    `previous_landlord_name` VARCHAR(150) DEFAULT NULL,
    `previous_landlord_phone` VARCHAR(30) DEFAULT NULL,
    
    -- Relatives or Friends (Emergency Contacts)
    `emergency_contact_1_name` VARCHAR(150) DEFAULT NULL,
    `emergency_contact_1_address` VARCHAR(255) DEFAULT NULL,
    `emergency_contact_1_phone` VARCHAR(30) DEFAULT NULL,
    `emergency_contact_1_relationship` VARCHAR(100) DEFAULT NULL,
    
    `emergency_contact_2_name` VARCHAR(150) DEFAULT NULL,
    `emergency_contact_2_address` VARCHAR(255) DEFAULT NULL,
    `emergency_contact_2_phone` VARCHAR(30) DEFAULT NULL,
    `emergency_contact_2_relationship` VARCHAR(100) DEFAULT NULL,
    
    -- Credit check authorization & signature
    `credit_check_authorized` TINYINT(1) DEFAULT 0,
    `application_date` DATE DEFAULT NULL,
    `applicant_signature` MEDIUMTEXT DEFAULT NULL,
    
    -- Workflow / Assignment fields
    `status` ENUM('pending', 'approved', 'lease_created', 'lease_sent', 'converted', 'rejected') NOT NULL DEFAULT 'pending',
    `status_date` DATETIME DEFAULT NULL,
    `target_property_id` INT UNSIGNED DEFAULT NULL,
    `target_unit_id` INT UNSIGNED DEFAULT NULL,
    `tenant_id` INT UNSIGNED DEFAULT NULL,
    `lease_id` INT UNSIGNED DEFAULT NULL,
    `lease_signed` TINYINT(1) NOT NULL DEFAULT 0,
    `lease_signed_at` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_applicants_property` FOREIGN KEY (`target_property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_applicants_unit` FOREIGN KEY (`target_unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_applicants_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Lease Templates Table (Word DOCX Templates)
DROP TABLE IF EXISTS `lease_templates`;
CREATE TABLE `lease_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `placeholder_mappings` LONGTEXT DEFAULT NULL,
    `uploaded_by_user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_uploaded_by` (`uploaded_by_user_id`),
    CONSTRAINT `fk_lease_templates_user` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
