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

// Dapatkan nama doktor dari URL (jika ada)
$selectedDentist = isset($_GET['dentist']) ? $_GET['dentist'] : null;

// Kalau tiada doktor dipilih, pilih doktor random berdasarkan username
if (!$selectedDentist) {
    $sqlDentist = "SELECT username FROM users WHERE role='doctor' ORDER BY RAND() LIMIT 1";
    $resultDentist = $conn->query($sqlDentist);

    if ($resultDentist && $resultDentist->num_rows > 0) {
        $selectedDentist = $resultDentist->fetch_assoc()['username'];
    } else {
        $selectedDentist = "";
    }
}

// Jana slot klinik kerana jadual dentist_schedule tidak wujud dalam schema.
$bookedSlots = [];
$sqlBooked = "SELECT date, time FROM appointments
              WHERE dentist=? AND date >= CURDATE() AND status IN ('Pending', 'Approved', 'Done')";
$stmtBooked = $conn->prepare($sqlBooked);
if ($stmtBooked) {
    $stmtBooked->bind_param("s", $selectedDentist);
    $stmtBooked->execute();
    $resultBooked = $stmtBooked->get_result();
    while ($row = $resultBooked->fetch_assoc()) {
        $bookedSlots[$row['date'] . ' ' . $row['time']] = true;
    }
    $stmtBooked->close();
}

$schedule = [];
for ($dayOffset = 0; $dayOffset < 30; $dayOffset++) {
    $date = date('Y-m-d', strtotime("+$dayOffset days"));
    if ((int)date('N', strtotime($date)) > 5) {
        continue;
    }
    for ($hour = 9; $hour <= 16; $hour++) {
        foreach (['00:00:00', '30:00'] as $minute) {
            $time = sprintf('%02d:%s', $hour, $minute);
            if (!isset($bookedSlots[$date . ' ' . $time])) {
                $schedule[] = ['date' => $date, 'time' => $time];
            }
        }
    }
}

// Bila customer submit appointment
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $date = $_POST["date"];
    $time = $_POST["time"];
    $treatment = $_POST["treatment"];
    $dentist = $_POST["dentist"];

    // Dapatkan ID customer
    $sqlUser = "SELECT id FROM users WHERE username=?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("s", $_SESSION["username"]);
    $stmtUser->execute();
    $customer_id = $stmtUser->get_result()->fetch_assoc()["id"];

    // Simpan appointment
    $sqlInsert = "INSERT INTO appointments (customer_id, dentist, date, time, treatment, status) 
                  VALUES (?, ?, ?, ?, ?, 'Pending')";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("issss", $customer_id, $dentist, $date, $time, $treatment);
    $stmtInsert->execute();

    echo "<script>alert('Appointment booked successfully!'); window.location='dashboard_patient.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Klinik Pergigian Diyana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/our_doctor.css">
    <link rel="stylesheet" href="../css/sidebar_patient.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
</head>
<body>

<div class="header">
    <div class="header-title">
        <h4>KLINIK PERGIGIAN DIYANA</h4>
        <h6 class="text-muted">PATIENT CONSOLE</h6>
    </div>
     <div class="notification-panel">
        <details>
            <summary>
                🔔 <span>Notifications</span>
                <?php
                $notificationEntries = [];
                $notificationStmt = $conn->prepare("SELECT date, time, treatment, status, notes
                    FROM appointments
                    WHERE customer_id = ? AND TRIM(COALESCE(notes, '')) <> ''
                    ORDER BY date DESC, time DESC, id DESC
                    LIMIT 5");
                if ($notificationStmt) {
                    $notificationStmt->bind_param("i", $_SESSION['user_id']);
                    $notificationStmt->execute();
                    $notificationResult = $notificationStmt->get_result();
                    while ($row = $notificationResult->fetch_assoc()) {
                        $notificationEntries[] = $row;
                    }
                    $notificationStmt->close();
                }
                ?>
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
</div>

<style>
.notification-panel { display:flex; align-items:center; }
.notification-panel details { position:relative; }
.notification-panel summary {
    list-style:none; cursor:pointer; display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.18);
    color:#fff; border-radius:999px; padding:10px 14px; font-weight:600; border:1px solid rgba(255,255,255,0.4);
}
.notification-panel summary::-webkit-details-marker { display:none; }
.notification-badge { background:#ffeb3b; color:#1f2937; border-radius:999px; padding:2px 7px; font-size:12px; font-weight:700; }
.notification-menu { position:absolute; right:0; top:calc(100% + 12px); width:340px; max-height:300px; overflow-y:auto; background:#fff; border-radius:14px; box-shadow:0 16px 28px rgba(0,0,0,0.15); padding:10px; z-index:50; }
.notification-item { border-bottom:1px solid #eef2f7; padding:10px 8px; }
.notification-item:last-child { border-bottom:none; }
.notification-title { font-weight:700; color:#0f172a; margin-bottom:4px; }
.notification-text { color:#475569; font-size:0.92rem; line-height:1.5; margin-bottom:5px; }
.notification-meta { color:#64748b; font-size:0.76rem; }
.notification-empty { color:#64748b; font-size:0.9rem; padding:10px 8px; }
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar">
            <div class="logo-box">
                <img src="img/logo.jpeg" alt="Clinic Logo">
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard_patient.php">Dashboard</a>
                <a class="nav-link active" href="book_appointments.php">Book Appointment</a>
                <a class="nav-link" href="our_dentist.php">Our Dentists</a>
                <a class="nav-link" href="patient_past.php">My Records</a>
                <a class="nav-link" href="patient_feedback.php">Feedback</a>
                <a class="nav-link" href="patient_profile.php">Settings</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 content">
            <h4 class="mb-4">
                Book Appointment with <?= htmlspecialchars($selectedDentist) ?>
            </h4>

            <form method="POST" class="w-50">
                <input type="hidden" name="dentist" value="<?= htmlspecialchars($selectedDentist) ?>">

                <div class="mb-3">
                    <label for="treatment" class="form-label">Treatment Type</label>
                    <select name="treatment" class="form-select" required>
                        <option value="">Select Treatment</option>
                        <option>Scaling</option>
                        <option>Filling</option>
                        <option>Extraction</option>
                        <option>Braces</option>
                        <option>Whitening</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="date" class="form-label">Select Date</label>
                    <select name="date" class="form-select" required>
                        <option value="">Select Date</option>
                        <?php
                        $datesShown = [];
                        foreach ($schedule as $row) {
                            if (!in_array($row["date"], $datesShown)) {
                                echo "<option value='{$row["date"]}'>{$row["date"]}</option>";
                                $datesShown[] = $row["date"];
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="time" class="form-label">Select Time</label>
                    <select name="time" class="form-select" required>
                        <option value="">Select Time</option>
                        <?php
                        foreach ($schedule as $row) {
                            echo "<option value='{$row["time"]}'>{$row["time"]}</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">Confirm Booking</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>