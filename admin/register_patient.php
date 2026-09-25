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

// Keep patient details in a dedicated table linked to the login account.
$conn->query("CREATE TABLE IF NOT EXISTS patients (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    ic_number VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_patient_user (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

function ensureUserRegistrationColumns($conn) {
    $columnsResult = $conn->query("SHOW COLUMNS FROM users");
    $columns = [];

    if ($columnsResult) {
        while ($row = $columnsResult->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }

    $migrations = [
        'fullname' => "ALTER TABLE users ADD COLUMN fullname VARCHAR(100) NULL AFTER role",
        'ic_number' => "ALTER TABLE users ADD COLUMN ic_number VARCHAR(30) NULL AFTER fullname",
        'email' => "ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL AFTER ic_number",
        'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL AFTER email",
        'is_first_time' => "ALTER TABLE users ADD COLUMN is_first_time TINYINT(1) NOT NULL DEFAULT 1 AFTER phone"
    ];

    foreach ($migrations as $field => $sql) {
        if (!in_array($field, $columns, true)) {
            $conn->query($sql);
        }
    }
}

ensureUserRegistrationColumns($conn);

$message = "";
$messageType = "";
$temporaryPassword = "";
$generatedUsername = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $icNumber = trim($_POST["ic_number"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($fullname === "" || $icNumber === "" || $email === "" || $phone === "") {
        $message = "Sila isi semua maklumat patient.";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Sila masukkan email yang sah.";
        $messageType = "danger";
    } else {
        $check = $conn->prepare("SELECT 1 FROM patients WHERE ic_number=? OR email=? LIMIT 1");
        $check->bind_param("ss", $icNumber, $email);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $message = "Patient dengan nombor IC atau email tersebut sudah didaftarkan.";
            $messageType = "danger";
        } else {
            $prefix = preg_replace("/[^A-Za-z0-9]/", "", strtolower($fullname));
            $prefix = substr($prefix ?: "patient", 0, 30);
            $generatedUsername = $prefix;
            $usernameNumber = 2;
            $usernameCheck = $conn->prepare("SELECT 1 FROM users WHERE username=? LIMIT 1");
            while (true) {
                $usernameCheck->bind_param("s", $generatedUsername);
                $usernameCheck->execute();
                if (!$usernameCheck->get_result()->fetch_assoc()) {
                    break;
                }
                $generatedUsername = $prefix . $usernameNumber;
                $usernameNumber++;
            }
            $usernameCheck->close();
            $temporaryPassword = "P" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)) . "!";
            $storedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

            $conn->begin_transaction();
            $insert = $conn->prepare("INSERT INTO users (username, password, role, is_first_time)
                                      VALUES (?, ?, 'customer', 1)");
            $insert->bind_param("ss", $generatedUsername, $storedPassword);

            if ($insert->execute()) {
                $patientId = $insert->insert_id;
                $patient = $conn->prepare("INSERT INTO patients (patient_id, fullname, ic_number, email, phone)
                                           VALUES (?, ?, ?, ?, ?)");
                $patient->bind_param("issss", $patientId, $fullname, $icNumber, $email, $phone);
                $patientInserted = $patient->execute();
                $patient->close();
                if ($patientInserted) {
                    $conn->commit();
                    $message = "Patient berjaya didaftarkan. Simpan username dan password sementara di bawah.";
                    $messageType = "success";
                } else {
                    $conn->rollback();
                    $message = "Pendaftaran patient gagal disimpan.";
                    $messageType = "danger";
                    $temporaryPassword = "";
                    $generatedUsername = "";
                }
            } else {
                $conn->rollback();
                $message = "Pendaftaran gagal. Sila cuba lagi.";
                $messageType = "danger";
                $temporaryPassword = "";
                $generatedUsername = "";
            }
            $insert->close();
        }
    }
}

// --- Stats: today / this week / this month ---
function countPatients($conn, $sql) {
    $r = $conn->query($sql);
    return $r ? (int)$r->fetch_assoc()['total'] : 0;
}
$todayCount = countPatients($conn, "SELECT COUNT(*) AS total FROM patients WHERE DATE(created_at) = CURDATE()");
$weekCount  = countPatients($conn, "SELECT COUNT(*) AS total FROM patients WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$monthCount = countPatients($conn, "SELECT COUNT(*) AS total FROM patients WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");

// --- Registrations by hour, today (fixed 9am-5pm window) ---
$hourResult = $conn->query("SELECT HOUR(created_at) AS hr, COUNT(*) AS total FROM patients WHERE DATE(created_at) = CURDATE() GROUP BY HOUR(created_at)");
$hourMap = [];
if ($hourResult) {
    while ($row = $hourResult->fetch_assoc()) {
        $hourMap[(int)$row['hr']] = (int)$row['total'];
    }
}
$hourLabels = [];
$hourCounts = [];
for ($h = 9; $h <= 17; $h++) {
    $hourLabels[] = date("ga", mktime($h, 0, 0));
    $hourCounts[] = $hourMap[$h] ?? 0;
}

// --- Recent walk-ins (last 5) ---
$recentResult = $conn->query("SELECT fullname, created_at FROM patients ORDER BY created_at DESC LIMIT 5");
$recent = $recentResult ? $recentResult->fetch_all(MYSQLI_ASSOC) : [];

function patientInitials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) { $out .= mb_strtoupper(mb_substr($p, 0, 1)); }
    return $out ?: '?';
}
function patientTimeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff / 60) . " minutes ago";
    if ($diff < 86400) return floor($diff / 3600) . " hours ago";
    return date("d M, g:ia", strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Walk-in Patient</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/settings.css?v=20260915">
    <link rel="stylesheet" href="../css/sidebar_admin.css?v=20260924">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root{
            --teal-900:#1B4645; --teal-700:#2E6E6E; --teal-500:#4DB6AC;
            --teal-200:#BFE3DE; --mint-100:#EAF6F4; --ink:#1E3532; --ink-soft:#5C7472;
            --green:#3FA372;
        }
        body{ font-family:'Segoe UI', sans-serif; }
        h4, h5, h6, .card h5{ font-family:'Segoe UI', sans-serif; }

        .viz-section{ margin-top:24px; }
        .stat-card{
            background:#fff; border-radius:14px; padding:20px 22px;
            box-shadow:0 6px 18px rgba(27,70,69,0.07); height:100%;
        }
        .stat-card .label{ font-size:12.5px; font-weight:600; color:var(--ink-soft); font-family:'Segoe UI',sans-serif; }
        .stat-card .value{ font-family:'Segoe UI',sans-serif; font-size:32px; font-weight:600; color:var(--teal-900); }

        .panel{
            background:#fff; border-radius:14px; padding:24px 26px;
            box-shadow:0 6px 18px rgba(27,70,69,0.07); height:100%;
        }
        .panel h6{ font-family:'Segoe UI',sans-serif; font-weight:600; color:var(--teal-900); margin-bottom:16px; }

        .feed-item{ display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #EEF5F3; }
        .feed-item:last-child{ border-bottom:none; }
        .feed-avatar{
            width:34px; height:34px; border-radius:50%; background:var(--teal-200); color:var(--teal-900);
            display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; flex-shrink:0;
        }
        .feed-name{ font-size:13.5px; font-weight:600; color:var(--ink); display:block; font-family:'Segoe UI',sans-serif; }
        .feed-time{ font-size:12px; color:var(--ink-soft); }

        .sidebar{
            background:linear-gradient(180deg, #2e8b8b, #3a5f6f) !important;
            height:calc(100vh - 60px) !important;
            padding:20px !important;
            position:fixed !important;
            top:60px !important;
            left:0 !important;
            width:220px !important;
            transform:translateX(-100%);
            transition:transform .4s ease;
            z-index:1000;
        }
        .sidebar.show{ transform:translateX(0) !important; }
        .sidebar .logo-box{
            background-color:#fff !important;
            width:120px !important;
            height:120px !important;
            margin:0 auto 30px !important;
            border-radius:50% !important;
            overflow:hidden;
            box-shadow:0 4px 12px rgba(0,0,0,.3);
        }
        .sidebar .logo-box img{ width:100% !important; height:100% !important; object-fit:cover; }
        .sidebar .nav-link{
            background-color:#fff !important;
            color:#2e8b8b !important;
            margin-bottom:15px !important;
            border-radius:30px !important;
            padding:10px 15px !important;
            font-weight:600;
        }
        .sidebar .nav-link:hover{ background-color:#17a2b8 !important; color:#fff !important; }
        .sidebar .nav-link.active{ background-color:#3a5f6f !important; color:#fff !important; }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
        <h4>🦷 KLINIK PERGIGIAN DIYANA - REGISTER PATIENT</h4>
    </div>
    <a href="../index.php" class="btn btn-light">Logout</a>
</div>

<?php include('../asset/sidebar_admin.php'); ?>

<div class="container-fluid flex-grow-1">
    <div class="row">
        <main class="col-md-12 content shift" id="content">
            <div class="card p-4">
                <h5 class="mb-4">Walk-in Patient Registration</h5>
                <?php if ($message !== ""): ?>
                    <div class="alert alert-<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if ($generatedUsername !== "" && $temporaryPassword !== ""): ?>
                    <div class="alert alert-info">
                        <strong>Login sementara patient</strong><br>
                        Username: <strong><?= htmlspecialchars($generatedUsername) ?></strong><br>
                        Password: <strong><?= htmlspecialchars($temporaryPassword) ?></strong>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="fullname">Nama Patient</label>
                        <input class="form-control" id="fullname" name="fullname" placeholder="Full Name" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ic_number">Nombor IC</label>
                        <input class="form-control" id="ic_number" name="ic_number" placeholder="000000-00-0000" required value="<?= htmlspecialchars($_POST['ic_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Example@gmail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Nombor Telefon</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="000-0000000" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-primary">Daftar Patient</button>
                    </div>
                </form>
            </div>

            <div class="viz-section">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="label">Registered Today</div>
                            <div class="value"><?= $todayCount ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="label">This Week</div>
                            <div class="value"><?= $weekCount ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="label">This Month</div>
                            <div class="value"><?= $monthCount ?></div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-5">
                        <div class="panel">
                            <h6>Recent Walk-ins</h6>
                            <?php if (empty($recent)): ?>
                                <p class="text-muted" style="font-size:13px;">No patients registered yet.</p>
                            <?php else: foreach ($recent as $r): ?>
                                <div class="feed-item">
                                    <div class="feed-avatar"><?= htmlspecialchars(patientInitials($r['fullname'])) ?></div>
                                    <div>
                                        <span class="feed-name"><?= htmlspecialchars($r['fullname']) ?></span>
                                        <span class="feed-time"><?= patientTimeAgo($r['created_at']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="panel">
                            <h6>Today's Registrations by Hour</h6>
                            <canvas id="hourChart" style="max-height:220px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="footer">Copyright © 2026 Klinik Pergigian Diyana — All Rights Reserved</div>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('content').classList.toggle('shift');
}

new Chart(document.getElementById('hourChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($hourLabels) ?>,
        datasets: [{
            data: <?= json_encode($hourCounts) ?>,
            backgroundColor: '#4DB6AC',
            borderRadius: 5,
            maxBarThickness: 22
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, color: '#5C7472' }, grid: { color: '#EEF5F3' } },
            x: { ticks: { color: '#5C7472' }, grid: { display: false } }
        }
    }
});
</script>
</body>
</html>