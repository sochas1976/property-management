# PropPulse — Property Management System (PMS)

A modern, responsive Property Management System built with a **PHP MVC backend**, **MySQL database (InnoDB)**, **HTML5/CSS3/JavaScript frontend**, and **local filesystem document storage**.

Designed to run seamlessly on **XAMPP** on Windows with zero external framework dependencies or Composer installations required.

---

## Key Features

- **Portfolio & Unit Hierarchy**: Manage multiple properties (single-family, multi-family, commercial, condos), customize unit floor plans, square footage, bed/bath specs, and target rents.
- **Tenant Directory**: Complete resident roster with emergency contact records, active lease assignments, and rental history.
- **Lease Lifecycle Management**: Draft, activate, terminate, and monitor 12-month or custom leases. When a lease is signed, the associated unit occupancy automatically updates to `Occupied`.
- **Local Document Storage Vault**: Upload signed lease agreements (PDFs) and maintenance evidence photos directly to a protected local disk directory (`storage/`) outside web-root access, streamed via authenticated controllers.
- **Maintenance & Work Orders**: Priority-tagged ticketing (`Emergency`, `High`, `Medium`, `Low`), issue categorization (plumbing, HVAC, electrical, etc.), contractor assignment, completion notes, and local photo attachments.
- **Billing & Financial Ledger**: Invoicing system, automated balance calculation, rent collection logging (ACH, card, cash, check), and rent roll summary metrics.
- **Security & Authorization**: Role-based access control, bcrypt password hashing, CSRF protection, and SQL injection prevention via PDO prepared statements.

---

## System Requirements

- **PHP 8.0+** with `pdo_mysql` and `fileinfo` extensions enabled (enabled by default in XAMPP).
- **MySQL 5.7+ / 8.0+** or **MariaDB 10.3+** (included in XAMPP).
- **Apache Web Server** with `mod_rewrite` enabled (included in XAMPP).

---

## Quick Setup Guide for XAMPP

### Step 1: Start XAMPP Services
1. Open your **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

---

### Step 2: Import Database Schema & Demo Data
1. Open your browser and go to **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click the **Import** tab at the top.
3. Choose the file `database/schema.sql` from this project and click **Go** (creates `property_management_db` and tables).
4. Click **Import** again, choose `database/seed.sql`, and click **Go** (loads sample properties, units, leases, and users).

*Alternatively, run via Windows Command Prompt / PowerShell:*
```bash
mysql -u root < database/schema.sql
mysql -u root < database/seed.sql
```

---

### Step 3: Configure Database Credentials
Open `config/database.php`. The default settings match standard XAMPP out of the box:
```php
return [
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'database' => 'property_management_db',
    'username' => 'root',
    'password' => '', // Default XAMPP root password is empty
];
```

---

### Step 4: Accessing the Application

#### Option A: Running directly with PHP Built-in Server (Easiest)
From this project directory in your terminal, run:
```bash
php -S localhost:8000 -t public
```
Then visit: **[http://localhost:8000](http://localhost:8000)**

#### Option B: Running inside XAMPP `htdocs`
If this folder is located or symlinked inside `C:\xampp\htdocs\property-management`, open:
**`http://localhost/property-management/public/`**

The custom router automatically detects the subfolder path and routes all requests seamlessly!

---

## Default Login Credentials

Use the pre-seeded administrator account to log in:

- **Email**: `admin@proppulse.com`
- **Password**: `password`

*(Additional manager and tenant accounts are also available in `database/seed.sql`)*

---

## Local Document Storage Architecture

Files uploaded via the system (such as signed Lease Agreement PDFs or Maintenance photos) are managed with strict security:

1. **Storage Location**:
   - Lease documents: `storage/documents/leases/`
   - Maintenance photos: `storage/photos/maintenance/`
2. **Access Control**:
   - `storage/.htaccess` enforces `Require all denied` so web browsers cannot access uploaded documents directly via URL.
   - Files are securely streamed through `DocumentController` (`/documents/{id}`) only after verifying that an active user session is present.
3. **MIME Verification & UUID Naming**:
   - Uploaded files are checked against disk magic bytes (`finfo`) rather than client file extensions to prevent malicious script uploads.
   - Files are stored on disk with cryptographic random UUIDs (e.g. `c4b18f8e12b7a...pdf`), with original human-readable names preserved in the database.

---

## Directory Structure

```
├── app/
│   ├── Controllers/       # Auth, Dashboard, Property, Unit, Tenant, Lease, Maintenance, Finance, Document
│   ├── Core/              # Database (PDO), Router, Auth, Controller, Storage
│   └── Models/            # User, Property, Unit, Tenant, Lease, MaintenanceRequest, Invoice, Payment, Document
├── config/                # Database & Application constants
├── database/              # MySQL schema.sql & seed.sql
├── public/                # Web Root
│   ├── css/               # Modern stylesheet (app.css)
│   ├── js/                # Interactive UI scripts (app.js)
│   ├── .htaccess          # URL rewriting
│   └── index.php          # Application front controller & routing
├── storage/               # Protected local vault (.htaccess protected)
│   ├── documents/leases/
│   └── photos/maintenance/
├── views/                 # HTML templates
│   ├── auth/
│   ├── dashboard/
│   ├── finances/
│   ├── layouts/
│   ├── leases/
│   ├── maintenance/
│   ├── properties/
│   ├── tenants/
│   └── units/
└── README.md
```
