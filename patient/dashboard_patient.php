<?php
session_start();
include("../config.php");

// Semak login dengan fallback yang lebih stabil supaya refresh tidak log keluar secara tiba-tiba.
$loggedIn = $_SESSION["loggedin"] ?? false;
$userId = $_SESSION["user_id"] ?? null;
$username = $_SESSION["username"] ?? "";
$role = $_SESSION["role"] ?? "";

if (($loggedIn !== true && empty($username) && empty($userId)) || $role !== "customer") {
    header("Location: ../login.php");
    exit;
}

if ($username === "" && $userId) {
    $sqlUser = "SELECT id, username FROM users WHERE id=? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    if (!$resultUser) {
        header("Location: ../login.php");
        exit;
    }

    $username = $resultUser["username"];
    $_SESSION["username"] = $username;
} else {
    $sqlUser = "SELECT id FROM users WHERE username=? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("s", $username);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    if (!$resultUser) {
        header("Location: ../login.php");
        exit;
    }
}

$feedbackSubmitted = isset($_GET["feedback_submitted"]) && $_GET["feedback_submitted"] === "1";
$customer_id = $resultUser["id"] ?? $userId;

if (!$customer_id) {
    header("Location: ../login.php");
    exit;
}

$notificationEntries = [];
$notificationStmt = $conn->prepare("SELECT date, time, treatment, status, notes
    FROM appointments
    WHERE customer_id = ? AND TRIM(COALESCE(notes, '')) <> ''
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

$latestAppointment = null;
$sqlLatestApp = "SELECT date, time, treatment, status
                 FROM appointments
                 WHERE customer_id=?
                 ORDER BY date DESC, time DESC, id DESC
                 LIMIT 1";

$stmtLatestApp = $conn->prepare($sqlLatestApp);
$stmtLatestApp->bind_param("i", $customer_id);
$stmtLatestApp->execute();
$latestAppointment = $stmtLatestApp->get_result()->fetch_assoc();
$stmtLatestApp->close();

// Ambil 3 appointment terbaru
$sqlApp = "SELECT date, time, treatment, status
           FROM appointments
           WHERE customer_id=?
           ORDER BY date DESC, time DESC, id DESC
           LIMIT 3";

$stmtApp = $conn->prepare($sqlApp);
$stmtApp->bind_param("i", $customer_id);
$stmtApp->execute();
$resultApp = $stmtApp->get_result();

$statusPopup = null;
if ($latestAppointment && !empty(trim((string)($latestAppointment["status"] ?? "")))) {
    $latestStatus = trim((string)$latestAppointment["status"]);
    $popupConfig = [
        "pending" => [
            "title" => "Appointment is Pending",
            "message" => "Your appointment is still awaiting confirmation from the clinic.",
            "icon" => "⏳"
        ],
        "approved" => [
            "title" => "Appointment Approved",
            "message" => "Your appointment has been approved. Please attend on the scheduled date and time.",
            "icon" => "✅"
        ],
        "done" => [
            "title" => "Appointment Completed",
            "message" => "Your appointment has been completed successfully. Thank you for choosing our clinic.",
            "icon" => "🎉"
        ],
        "cancelled" => [
            "title" => "Appointment Cancelled",
            "message" => "Your appointment has been cancelled. You may book a new one whenever needed.",
            "icon" => "⚠️"
        ]
    ];

    $statusPopup = $popupConfig[strtolower($latestStatus)] ?? [
        "title" => "Appointment Status Update",
        "message" => "Your current appointment status is " . htmlspecialchars($latestStatus) . ".",
        "icon" => "ℹ️"
    ];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Patient Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../css/dashboard_patient.css">

</head>

<body>


<!-- Header -->
<div class="header">

    <div class="header-left">

        <span class="menu-toggle" onclick="toggleSidebar()">☰</span>

        <h4>🦷 KLINIK PERGIGIAN DIYANA - PATIENT</h4>

    </div>

    <div class="header-actions">
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

        <a href="../index.php" class="btn btn-light">Logout</a>
    </div>

</div>


<!-- Sidebar -->
<div class="sidebar" id="sidebar">

    <div class="logo-box">

        <img src="../img/logo.jpeg" alt="Clinic Logo">

    </div>

    <nav class="nav flex-column">

        <a class="nav-link active" href="dashboard_patient.php">
            Dashboard
        </a>

        <a class="nav-link" href="our_dentist.php">
            Our Dentists
        </a>

        <a class="nav-link" href="patient_past.php">
            My Records
        </a>

        <a class="nav-link" href="patient_feedback.php">
            Feedback
        </a>

        <a class="nav-link" href="patient_profile.php">
            Settings
        </a>

    </nav>

</div>


<!-- Main Content -->

<div class="container-fluid flex-grow-1">

    <div class="row">

        <div class="col-md-12 content" id="content">

            <?php if ($feedbackSubmitted): ?>
                <div class="alert alert-success" role="alert">
                    Thank you. Your feedback has been submitted successfully.
                </div>
            <?php endif; ?>


            <!-- Welcome Section -->

            <div class="card mb-4">

                <div class="card-body">

                    <h5>
                        Welcome, <?= htmlspecialchars($username) ?>!
                    </h5>

                    <p class="mb-0">
                        Welcome to Klinik Pergigian Diyana Patient Dashboard.
                    </p>

                </div>

            </div>


            <!-- Carousel -->

            <div id="clinicCarousel"
                 class="carousel slide mb-4"
                 data-bs-ride="carousel">


                <div class="carousel-inner">


                    <div class="carousel-item active">

                        <img src="../img/clinic1.jpeg"
                             class="d-block w-100"
                             alt="Clinic Image 1">

                    </div>


                    <div class="carousel-item">

                        <img src="../img/clinic2.jpeg"
                             class="d-block w-100"
                             alt="Clinic Image 2">

                    </div>


                    <div class="carousel-item">

                        <img src="../img/clinic3.jpeg"
                             class="d-block w-100"
                             alt="Clinic Image 3">

                    </div>


                </div>


                <button class="carousel-control-prev"
                        type="button"
                        data-bs-target="#clinicCarousel"
                        data-bs-slide="prev">

                    <span class="carousel-control-prev-icon"></span>

                </button>


                <button class="carousel-control-next"
                        type="button"
                        data-bs-target="#clinicCarousel"
                        data-bs-slide="next">

                    <span class="carousel-control-next-icon"></span>

                </button>


            </div>


            <!-- Past Appointments -->

            <div class="card mb-4">

                <div class="card-body">

                    <h6 class="appointment-title">
                        Recent Appointments
                    </h6>


                    <div class="appointment-container">

                        <?php if ($resultApp->num_rows > 0): ?>

                            <?php while($row = $resultApp->fetch_assoc()): ?>

                                <?php
                                    $appointmentStatus = trim((string)($row["status"] ?? ""));
                                    $appointmentStatusClass = strtolower($appointmentStatus);
                                    $appointmentStatusStyles = [
                                        "approved" => "background:#d9f2df;color:#207a39;",
                                        "pending" => "background:#fff0cf;color:#a56500;",
                                        "cancelled" => "background:#fbdada;color:#b52b2b;",
                                        "done" => "background:#d9eaff;color:#1e5fa8;"
                                    ];
                                    $appointmentStatusStyle = $appointmentStatusStyles[$appointmentStatusClass] ?? "background:#e9ecef;color:#495057;";
                                ?>

                                <div class="appointment-card">

                                    <h6>
                                        Dental Appointment
                                    </h6>

                                    <p>
                                        <strong>Date:</strong>
                                        <?= htmlspecialchars($row["date"]) ?>
                                    </p>

                                    <p>
                                        <strong>Time:</strong>
                                        <?= htmlspecialchars($row["time"]) ?>
                                    </p>

                                    <p>
                                        <strong>Treatment:</strong>
                                        <?= htmlspecialchars($row["treatment"]) ?>
                                    </p>

                                    <p>
                                        <strong>Status:</strong>

                                        <span class="status" style="<?= $appointmentStatusStyle ?>">
                                            <?= htmlspecialchars($appointmentStatus) ?>
                                        </span>

                                    </p>

                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <div class="appointment-card">

                                <h6>
                                    No appointments yet
                                </h6>

                                <p>
                                    You do not have any appointment records.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- Patient Menu -->

            <div class="row text-center mb-4">


                <!-- My Appointments -->

                <div class="col-md-4">

                    <div class="card top-card">

                        <div class="card-body">

                            <div class="icon">🗓</div>

                            <h6>Book Appointments</h6>

                            <p>
                                Book and manage your dental appointments.
                            </p>

                            <a href="our_dentist.php"
                               class="btn btn-info btn-sm">

                                Book Appointment

                            </a>

                        </div>

                    </div>

                </div>


                <!-- My Records -->

                <div class="col-md-4">

                    <div class="card top-card">

                        <div class="card-body">

                            <div class="icon">📋</div>

                            <h6>My Records</h6>

                            <p>
                                View your dental records.
                            </p>

                            <a href="patient_past.php"
                               class="btn btn-info btn-sm">

                                View Records

                            </a>

                        </div>

                    </div>

                </div>


                <!-- Feedback -->

                <div class="col-md-4">

                    <div class="card top-card">

                        <div class="card-body">

                            <div class="icon">💬</div>

                            <h6>Feedback</h6>

                            <p>
                                Share your feedback with us.
                            </p>

                            <a href="patient_feedback.php"
                               class="btn btn-info btn-sm">

                                Give Feedback

                            </a>

                        </div>

                    </div>

                </div>


            </div>


            <!-- Feedback -->

            <div class="card mb-4">

                <div class="card-body">

                    <h6>Give Feedback</h6>

                    <p>

                        We value your opinion!

                        <a href="patient_feedback.php"
                           class="btn btn-info btn-sm">

                            Submit Feedback

                        </a>

                    </p>

                </div>

            </div>


        </div>

    </div>

</div>


<?php if ($statusPopup && $latestAppointment): ?>
<style>
    .appointment-status-modal .modal-content {
        border: none;
        border-radius: 22px;
        overflow: hidden;
        background: linear-gradient(135deg, #ffffff 0%, #f2fbff 100%);
        box-shadow: 0 20px 40px rgba(17, 94, 110, 0.18);
    }
    .appointment-status-modal .modal-header {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        color: #fff;
        padding: 1.2rem 1.5rem 1rem;
    }
    .appointment-status-modal .modal-title {
        font-size: 1.2rem;
        letter-spacing: 0.02em;
    }
    .appointment-status-modal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
        opacity: 1;
    }
    .appointment-status-modal .modal-body {
        padding: 1.4rem 1.5rem 0.6rem;
    }
    .appointment-status-modal .status-detail-box {
        background: #f3f9fb;
        border: 1px solid #dfeef3;
        border-radius: 14px;
        padding: 1rem 1rem 0.75rem;
    }
    .appointment-status-modal .status-detail-box div {
        margin-bottom: 0.45rem;
        color: #1f2937;
        font-size: 0.97rem;
    }
    .appointment-status-modal .status-detail-box div:last-child {
        margin-bottom: 0;
    }
    .appointment-status-modal .modal-footer {
        padding: 0.8rem 1.5rem 1.3rem;
        border-top: none;
    }
    .appointment-status-modal .btn-primary {
        background: linear-gradient(135deg, #14b8a6 0%, #0ea5a5 100%);
        border: none;
        border-radius: 10px;
        padding: 0.6rem 1.25rem;
        font-weight: 600;
    }
    .appointment-status-modal .btn-primary:hover {
        background: linear-gradient(135deg, #129d92 0%, #0d8d8d 100%);
    }
</style>
<div class="modal fade appointment-status-modal" id="appointmentStatusModal" tabindex="-1" aria-labelledby="appointmentStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="appointmentStatusModalLabel">
                    <?= htmlspecialchars($statusPopup["icon"]) ?> <?= htmlspecialchars($statusPopup["title"]) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3 text-secondary"><?= htmlspecialchars($statusPopup["message"]) ?></p>
                <div class="status-detail-box">
                    <div><strong>Date:</strong> <?= htmlspecialchars($latestAppointment["date"] ?? "-") ?></div>
                    <div><strong>Time:</strong> <?= htmlspecialchars($latestAppointment["time"] ?? "-") ?></div>
                    <div><strong>Treatment:</strong> <?= htmlspecialchars($latestAppointment["treatment"] ?? "-") ?></div>
                    <div><strong>Status:</strong> <?= htmlspecialchars($latestAppointment["status"] ?? "-") ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Footer -->

<div class="footer">

    Copyright © 2026 Klinik Pergigian Diyana — All Rights Reserved

</div>


<!-- JavaScript -->

<style>
.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}
.notification-panel {
    position: relative;
    display: flex;
    align-items: center;
}
.notification-panel details {
    position: relative;
}
.notification-panel summary {
    list-style: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.18);
    color: #fff;
    border-radius: 999px;
    padding: 10px 14px;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.4);
}
.notification-panel summary::-webkit-details-marker { display: none; }
.notification-badge {
    background: #ffeb3b;
    color: #1f2937;
    border-radius: 999px;
    padding: 2px 7px;
    font-size: 12px;
    font-weight: 700;
}
.notification-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 12px);
    width: 340px;
    max-height: 300px;
    overflow-y: auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 16px 28px rgba(0,0,0,0.15);
    padding: 10px;
    z-index: 50;
}
.notification-item {
    border-bottom: 1px solid #eef2f7;
    padding: 10px 8px;
}
.notification-item:last-child { border-bottom: none; }
.notification-title {
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}
.notification-text {
    color: #475569;
    font-size: 0.92rem;
    line-height: 1.5;
    margin-bottom: 5px;
}
.notification-meta {
    color: #64748b;
    font-size: 0.76rem;
}
.notification-empty {
    color: #64748b;
    font-size: 0.9rem;
    padding: 10px 8px;
}
</style>
<script>

function toggleSidebar() {

    document.getElementById('sidebar').classList.toggle('show');

    document.getElementById('content').classList.toggle('shift');

}

<?php if ($statusPopup && $latestAppointment): ?>
document.addEventListener('DOMContentLoaded', function () {
    const appointmentModal = new bootstrap.Modal(document.getElementById('appointmentStatusModal'));
    appointmentModal.show();
});
<?php endif; ?>

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>
