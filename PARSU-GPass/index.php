<?php
/**
 * PARSU-GPass: Homepage / Welcome Portal
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect authenticated users to their dashboard
if (isLoggedIn()) {
    $role = $_SESSION['account_role'] ?? 'applicant';
    header("Location: " . BASE_URL . "/$role/dashboard.php");
    exit;
}

$pageTitle = "Online Vehicle Gate Pass Management System";
include __DIR__ . '/includes/header.php';
?>

<div class="landing-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <img src="<?= BASE_URL ?>/assets/images/PSU%20LOGO.png" alt="Partido State University Logo" style="height: 120px; width: auto; object-fit: contain; display: block; margin: 0 auto 20px auto;">
        <div class="hero-badge" style="color: #ffffff;">Partido State University</div>
        <h1 class="hero-title">Vehicle Gate Pass &amp; Sticker Application</h1>
        <p class="hero-subtitle">Fast, transparent, and seamless online gate pass registration for students, faculty, and administrative staff across all campuses.</p>
        
        <div class="hero-actions">
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-gold btn-lg"><i class="fa-solid fa-right-to-bracket"></i> Account Login</a>
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline-white btn-lg"><i class="fa-solid fa-user-plus"></i> Create Account</a>
        </div>
    </div>
</div>

<div class="container landing-container">
    <!-- Notice Card -->
    <div class="card notice-banner">
        <div class="notice-icon" style="color: var(--gold-color);">
            <i class="fa-solid fa-bullhorn"></i>
        </div>
        <div class="notice-text">
            <strong>Campus Security Advisory:</strong> All motor vehicles (motorcycles, sedans, SUVs, vans) entering the university premises must display a valid PARSU Gate Pass Sticker. Drive safely and observe the campus 20 km/h speed limit.
        </div>
    </div>

    <!-- Process Flow Section -->
    <section class="landing-section">
        <div class="section-header">
            <h2 class="section-title">How It Works</h2>
            <p class="section-description">Complete your application in five convenient steps from any device.</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon" style="color: var(--primary-light);">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="step-title">Create Account</h3>
                <p class="step-desc">Register with your institutional details as a Student or Employee.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon" style="color: var(--primary-light);">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <h3 class="step-title">Vehicle &amp; Ownership</h3>
                <p class="step-desc">Provide vehicle details and select one of the four verified ownership categories.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon" style="color: var(--primary-light);">
                    <i class="fa-solid fa-signature"></i>
                </div>
                <h3 class="step-title">Sign &amp; Upload</h3>
                <p class="step-desc">Sign on the digital canvas and upload the required vehicle documents (OR, CR, Driver's License).</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <div class="step-icon" style="color: var(--primary-light);">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h3 class="step-title">Payment</h3>
                <p class="step-desc">Once approved, settle the official university fee and upload your cashier PSU-OR receipt.</p>
            </div>
            <div class="step-card">
                <div class="step-number">5</div>
                <div class="step-icon" style="color: var(--primary-light);">
                    <i class="fa-solid fa-id-badge"></i>
                </div>
                <h3 class="step-title">Claim Sticker</h3>
                <p class="step-desc">Track sticker generation and claim your physical RFID gate pass sticker from the Security Office.</p>
            </div>
        </div>
    </section>

    <!-- Gate Pass Fees & Validity -->
    <section class="landing-section">
        <div class="section-header">
            <h2 class="section-title">Fees &amp; Sticker Validity</h2>
            <p class="section-description">Official university gate pass rates established per university board guidelines.</p>
        </div>

        <div class="pricing-grid">
            <div class="pricing-card card">
                <div class="pricing-header">
                    <span class="pricing-role">Student Gate Pass</span>
                    <div class="pricing-amount">&#8369;130.00</div>
                    <span class="pricing-duration">Valid for 2 Academic Years</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fa-solid fa-circle-check" style="color: var(--success-color); margin-right: 8px;"></i> Includes Official Gate Pass Sticker</li>
                    <li><i class="fa-solid fa-circle-check" style="color: var(--success-color); margin-right: 8px;"></i> Valid for Motorcycles and 4-Wheel Vehicles</li>
                    <li><i class="fa-solid fa-circle-check" style="color: var(--success-color); margin-right: 8px;"></i> Validated student ID &amp; registration required</li>
                    <li><i class="fa-solid fa-circle-check" style="color: var(--success-color); margin-right: 8px;"></i> Fast lane access through campus security gates</li>
                </ul>
            </div>

            <div class="pricing-card card featured">
                <div class="featured-tag">Faculty &amp; Staff</div>
                <div class="pricing-header">
                    <span class="pricing-role">Employee Gate Pass</span>
                    <div class="pricing-amount">&#8369;280.00</div>
                    <span class="pricing-duration">Valid for 3 Academic Years</span>
                </div>
                <ul class="pricing-features">
                    <li><i class="fa-solid fa-circle-check" style="color: var(--gold-color); margin-right: 8px;"></i> Multi-Year Priority Campus Sticker</li>
                    <li><i class="fa-solid fa-circle-check" style="color: var(--gold-color); margin-right: 8px;"></i> Faculty and Administrative Staff Designated Parking</li>
                    <li><i class="fa-solid fa-circle-check" style="color: var(--gold-color); margin-right: 8px;"></i> Valid for all registered private vehicles</li>
                    <li><i class="fa-solid fa-circle-check" style="color: var(--gold-color); margin-right: 8px;"></i> Online verification and digital status tracking</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- 4 Ownership Categories Explanation -->
    <section class="landing-section">
        <div class="section-header">
            <h2 class="section-title">Required Documents by Ownership Type</h2>
            <p class="section-description">Ensure you have clear digital copies of the required documents before applying.</p>
        </div>

        <div class="ownership-rules-grid">
            <div class="card rule-card">
                <div class="rule-badge">Type 1 (3 Documents)</div>
                <h4 class="rule-title">First Owner (Own Name)</h4>
                <p class="rule-sub">Vehicle is directly registered under applicant's name.</p>
                <ol class="doc-list">
                    <li>Official Receipt (OR)</li>
                    <li>Certificate of Registration (CR)</li>
                    <li>Applicant Driver's License (with 3 signatures)</li>
                </ol>
            </div>

            <div class="card rule-card">
                <div class="rule-badge">Type 2 (5 Documents)</div>
                <h4 class="rule-title">First Owner (Relative's Name)</h4>
                <p class="rule-sub">Vehicle registered under immediate family or relative.</p>
                <ol class="doc-list">
                    <li>Official Receipt (OR)</li>
                    <li>Certificate of Registration (CR)</li>
                    <li>Registered Vehicle Owner's Driver's License (with 3 signatures)</li>
                    <li>Applicant Driver's License (with 3 signatures)</li>
                    <li>Notarized Authorization Letter from Owner</li>
                </ol>
            </div>

            <div class="card rule-card">
                <div class="rule-badge">Type 3 (5 Documents)</div>
                <h4 class="rule-title">Second-Hand (Applicant is Buyer)</h4>
                <p class="rule-sub">Vehicle bought second-hand with applicant on Deed of Sale.</p>
                <ol class="doc-list">
                    <li>Official Receipt (OR)</li>
                    <li>Certificate of Registration (CR)</li>
                    <li>Notarized Deed of Sale</li>
                    <li>Previous Registered Owner's Driver's License (with 3 signatures)</li>
                    <li>Applicant Driver's License (with 3 signatures)</li>
                </ol>
            </div>

            <div class="card rule-card">
                <div class="rule-badge">Type 4 (7 Documents)</div>
                <h4 class="rule-title">Second-Hand (Applicant NOT Buyer)</h4>
                <p class="rule-sub">Bought second-hand by relative; applicant is authorized driver.</p>
                <ol class="doc-list">
                    <li>Official Receipt (OR)</li>
                    <li>Certificate of Registration (CR)</li>
                    <li>Notarized Deed of Sale</li>
                    <li>Previous Registered Owner's Driver's License (with 3 signatures)</li>
                    <li>Applicant Driver's License (with 3 signatures)</li>
                    <li>Notarized Authorization Letter from Buyer</li>
                    <li>Buyer's Driver's License (with 3 signatures)</li>
                </ol>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

