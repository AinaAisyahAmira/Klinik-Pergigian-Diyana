<?php
session_start();
include("../config.php");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

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

$today = date("Y-m-d");

// Statistik atas
$todaysAppointments = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE date='$today'")->fetch_assoc()["total"];
$pendingAppointments = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status='Pending'")->fetch_assoc()["total"];
$totalAppointments = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()["total"];
$availableDentist = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='doctor'")->fetch_assoc()["total"];

// Feedback
$feedbackData = $conn->query("SELECT rating, comment, customer_name FROM feedback ORDER BY id DESC LIMIT 3");
$avgRating = $conn->query("SELECT AVG(rating) AS avg FROM feedback")->fetch_assoc()["avg"];

// Recent activities
$activities = $conn->query("SELECT description FROM activities ORDER BY created_at DESC LIMIT 4");
$ratingPercent = min(100, max(0, (float) $avgRating / 5 * 100));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dental Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/dashboard_admin.css">
</head>
<body>

<div class="header">
    <div class="header-left">
        <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
        <h4>🦷 KLINIK PERGIGIAN DIYANA - ADMIN</h4>
    </div>
    <a href="../index.php" class="btn btn-light">Logout</a>
</div>

<div class="sidebar" id="sidebar">
    <div class="logo-box"><img src="../img/logo.jpeg" alt="Clinic Logo"></div>
    <nav class="nav flex-column">
        <a class="nav-link active" href="dashboard_admin.php">Dashboard</a>
        <a class="nav-link" href="admin_appointments.php">Appointments</a>
        <a class="nav-link" href="patient_list.php">Patient List</a>
        <a class="nav-link" href="register_patient.php">Register Patient</a>
        <a class="nav-link" href="reports.php">Reports</a>
        <a class="nav-link" href="settings.php">Settings</a>
    </nav>
</div>

<div class="container-fluid flex-grow-1">
    <div class="row">
        <div class="col-md-12 content" id="content">
            <!-- Statistik Atas -->
            <div class="row text-center mb-4">
                <div class="col-md-3"><div class="card top-card"><div class="card-body"><div class="icon">🗓</div><h6>Today's Appointments</h6><h3><?= $todaysAppointments ?></h3></div></div></div>
                <div class="col-md-3"><div class="card top-card"><div class="card-body"><div class="icon">⏳</div><h6>Pending Appointments</h6><h3><?= $pendingAppointments ?></h3></div></div></div>
                <div class="col-md-3"><div class="card top-card"><div class="card-body"><div class="icon">📊</div><h6>Total Appointments</h6><h3><?= $totalAppointments ?></h3></div></div></div>
                <div class="col-md-3"><div class="card top-card"><div class="card-body"><div class="icon">👩‍⚕️</div><h6>Available Dentist</h6><h3><?= $availableDentist ?></h3></div></div></div>
            </div>

            <!-- Feedback -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6>Patient Feedback</h6>
                    <h4><?= number_format($avgRating, 1) ?> ⭐</h4>
                    <div class="rating-bar"><div class="rating-fill" style="--rating-width: <?= $ratingPercent ?>%;"></div></div>
                    <ul class="mt-3">
                        <?php if ($feedbackData && $feedbackData->num_rows > 0): ?>
                            <?php while($fb = $feedbackData->fetch_assoc()): ?>
                                <li>
                                    <?= str_repeat("⭐", max(1, min(5, (int)round((float)$fb["rating"])))) ?>
                                    <?= htmlspecialchars($fb["comment"]) ?>
                                    — <em><?= htmlspecialchars($fb["customer_name"]) ?></em>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li>No patient feedback yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6>Recent Activities</h6>
                    <ul class="list-group">
                        <?php while($a = $activities->fetch_assoc()): ?>
                            <li class="list-group-item d-flex align-items-center">
                                <div class="avatar"><?= strtoupper(substr($a["description"],0,1)) ?></div>
                                <?= htmlspecialchars($a["description"]) ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    Copyright © 2026 Klinik Pergigian Diyana — All Rights Reserved
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('content').classList.toggle('shift');
}

</script>
</body>
</html>