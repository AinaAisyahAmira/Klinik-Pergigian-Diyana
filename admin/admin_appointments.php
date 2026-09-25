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

// Ambil semua appointment dari DB termasuk notes
$sql = "SELECT a.id, u.username AS customer, a.treatment, a.date, a.time, a.dentist, a.status, a.notes
        FROM appointments a
        JOIN users u ON a.customer_id = u.id
        ORDER BY a.date DESC, a.time DESC";
$result = $conn->query($sql);
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';
$notifyMessage = isset($_GET['notify']) && $_GET['notify'] === '1'
    ? 'Appointment status and notes updated successfully. Patient notification was prepared for email/WhatsApp when available.'
    : 'Appointment status and notes updated successfully.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_appointment.css">
    <link rel="stylesheet" href="../css/dashboard_admin.css">
</head>
<body>

<div class="header">
    <div class="header-left">
        <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
        <h3>🗓 Appointment Management</h3>
    </div>
    <a href="../index.php" class="btn btn-light">Logout</a>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo-box"><img src="../img/logo.jpeg" alt="Clinic Logo"></div>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard_admin.php">Dashboard</a>
        <a class="nav-link active" href="admin_appointments.php">Appointments</a>
        <a class="nav-link" href="patient_list.php">Patient List</a>
        <a class="nav-link" href="register_patient.php">Register Patient</a>
        <a class="nav-link" href="reports.php">Reports</a>
        <a class="nav-link" href="settings.php">Settings</a>
    </nav>
</div>
<div class="container-fluid flex-grow-1">
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-12 content" id="content">
            <div class="card p-4">
                <h5 class="mb-4">All Appointments</h5>
                <?php if ($updated): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($notifyMessage) ?></div>
                <?php endif; ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Treatment</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Dentist</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row["customer"] ?? "") ?></td>
                                    <td><?= htmlspecialchars($row["treatment"] ?? "") ?></td>
                                    <td>
                                        <?= !empty($row["date"]) ? htmlspecialchars(date("d/m/Y", strtotime($row["date"]))) : "" ?>
                                    </td>
                                    <td><?= $row["time"] ?? "" ?></td>
                                    <td><?= htmlspecialchars($row["dentist"] ?? "") ?></td>
                                    <?php
                                        $status = trim((string)($row["status"] ?? ""));
                                        $statusClass = strtolower($status);
                                        $allowedStatusClasses = ["approved", "pending", "cancelled", "done"];
                                        if (!in_array($statusClass, $allowedStatusClasses, true)) {
                                            $statusClass = "unknown";
                                        }
                                        $statusStyles = [
                                            "approved" => "background:#d9f2df;color:#207a39;",
                                            "pending" => "background:#fff0cf;color:#a56500;",
                                            "cancelled" => "background:#fbdada;color:#b52b2b;",
                                            "done" => "background:#d9eaff;color:#1e5fa8;",
                                            "unknown" => "background:#e9ecef;color:#495057;"
                                        ];
                                    ?>
                                    <td class="status-cell" style="padding:0;<?= $statusStyles[$statusClass] ?>">
                                        <span class="status-badge status-<?= $statusClass ?>" style="<?= $statusStyles[$statusClass] ?>display:flex;width:100%;min-height:42px;align-items:center;justify-content:center;box-sizing:border-box;">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row["notes"] ?? "") ?></td>
                                    <td>
                                        <a href="edit_appointment.php?id=<?= $row["id"] ?>" class="btn btn-success btn-sm">Edit</a>
                                        <a href="delete_appointment.php?id=<?= $row["id"] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this appointment?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No appointments found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
