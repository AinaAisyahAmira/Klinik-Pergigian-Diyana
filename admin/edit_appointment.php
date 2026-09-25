<?php
session_start();
include("../config.php");

$loggedIn = $_SESSION["loggedin"] ?? false;
$username = $_SESSION["username"] ?? "";
$role = $_SESSION["role"] ?? "";

if (($loggedIn !== true && $username === "") || $role !== "admin") {
    header("Location: ../login.php");
    exit;
}

if ($username === "") {
    $userId = $_SESSION["user_id"] ?? null;
    if (!$userId) {
        header("Location: ../login.php");
        exit;
    }

    $userStmt = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if (!$userRow) {
        header("Location: ../login.php");
        exit;
    }

    $username = $userRow["username"];
    $_SESSION["username"] = $username;
}

$referrerPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename((string)$referrerPath) === 'edit_appointment.php') {
    header("Location: admin_appointments.php");
    exit;
}

// Bug fix: $_GET['id'] was concatenated straight into the SQL string below —
// classic SQL injection (e.g. ?id=1 OR 1=1). Cast to int and use a prepared
// statement instead.
$id = (int)($_GET['id'] ?? 0);
if ($id < 0) {
    die("Invalid appointment.");
}

// Ambil data appointment + nama customer
$stmt = $conn->prepare("
    SELECT a.*, u.username AS customer_name 
    FROM appointments a 
    JOIN users u ON a.customer_id = u.id 
    WHERE a.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    die("Appointment not found!");
}

// Senarai rawatan (boleh tambah ikut keperluan)
$treatments = ["Scaling", "Filling", "Extraction","Braces","Whitening", "Pediatric Dentistry"];

// Senarai doktor (boleh tarik dari table users kalau nak dinamik)
$dentists = ["Dr Nur Diyana","Dr Huda Sofiah","Dr. Afif Azmi", "Dr. Mohd Ghazlan", "Dr. Marina Shanthini"];

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $treatment = trim($_POST['treatment'] ?? '');
    $date      = trim($_POST['date'] ?? '');
    $time      = trim($_POST['time'] ?? '');
    $dentist   = trim($_POST['dentist'] ?? '');
    $status    = trim($_POST['status'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    // Bug fix: validate against the known option lists / basic formats
    // instead of trusting POST data outright.
    if (!in_array($treatment, $treatments, true)) $errors[] = "Invalid treatment.";
    if (!in_array($dentist, $dentists, true)) $errors[] = "Invalid dentist.";
    if (!in_array($status, ["Pending", "Approved", "Done", "Cancelled"], true)) $errors[] = "Invalid status.";
    if (!DateTime::createFromFormat('Y-m-d', $date)) $errors[] = "Invalid date.";
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) $errors[] = "Invalid time.";

    if (empty($errors)) {
        // Bug fix: this UPDATE previously interpolated raw POST values
        // straight into the query string — a prepared statement closes
        // that off entirely.
        $stmtUpdate = $conn->prepare("
            UPDATE appointments SET 
              treatment = ?, 
              date = ?, 
              time = ?, 
              dentist = ?, 
              status = ?,
              notes = ?
            WHERE id = ?
        ");
        $stmtUpdate->bind_param("ssssssi", $treatment, $date, $time, $dentist, $status, $notes, $id);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $notificationResult = sendAppointmentNotificationToPatient(
            $conn,
            (int)($row['customer_id'] ?? 0),
            $status,
            $treatment,
            $date,
            $time,
            $notes
        );

        $notifyText = "Appointment status and notes updated successfully.";
        if (!empty($notificationResult['email']) || !empty($notificationResult['whatsapp_url'])) {
            $notifyText .= " Patient notification was prepared for email/WhatsApp when available.";
        }

        header("Location: admin_appointments.php?updated=1&notify=1");
        exit;
    }

    // Keep the row array in sync with what the admin just typed, so the
    // re-rendered form below reflects their edits rather than the old DB row.
    $row = array_merge($row, compact('treatment', 'date', 'time', 'dentist', 'status', 'notes'));
}

$statusMeta = [
    "Pending"   => ["color" => "#c48a2e", "bg" => "#fbf1e0", "icon" => "bi-hourglass-split"],
  "Approved"  => ["color" => "#2878a8", "bg" => "#e7f3fb", "icon" => "bi-check-circle"],
    "Done"      => ["color" => "#2f7a6b", "bg" => "#e6f2ef", "icon" => "bi-check-circle"],
    "Cancelled" => ["color" => "#b8443c", "bg" => "#fbeae9", "icon" => "bi-x-circle"],
];
$currentStatus = $row['status'] ?? 'Pending';
$stripeColor = $statusMeta[$currentStatus]['color'] ?? '#2f7a6b';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Appointment · Klinik Pergigian Diyana</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Old+Standard+TT:wght@400;700&family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin_appointment.css">
  <link rel="stylesheet" href="../css/dashboard_admin.css">

  <style>
    :root {
      --teal-deep: #2f7a6b;
      --teal-soft: #e6f2ef;
      --gold: #b08d57;
      --ink: #22313a;
      --line: #e2e7e6;
      --canvas: #f5f7f6;
    }

    body {
      background: var(--canvas);
      color: var(--ink);
      font-family: "Segoe UI", -apple-system, sans-serif;
      min-height: 100vh;
    }

    .topbar {
      background: #fff;
      border-bottom: 1px solid var(--line);
      padding: 18px 32px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .topbar .mark {
      width: 34px; height: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--gold), #d9c08f);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-family: "Old Standard TT", serif; font-weight: 700;
    }
    .topbar .clinic-name {
      font-family: "Old Standard TT", serif;
      font-size: 1.05rem;
      letter-spacing: 0.02em;
    }
    .topbar .clinic-sub {
      font-size: 0.72rem;
      color: #8a9490;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .page-wrap {
      max-width: 720px;
      margin: 0 auto;
      padding: 40px 20px 64px;
    }

    .eyebrow {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--teal-deep);
      font-weight: 600;
      margin-bottom: 6px;
    }
    h1.page-title {
      font-family: "Old Standard TT", serif;
      font-size: 1.9rem;
      margin-bottom: 28px;
    }

    .card-panel {
      background: #fff;
      border-radius: 14px;
      border: 1px solid var(--line);
      box-shadow: 0 6px 24px rgba(34,49,58,0.06);
      overflow: hidden;
      position: relative;
    }
    /* Signature element: a status-colored edge on the card, so the current
      state (Pending/Approved/Done/Cancelled) reads at a glance before you read
       a word of the form. */
    .card-panel::before {
      content: "";
      position: absolute;
      top: 0; left: 0; bottom: 0;
      width: 5px;
      background: <?= htmlspecialchars($stripeColor) ?>;
    }

    .card-panel .inner { padding: 32px 32px 28px 36px; }

    .status-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .field-label {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #6b7876;
      font-weight: 600;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .field-label i { color: var(--teal-deep); }

    .form-control, .form-select {
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 10px 12px;
      font-size: 0.95rem;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--teal-deep);
      box-shadow: 0 0 0 3px rgba(47,122,107,0.12);
    }
    .form-control[readonly] {
      background: #f5f7f6;
      color: #6b7876;
    }

    .section-divider {
      border-top: 1px solid var(--line);
      margin: 24px 0;
    }

    .btn-clinic-primary {
      background: var(--teal-deep);
      border-color: var(--teal-deep);
      color: #fff;
      font-weight: 600;
      padding: 10px 22px;
      border-radius: 8px;
    }
    .btn-clinic-primary:hover { background: #26645a; border-color: #26645a; color: #fff; }

    .btn-clinic-secondary {
      background: #fff;
      border: 1px solid var(--line);
      color: var(--ink);
      font-weight: 500;
      padding: 10px 22px;
      border-radius: 8px;
    }
    .btn-clinic-secondary:hover { background: #f5f7f6; color: var(--ink); }

    .error-box {
      background: #fbeae9;
      border: 1px solid #f1c7c3;
      color: #8a2e26;
      border-radius: 8px;
      padding: 12px 16px;
      font-size: 0.9rem;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

  <div class="header">
    <div class="header-left">
        <h3>Edit Appointment</h3>
    </div>
    <a href="../index.php" class="btn btn-light">Logout</a>
</div>

  <div class="page-wrap">
    <div class="eyebrow">Appointment #<?= (int)$row['id'] ?></div>
    <h1 class="page-title">Edit Appointment</h1>

    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= htmlspecialchars(implode(' ', $errors)) ?>
      </div>
    <?php endif; ?>

    <div class="card-panel">
      <div class="inner">

        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <div class="field-label mb-1"><i class="bi bi-person"></i> Patient</div>
            <div class="fw-semibold fs-5"><?= htmlspecialchars($row['customer_name']) ?></div>
          </div>
          <?php $meta = $statusMeta[$currentStatus] ?? $statusMeta['Pending']; ?>
          <span class="status-chip" style="background:<?= htmlspecialchars($meta['bg']) ?>; color:<?= htmlspecialchars($meta['color']) ?>;">
            <i class="bi <?= htmlspecialchars($meta['icon']) ?>"></i> <?= htmlspecialchars($currentStatus) ?>
          </span>
        </div>

        <div class="section-divider"></div>

        <form method="POST">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label"><i class="bi bi-heart-pulse"></i> Treatment</label>
              <select name="treatment" class="form-select" required>
                <?php foreach ($treatments as $t): ?>
                  <option value="<?= htmlspecialchars($t) ?>" <?= ($row['treatment'] == $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="field-label"><i class="bi bi-person-badge"></i> Dentist</label>
              <select name="dentist" class="form-select" required>
                <?php foreach ($dentists as $d): ?>
                  <option value="<?= htmlspecialchars($d) ?>" <?= ($row['dentist'] == $d) ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="field-label"><i class="bi bi-calendar3"></i> Date</label>
              <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($row['date']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="field-label"><i class="bi bi-clock"></i> Time</label>
              <input type="time" name="time" class="form-control" value="<?= htmlspecialchars(substr($row['time'], 0, 5)) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="field-label"><i class="bi bi-flag"></i> Status</label>
              <select name="status" class="form-select">
                <?php foreach (["Pending", "Approved", "Done", "Cancelled"] as $s): ?>
                  <option value="<?= $s ?>" <?= ($currentStatus == $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="field-label"><i class="bi bi-journal-text"></i> Notes</label>
              <textarea name="notes" class="form-control" rows="4" placeholder="Add any notes for this appointment…"><?= htmlspecialchars($row['notes'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="section-divider"></div>

          <div class="d-flex justify-content-end gap-2">
            <a href="admin_appointments.php" class="btn btn-clinic-secondary">Cancel</a>
            <button type="submit" class="btn btn-clinic-primary">
              <i class="bi bi-check2 me-1"></i> Save changes
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

</body>
</html>