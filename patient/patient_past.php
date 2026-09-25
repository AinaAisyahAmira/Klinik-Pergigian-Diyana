<?php
session_start();
include("../config.php");

$loggedIn = $_SESSION["loggedin"] ?? false;
$username = $_SESSION["username"] ?? "";
$role = $_SESSION["role"] ?? "";

if (($loggedIn !== true && $username === "") || $role !== "customer") {
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

// Dapatkan ID customer
$sqlUser = "SELECT id FROM users WHERE username=?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("s", $username);
$stmtUser->execute();

$resultUser = $stmtUser->get_result()->fetch_assoc();

if (!$resultUser) {
    header("Location: ../login.php");
    exit;
}

$customer_id = $resultUser["id"];

$notificationEntries = [];
$notificationStmt = $conn->prepare("SELECT date, time, treatment, status, notes
    FROM appointments
    WHERE customer_id=? AND TRIM(COALESCE(notes, '')) <> ''
    ORDER BY date DESC, time DESC, id DESC
    LIMIT 5");
if ($notificationStmt) {
    $notificationStmt->bind_param("i", $customer_id);
    $notificationStmt->execute();
    $notificationResult = $notificationStmt->get_result();
    while ($row = $notificationResult->fetch_assoc()) {
        $notificationEntries[] = $row;
    }
    $notificationStmt->close();
}

// Ambil semua appointment customer
$sqlApp = "SELECT date, time, treatment, dentist, status
           FROM appointments
           WHERE customer_id=?
           ORDER BY date DESC, time DESC";

$stmtApp = $conn->prepare($sqlApp);
$stmtApp->bind_param("i", $customer_id);
$stmtApp->execute();

$resultApp = $stmtApp->get_result();
$upcomingAppointments = [];
$pastAppointments = [];

while ($appointment = $resultApp->fetch_assoc()) {
    if ($appointment["date"] >= date("Y-m-d")) {
        $upcomingAppointments[] = $appointment;
    } else {
        $pastAppointments[] = $appointment;
    }
}

function renderPatientAppointment($appointment) {
    $status = trim((string)($appointment["status"] ?? ""));
    $statusClass = strtolower($status);
    $allowedStatusClasses = ["approved", "pending", "cancelled", "done"];
    if (!in_array($statusClass, $allowedStatusClasses, true)) {
        $statusClass = "unknown";
    }
    ?>
    <div class="appointment-card">
        <div class="appointment-info">
            <h6>Dental Appointment</h6>
            <p><strong>Date:</strong> <?= htmlspecialchars(date("d/m/Y", strtotime($appointment["date"]))) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars($appointment["time"]) ?></p>
            <p><strong>Treatment:</strong> <?= htmlspecialchars($appointment["treatment"]) ?></p>
            <p><strong>Dentist:</strong> <?= htmlspecialchars($appointment["dentist"]) ?></p>
            <p><strong>Status:</strong> <span class="status-badge status-<?= $statusClass ?>"><?= htmlspecialchars($status) ?></span></p>
        </div>
    </div>
    <?php
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Records</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Guna CSS yang sama dengan Dashboard -->
    <link
        rel="stylesheet"
        href="../css/dashboard_patient.css"
    >
    <link
        rel="stylesheet"
        href="../css/patient_past.css?v=20260924"
    >

</head>


<body>


<!-- ================================================= -->
<!-- HEADER                                            -->
<!-- ================================================= -->

<div class="header">


    <div class="header-left">


        <!-- Menu Button -->

        <span
            class="menu-toggle"
            onclick="toggleSidebar()"
        >
            ☰
        </span>


        <!-- Title -->

        <h4>
            🦷 KLINIK PERGIGIAN DIYANA - PATIENT
        </h4>


    </div>


    <!-- Notifications -->

    <div class="notification-panel">
        <details>
            <summary>
                🔔 <span>Notifications</span>
                <?php if (!empty($notificationEntries)): ?>
                    <span class="notification-badge"><?= count($notificationEntries) ?></span>
                <?php endif; ?>
            </summary>
            <div class="notification-menu">
                <?php if (!empty($notificationEntries)): ?>
                    <?php foreach ($notificationEntries as $note): ?>
                        <div class="notification-item">
                            <div class="notification-title"><?= htmlspecialchars($note['status'] ?? 'Update') ?></div>
                            <div class="notification-text"><?= htmlspecialchars($note['notes']) ?></div>
                            <div class="notification-meta"><?= htmlspecialchars($note['date']) ?> • <?= htmlspecialchars($note['time']) ?> • <?= htmlspecialchars($note['treatment']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notification-empty">No doctor notifications yet.</div>
                <?php endif; ?>
            </div>
        </details>
    </div>

    <!-- Logout -->

    <a
        href="../index.php"
        class="btn btn-light"
    >
        Logout
    </a>


</div>



<!-- ================================================= -->
<!-- SIDEBAR                                           -->
<!-- ================================================= -->

<div
    class="sidebar"
    id="sidebar"
>


    <!-- Logo -->

    <div class="logo-box">

        <img
            src="../img/logo.jpeg"
            alt="Clinic Logo"
        >

    </div>


    <!-- Menu -->

    <nav class="nav flex-column">


        <a
            class="nav-link"
            href="dashboard_patient.php"
        >
            Dashboard
        </a>


        <a
            class="nav-link"
            href="our_dentist.php"
        >
            Our Dentists
        </a>


        <a
            class="nav-link active"
            href="patient_past.php"
        >
            My Records
        </a>


        <a
            class="nav-link"
            href="patient_feedback.php"
        >
            Feedback
        </a>


        <a
            class="nav-link"
            href="patient_profile.php"
        >
            Settings
        </a>


    </nav>


</div>



<!-- ================================================= -->
<!-- MAIN CONTENT                                      -->
<!-- ================================================= -->

<div class="container-fluid flex-grow-1">


    <div class="row">


        <div
            class="col-md-12 content"
            id="content"
        >


            <!-- Page Title -->

            <div class="card mb-4">


                <div class="card-body">


                    <h5>
                        My Records
                    </h5>


                    <p class="mb-0">
                        View your dental appointment history.
                    </p>


                </div>


            </div>



            <div class="card mb-4 records-card">
                <div class="card-body">
                    <h6 class="appointment-title">Appointment Records</h6>

                    <div class="record-tabs" role="tablist">
                        <button type="button" class="record-tab active" data-target="upcomingRecords">Recent Appointments</button>
                        <button type="button" class="record-tab" data-target="pastRecords">Past Appointments</button>
                    </div>

                    <div id="upcomingRecords" class="record-panel active">
                        <?php if (empty($upcomingAppointments)): ?>
                            <div class="empty-card">
                                <h6>No recent appointments</h6>
                                <p>You do not have any upcoming appointments.</p>
                            </div>
                        <?php else: ?>
                            <div class="appointment-container">
                                <?php foreach ($upcomingAppointments as $appointment): renderPatientAppointment($appointment); endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="pastRecords" class="record-panel">
                        <?php if (empty($pastAppointments)): ?>
                            <div class="empty-card">
                                <h6>No past appointments</h6>
                                <p>Your completed appointment history will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="appointment-container">
                                <?php foreach ($pastAppointments as $appointment): renderPatientAppointment($appointment); endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        </div>


    </div>


</div>



<!-- ================================================= -->
<!-- FOOTER                                            -->
<!-- ================================================= -->

<div class="footer">

    Copyright © 2026 Klinik Pergigian Diyana — All Rights Reserved

</div>



<!-- ================================================= -->
<!-- SIDEBAR JAVASCRIPT                                -->
<!-- ================================================= -->

<script>

function toggleSidebar() {

    var sidebar = document.getElementById("sidebar");

    var content = document.getElementById("content");


    sidebar.classList.toggle("show");

    content.classList.toggle("shift");

}

document.querySelectorAll(".record-tab").forEach(function (tab) {
    tab.addEventListener("click", function () {
        document.querySelectorAll(".record-tab").forEach(function (item) {
            item.classList.remove("active");
        });
        document.querySelectorAll(".record-panel").forEach(function (panel) {
            panel.classList.remove("active");
        });

        tab.classList.add("active");
        document.getElementById(tab.dataset.target).classList.add("active");
    });
});

</script>



<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
