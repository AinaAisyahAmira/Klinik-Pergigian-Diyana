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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Klinik Pergigian Diyana - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/reports.css">
</head>
<body>

<div class="header">
    <div class="header-left">
        <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
        <h4>🦷 KLINIK PERGIGIAN DIYANA - REPORTS</h4>
    </div>
    <div class="header-actions">
        <button type="button" id="printReportBtn" class="btn btn-light btn-sm">Print</button>
        <button type="button" id="downloadPdfBtn" class="btn btn-warning btn-sm">Download PDF</button>
        <a href="../index.php" class="btn btn-light btn-sm">Logout</a>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <div class="logo-box">
        <img src="../img/logo.jpeg" alt="Clinic Logo">
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard_admin.php">Dashboard</a>
        <a class="nav-link" href="admin_appointments.php">Appointments</a>
        <a class="nav-link" href="patient_list.php">Patient List</a>
        <a class="nav-link" href="register_patient.php">Register Patient</a>
        <a class="nav-link active" href="reports.php">Reports</a>
        <a class="nav-link" href="settings.php">Settings</a>
    </nav>
</div>

<div class="container-fluid flex-grow-1">
    <div class="row">
        <div class="col-md-12 content" id="content">
            <div class="card powerbi-card">
                <div class="card-body">
                    <div class="powerbi-embed-wrapper">
                        <iframe title="FYP SCAS LATEST"
                                src="https://app.powerbi.com/reportEmbed?reportId=8e29992d-dcb6-4516-9ee4-e823715defcc&autoAuth=true&ctid=221e8880-f1b1-41cd-8221-56d4277e4ffc"
                                frameborder="0"
                                allowfullscreen="true"
                                style="width:100%;height:100%;min-height:700px;border:0;">
                        </iframe>
                    </div>
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

    document.getElementById('printReportBtn')?.addEventListener('click', function () {
        window.print();
    });

    document.getElementById('downloadPdfBtn')?.addEventListener('click', function () {
        window.print();
    });
</script>
</body>
</html>
</body>
</html>