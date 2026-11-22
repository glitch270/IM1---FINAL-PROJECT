<?php
/**
 * Applicant: Digital Gate Pass & Vehicle Sticker View
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/applicant_auth.php';

$db = getDBConnection();
$applicantId = (int)$_SESSION['applicant_id'];

$stmt = $db->prepare("
    SELECT s.*, a.application_number, a.application_status,
           ap.first_name, ap.middle_name, ap.last_name, ap.suffix, ap.school_id, ap.applicant_type, ap.department, ap.course_year_section,
           v.vehicle_type, v.plate_number, v.year_model, v.color, v.license_number,
           p.psu_or_number, p.amount, p.payment_date,
           rel.released_at, rel.received_by
    FROM applications a
    JOIN applicant_profiles ap ON a.applicant_id = ap.applicant_id
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payments p ON a.application_id = p.application_id
    LEFT JOIN gate_pass_stickers s ON a.application_id = s.application_id
    LEFT JOIN sticker_releases rel ON s.sticker_id = rel.sticker_id
    WHERE a.applicant_id = ?
    ORDER BY a.application_id DESC LIMIT 1
");
$stmt->execute([$applicantId]);
$gatepass = $stmt->fetch();

$pageTitle = "My Gate Pass Sticker";
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-title-bar no-print">
    <div>
        <h1 class="page-title">Campus Vehicle Gate Pass</h1>
        <p class="page-subtitle">Your official Partido State University vehicle gate pass authorization.</p>
    </div>
    <?php if ($gatepass && $gatepass['sticker_number']): ?>
        <div class="header-action-group">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">🖨️ Print Gate Pass Certificate</button>
            <a href="<?= BASE_URL ?>/applicant/gatepass/release_status.php" class="btn btn-outline-primary">Release Status</a>
        </div>
    <?php endif; ?>
</div>

<?php if (!$gatepass || !$gatepass['sticker_number']): ?>
    <div class="card empty-state-card">
        <div class="empty-state-icon">🏷️</div>
        <h2 class="empty-state-title">No Sticker Issued Yet</h2>
        <p class="empty-state-desc">
            Your vehicle gate pass sticker will be generated once your application is approved and your cashier payment (PSU-OR) has been verified.
        </p>
        <div class="empty-state-actions">
            <?php if (!$gatepass): ?>
                <a href="<?= BASE_URL ?>/applicant/application/create.php" class="btn btn-primary">Start Application</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/applicant/application/application_status.php" class="btn btn-primary">Track Application Status</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Notice Banner -->
    <?php if ($gatepass['sticker_status'] === 'for_release'): ?>
        <div class="alert alert-primary no-print" style="margin-bottom: 20px;">
            <div class="alert-title">🏷️ Ready for Physical Pickup</div>
            <p>Your RFID gate pass sticker is printed and ready! Visit the <strong>Campus Security Office</strong> during office hours (8:00 AM - 5:00 PM) with your School ID to claim your physical sticker.</p>
        </div>
    <?php elseif ($gatepass['sticker_status'] === 'released'): ?>
        <div class="alert alert-success no-print" style="margin-bottom: 20px;">
            <div class="alert-title">✅ Gate Pass Active & Released</div>
            <p>Physical sticker claimed by <strong><?= e($gatepass['received_by']) ?></strong> on <?= date('F d, Y', strtotime($gatepass['released_at'])) ?>. Keep your sticker affixed to your windshield or front fork at all times.</p>
        </div>
    <?php endif; ?>

    <!-- Printable Official Gate Pass Certificate / Badge -->
    <div class="gatepass-certificate-wrap" style="max-width: 800px; margin: 0 auto;">
        <?php
        $validTimestamp = !empty($gatepass['valid_until']) ? strtotime($gatepass['valid_until']) : time();
        $validMonth = strtoupper(date('M', $validTimestamp));
        $validDay = date('d,', $validTimestamp);
        $validYear = date('Y', $validTimestamp);
        $formattedStickerNumber = preg_replace('/\s*-\s*/', ' - ', $gatepass['sticker_number'] ?? '');
        ?>
        <div class="physical-sticker-card <?= ($gatepass['applicant_type'] ?? '') === 'employee' ? 'sticker-employee' : 'sticker-student' ?>" style="margin-bottom: 25px;">
            <div class="sticker-header">
                <div class="sticker-brand-wrap">
                    <img src="<?= BASE_URL ?>/assets/images/PSU%20LOGO.png" alt="University Seal" class="sticker-emblem">
                    <div class="sticker-header-text">
                        <div class="sticker-univ-title">PARTIDO STATE UNIVERSITY</div>
                        <div class="sticker-tagline">OFFICIAL CAMPUS VEHICLE GATE PASS</div>
                    </div>
                </div>
                <div class="sticker-type-pill">
                    <?= strtoupper(e($gatepass['applicant_type'])) ?>
                </div>
            </div>

            <div class="sticker-body">
                <div class="sticker-number-banner">
                    <?= e($formattedStickerNumber) ?>
                </div>

                <div class="sticker-plate-box">
                    <div class="plate-label">VEHICLE PLATE / MV FILE:</div>
                    <div class="plate-code"><?= e($gatepass['plate_number']) ?></div>
                    <div class="plate-type"><?= e($gatepass['vehicle_type']) ?> &bull; <?= e($gatepass['color']) ?></div>
                </div>

                <div class="sticker-holder-info">
                    <div class="holder-name"><?= e(trim($gatepass['first_name'] . ' ' . $gatepass['last_name'])) ?></div>
                    <div class="holder-sub"><?= e($gatepass['school_id']) ?> &bull; <?= e($gatepass['department']) ?></div>
                </div>
            </div>

            <div class="sticker-footer">
                <div class="sticker-validity-col">
                    <div class="valid-label">VALID UNTIL:</div>
                    <div class="valid-date-lines">
                        <span class="valid-month"><?= $validMonth ?> </span><span class="valid-day"><?= $validDay ?></span>
                        <div class="valid-year"><?= $validYear ?></div>
                    </div>
                </div>
                <div class="sticker-barcode-wrap">
                    <svg class="sticker-barcode-svg" viewBox="0 0 300 52" width="280" height="48" xmlns="http://www.w3.org/2000/svg">
                        <rect x="0" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="8" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="16" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="38" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="60" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="68" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="76" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="84" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="106" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="128" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="136" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="158" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="166" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="174" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="182" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="190" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="212" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="234" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="242" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="250" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="258" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="266" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="288" y="0" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="0" y="28" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="8" y="28" width="4.5" height="22" rx="1.5" fill="#000035" />
                        <rect x="16" y="28" width="4.5" height="22" rx="1.5" fill="#000035" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Official Gate Pass Authority Document -->
        <div class="card certificate-sheet" style="padding: 30px; background: #fff; border: 2px solid #000035;">
            <div class="certificate-header text-center" style="border-bottom: 2px solid #c69214; padding-bottom: 15px; margin-bottom: 20px;">
                <img src="<?= BASE_URL ?>/assets/images/PSU%20LOGO.png" alt="Partido State University Logo" style="height: 72px; width: auto; object-fit: contain; display: block; margin: 0 auto 8px;">
                <h2 style="color: #000035; margin: 0; font-size: 1.4rem;"><?= strtoupper(e(UNIVERSITY_NAME)) ?></h2>
                <h4 style="color: #555; margin: 5px 0 0 0; font-weight: normal;">Office of Campus Safety and Security Services</h4>
                <h3 style="color: #c69214; margin-top: 10px; font-size: 1.2rem; letter-spacing: 2px;">CERTIFICATE OF VEHICLE GATE PASS REGISTRATION</h3>
            </div>

            <p style="text-align: justify; line-height: 1.8;">
                This certifies that the motor vehicle described below is officially authorized to enter, transit, and park within university premises in compliance with Campus Traffic and Safety Regulations:
            </p>

            <table class="table table-bordered" style="margin: 20px 0;">
                <tbody>
                    <tr>
                        <th style="width: 30%;">Gate Pass Sticker No.:</th>
                        <td class="font-bold text-primary" style="font-size: 1.15rem;"><?= e($gatepass['sticker_number']) ?></td>
                        <th style="width: 25%;">Validity Period:</th>
                        <td class="font-bold text-success"><?= date('F d, Y', strtotime($gatepass['issue_date'])) ?> to <?= date('F d, Y', strtotime($gatepass['valid_until'])) ?></td>
                    </tr>
                    <tr>
                        <th>Registered Driver / Owner:</th>
                        <td class="font-bold"><?= e(trim($gatepass['first_name'] . ' ' . $gatepass['middle_name'] . ' ' . $gatepass['last_name'])) ?></td>
                        <th>School / Employee ID:</th>
                        <td><?= e($gatepass['school_id']) ?></td>
                    </tr>
                    <tr>
                        <th>Vehicle Classification:</th>
                        <td><?= e($gatepass['vehicle_type']) ?> (<?= e($gatepass['year_model']) ?>)</td>
                        <th>Plate Number:</th>
                        <td class="font-bold"><?= e($gatepass['plate_number']) ?></td>
                    </tr>
                    <tr>
                        <th>College / Department:</th>
                        <td><?= e($gatepass['department']) ?></td>
                        <th>Driver's License No.:</th>
                        <td><?= e($gatepass['license_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Cashier Receipt (PSU-OR):</th>
                        <td>#<?= e($gatepass['psu_or_number'] ?? 'N/A') ?></td>
                        <th>Amount Paid:</th>
                        <td><?= formatCurrency($gatepass['amount'] ?? 0) ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="certificate-regulations" style="font-size: 0.85rem; color: #555; margin-top: 20px; line-height: 1.6;">
                <strong>Campus Traffic Regulations:</strong>
                <ol style="padding-left: 20px; margin-top: 5px;">
                    <li>Observe university speed limit of 20 km/h at all times.</li>
                    <li>Park only in designated parking slots; no parking on pedestrian walkways or yellow boxes.</li>
                    <li>Sticker is strictly non-transferable to other vehicles.</li>
                    <li>Drivers must carry their valid LTO Driver's License and university identification while on campus.</li>
                </ol>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

