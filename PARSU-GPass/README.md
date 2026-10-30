# PARSU-GPass: Online Vehicle Gate Pass Management System
**Partido State University (PSU)**

PARSU-GPass is an enterprise-grade vehicle gate pass and RFID sticker management system built with **plain PHP 8+ (PDO, no framework)**, **MySQL/MariaDB**, **HTML5**, **CSS3**, and **vanilla JavaScript**. It streamlines vehicle registration, security evaluations, payment settlements, and sticker issuance for students, faculty, and administrative staff across all campuses.

---

## 🚀 Key Features & Highlights

1. **Strict Role-Based Access Control (RBAC):**
   - Independent applicant and administrator portals guarded by `includes/applicant_auth.php` and `includes/admin_auth.php`.
   - Automatic redirection to `unauthorized.php` for forbidden routes.
2. **Dynamic 4-Category Ownership Engine:**
   - **Type 1: First-hand Owner (Own Name)** — 3 Documents: OR, CR, Applicant Driver's License.
   - **Type 2: First-hand Owner (Relative's Name)** — 5 Documents: OR, CR, Owner's DL, Applicant's DL, Notarized Authorization Letter.
   - **Type 3: Second-hand (Applicant is Buyer)** — 5 Documents: OR, CR, Notarized Deed of Sale, Previous Owner's DL, Applicant's DL.
   - **Type 4: Second-hand (Applicant NOT Buyer)** — 7 Documents: OR, CR, Deed of Sale, Previous Owner's DL, Applicant's DL, Buyer's Authorization Letter, Buyer's DL.
   - Client-side and server-side rules enforce that each Driver's License upload includes both front and back with 3 specimen signatures.
3. **HTML5 Canvas Digital Signature Pad:**
   - Captures applicant signatures with mouse and touch support, stroke history, undo, clear, and high-resolution base64 PNG export.
4. **Resubmission Without Duplication:**
   - Declines strictly require administrative remarks.
   - Resubmission updates the same application record; replaced files are versioned (`version_number`), retaining full audit history.
5. **Server-Enforced Financial & Validity Rules:**
   - **Student Gate Pass:** ₱130.00 (Valid for 2 Academic Years).
   - **Employee Gate Pass:** ₱280.00 (Valid for 3 Academic Years).
   - Amount is computed entirely on the server to prevent tampering.
   - Unique cashier PSU-OR receipt number enforcement.
6. **Sticker Issuance & Physical Release Handover:**
   - Automatic sticker numbering: `GP-YYYY-NNNNNN`.
   - Handover protocol (`sticker_releases`) logs recipient identity and releasing officer before activating the pass.
   - Real-time expired sticker detection updating status for records where `valid_until < CURDATE()`.
7. **Comprehensive Reporting & Document Inspector:**
   - Full-screen document previewer for images and PDFs.
   - Printable reports for Applications, Financial Collections, Released Stickers, and Expired Stickers.
   - Printable RFID windshield sticker and official Certificate of Gate Pass Registration.

---

## 🛠️ System Requirements & Prerequisites

- **Web Server:** Apache (via XAMPP, WAMP, or LAMP) with `mod_rewrite` enabled.
- **PHP Version:** PHP 8.0 or higher (PHP 8.1, 8.2, 8.3 supported).
- **PHP Extensions:** `pdo_mysql`, `fileinfo`, `session`.
- **Database:** MySQL 5.7+ or MariaDB 10.4+.
- **Browser:** Any modern web browser (Google Chrome, Mozilla Firefox, Microsoft Edge, Safari).

---

## 📦 Installation & Setup on XAMPP

### Step 1: Place the Project in XAMPP
Copy the `PARSU-GPass` folder into your XAMPP web root:
```
C:\xampp\htdocs\PARSU-GPass
```
*(If running on a separate folder or alias, the system automatically detects its base URL).*

### Step 2: Start Apache and MySQL
1. Launch the **XAMPP Control Panel**.
2. Click **Start** for both **Apache** and **MySQL**.

### Step 3: Import the Database
1. Open your browser and navigate to **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click on the **Databases** tab, create a new database named `parsu_gpass` with collation `utf8mb4_unicode_ci`.
3. Select `parsu_gpass`, click on the **Import** tab.
4. Choose the SQL file located at:
   ```
   PARSU-GPass/database/parsu_gpass.sql
   ```
5. Click **Import**. All tables, foreign key constraints, default document requirements, and seed accounts will be created.

### Step 4: Verify Database Configuration
Open `config/database.php` and verify the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'parsu_gpass');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
```

### Step 5: Verify Uploads Folder Permissions
Ensure the following directories inside `uploads/` have write permissions (read/write):
- `uploads/signatures/`
- `uploads/official_receipts/`
- `uploads/certificates_of_registration/`
- `uploads/drivers_licenses/`
- `uploads/authorization_letters/`
- `uploads/deeds_of_sale/`
- `uploads/payment_receipts/`

*(Each folder contains an `.htaccess` file preventing direct script execution).*

---

## 🔑 Default Test Credentials

All passwords have been securely hashed using bcrypt (`PASSWORD_BCRYPT`):

| Role | Email | Password | Details |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin@parsu.edu.ph` | `Admin@123456` | Campus Security Evaluator & Cashier Verifier |
| **Student Applicant** | `student@parsu.edu.ph` | `Student@123456` | Juan Dela Cruz &bull; ID: `STUD-2024-0012` &bull; ₱130.00 / 2 Yrs |
| **Employee Applicant** | `employee@parsu.edu.ph` | `Employee@123456` | Maria Reyes &bull; ID: `EMP-2018-0481` &bull; ₱280.00 / 3 Yrs |

---

## 📋 Comprehensive Manual Test Checklist

Follow this workflow to verify all features and business rules:

### 1. New Registration & Authentication
- [ ] Navigate to `http://localhost/PARSU-GPass/register.php`.
- [ ] Select **Student** or **Employee**. Note that student fields require Course/Year/Section.
- [ ] Fill in personal info and create an account. Confirm redirect to `login.php`.
- [ ] Sign in with the registered credentials and verify auto-routing to `/applicant/dashboard.php`.

### 2. Multi-Step Application Wizard (Testing Ownership Categories)
- [ ] Click **Apply for Gate Pass** (`create.php`).
- [ ] **Step 1 (Personal Details):** Verify pre-filled profile information, update contact number, click **Next**.
- [ ] **Step 2 (Vehicle Details):** Choose Vehicle Type (e.g., Motorcycle), enter Year Model (e.g., 2023), Plate Number (e.g., `ABC-1234`), Color, and Driver's License Number. Click **Next**.
- [ ] **Step 3 (Ownership Type):**
  - **Category 1 (First Owner - Own Name):** Verify only 3 uploads are required (OR, CR, Applicant DL).
  - **Category 2 (First Owner - Relative's Name):** Verify Owner Name & Relationship inputs appear; 5 uploads required.
  - **Category 3 (Second-hand - Applicant Buyer):** Verify Previous Owner input appears; 5 uploads required (Deed of Sale included).
  - **Category 4 (Second-hand - Not Buyer):** Verify Previous Owner, Buyer Name, and Relationship appear; 7 uploads required.
- [ ] Select a category and proceed to **Step 4 (Signature)**.
- [ ] **Step 4 (Digital Signature):** Draw a signature on the canvas pad using mouse or touch. Test **Undo Stroke** and **Clear Pad**. Click **Save & Continue**.
- [ ] **Step 5 (Documents):** Upload required sample files (JPG, PNG, or PDF up to 5MB). Verify file type and size validation.
- [ ] **Step 6 (Review & Submit):** Confirm all specifications, fee calculation (₱130 for student, ₱280 for employee), signature specimen, and files. Check undertaking box and click **Confirm & Submit**.
- [ ] Verify application status is now **Pending Review** with a generated Application Number (`GP-YYYY-NNNNNN`).

### 3. Administrator Review (Decline & Resubmit Cycle)
- [ ] Log out, then log in as Administrator (`admin@parsu.edu.ph` / `Admin@123456`).
- [ ] Go to **Pending Applications** (`admin/applications/pending.php`).
- [ ] Click **Review** on the submitted application.
- [ ] Inspect the vehicle details, ownership chain, and click **Inspect Files** to view documents full-screen.
- [ ] Click **Decline Application**. Note that the decline reason is mandatory. Type a sample remark (e.g., *"Certificate of Registration is blurry; please upload a clear scanned copy"*). Confirm decline.
- [ ] Log out and log back in as the applicant.
- [ ] Notice the dashboard warning badge. Click **View Decline Details** (`decline_details.php`).
- [ ] Read the administrator's exact decline remarks.
- [ ] Click **Replace Document** on the Certificate of Registration, choose a clearer file, and submit.
- [ ] Go to **Resubmit Application** (`resubmit.php`), enter applicant explanation notes, and finalize resubmission.
- [ ] Note that the application number remains identical and file version increments to `v2`.

### 4. Admin Approval & Cashier Payment Flow
- [ ] Log in as Admin. Navigate to **Resubmitted Applications** (`admin/applications/resubmitted.php`).
- [ ] Open the application, check version 2 of the document, and click **Approve Application**.
- [ ] Log in as Applicant. Observe the status has advanced to **Approved (Payment Pending)**.
- [ ] Go to **Upload Receipt** (`applicant/payment/upload_receipt.php`).
- [ ] Observe that the fee amount is fixed (₱130.00 for Student, ₱280.00 for Employee). Enter a unique PSU-OR receipt number (e.g. `OR-2026-009911`), payment date, and attach receipt file. Submit.
- [ ] Log in as Admin. Go to **Pending Payment Verifications** (`admin/payments/pending.php`).
- [ ] Open the payment record, inspect the receipt image, and click **Confirm Payment**.
- [ ] Application status updates to **Paid**.

### 5. Gate Pass Sticker Issuance & Physical Release
- [ ] In Admin portal, click **Generate Gate Pass Sticker** (`admin/gatepass/generate_sticker.php`).
- [ ] Verify auto-computed validity (Student = 2 years, Employee = 3 years) and unique sticker number format (`GP-2026-000001`). Click **Issue Sticker**.
- [ ] Status updates to **For Release**. View the printable physical sticker badge card.
- [ ] Go to **Record Physical Release** (`admin/gatepass/release.php`).
- [ ] Enter claimant's name (e.g. *"Juan Dela Cruz"*) and release remarks. Confirm handover.
- [ ] Status updates to **Released**.
- [ ] Log in as Applicant, navigate to **Gate Pass Sticker** (`applicant/gatepass/view_sticker.php`), and review the active Gate Pass badge and printable registration certificate.

### 6. Reports & Audit Verification
- [ ] In Admin portal, test all 4 reports:
  - **Applications Report:** Test date and status filters; click **Print Report**.
  - **Payments Report:** Review student vs employee subtotals and grand total revenue.
  - **Released Stickers Report:** Verify audit list of handed-over stickers.
  - **Expired Stickers Report:** Verifies rule 11: checks and updates stickers whose expiration date is prior to today.

---

## 🔒 Security Architecture

- **SQL Injection Prevention:** 100% prepared statements via PHP PDO. No raw string interpolation.
- **CSRF Protection:** Cryptographically secure random 32-byte tokens (`random_bytes`) generated per session and validated on all POST requests.
- **XSS Defense:** Strict context escaping (`e()` wrapper for `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- **File Upload Protection:** Strict MIME type inspection using `finfo` (bypassing spoofed extensions), randomized stored filenames, and Apache `.htaccess` script execution prevention (`php_flag engine off`, `Require all denied` for executable extensions).
- **Session Hijacking Mitigation:** Strict cookie settings (`httponly`, `samesite=Lax`, `secure` on HTTPS), `session_regenerate_id(true)` upon authentication.
- **Transactional Consistency:** Critical multi-table writes (submission, review, payment verification, sticker issuance, physical release) wrapped in `PDO::beginTransaction()` / `commit()` / `rollBack()`.

