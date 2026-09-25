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

// Dapatkan maklumat admin
$username = $_SESSION["username"];
$sql = "SELECT * FROM users WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Klinik Pergigian Diyana - Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/settings.css?v=20260915">
    <style>
        html, body { background: #AFEEEE !important; background-image: none !important; }
        body::before { display: none !important; content: none !important; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
        <h4>🦷 KLINIK PERGIGIAN DIYANA - SETTINGS</h4>
    </div>
    <a href="../index.php" class="btn btn-light">Logout</a>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo-box">
        <img src="../img/logo.jpeg" alt="Clinic Logo">
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard_admin.php">Dashboard</a>
        <a class="nav-link" href="admin_appointments.php">Appointments</a>
        <a class="nav-link" href="patient_list.php">Patient List</a>
        <a class="nav-link" href="register_patient.php">Register Patient</a>
        <a class="nav-link" href="reports.php">Reports</a>
        <a class="nav-link active" href="settings.php">Settings</a>
    </nav>
</div>
<div class="container-fluid flex-grow-1">
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-12 content" id="content">
            <div class="card p-4">
                <h5 class="mb-4">Settings</h5>
                <div class="text-center mb-3">
                    <div class="edit-icon">✎</div>
                    <button class="btn btn-outline-secondary btn-sm">Edit</button>
                </div>

                <form method="POST" action="../asset/update_settings.php" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? "") ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control" placeholder="Ali Bin Abu" value="<?= htmlspecialchars($user['fullname'] ?? "") ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="ali@example.com" value="<?= htmlspecialchars($user['email'] ?? "") ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone No</label>
                        <input type="text" name="phone" class="form-control" placeholder="012-3456789" value="<?= htmlspecialchars($user['phone'] ?? "") ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
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
