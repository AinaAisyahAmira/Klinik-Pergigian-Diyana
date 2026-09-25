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

$yearFilter = isset($_GET["year"]) ? (int) $_GET["year"] : 0;
$monthFilter = isset($_GET["month"]) ? (int) $_GET["month"] : 0;
$weekFilter = isset($_GET["week"]) ? (int) $_GET["week"] : 0;

if ($yearFilter === 0) {
    $yearFilter = (int) date("Y");
}

$yearOptions = [];
$yearQuery = $conn->query("SELECT DISTINCT YEAR(date) AS year_value FROM appointments WHERE date IS NOT NULL ORDER BY year_value DESC");
if ($yearQuery) {
    while ($yearRow = $yearQuery->fetch_assoc()) {
        $yearOptions[] = (int) $yearRow["year_value"];
    }
}
if ($yearOptions === []) {
    $yearOptions[] = (int) date("Y");
}

$weekOptions = [];
$maxWeek = (int) date("W", mktime(0, 0, 0, 12, 28, $yearFilter));
for ($i = 1; $i <= max(1, $maxWeek); $i++) {
    $weekOptions[] = $i;
}

$conditions = [];
if ($yearFilter > 0) {
    $conditions[] = "YEAR(date) = " . (int) $yearFilter;
}
if ($monthFilter > 0) {
    $conditions[] = "MONTH(date) = " . (int) $monthFilter;
}
if ($weekFilter > 0) {
    $weekStart = new DateTimeImmutable();
    $weekStart = $weekStart->setISODate($yearFilter, $weekFilter, 1)->setTime(0, 0, 0);
    $weekEnd = $weekStart->modify("+6 days")->setTime(23, 59, 59);
    $conditions[] = "date BETWEEN '" . $weekStart->format("Y-m-d") . "' AND '" . $weekEnd->format("Y-m-d") . "'";
}

$whereSql = $conditions ? " WHERE " . implode(" AND ", $conditions) : "";

$reportQuery = "SELECT id, customer_id, dentist, date, time, treatment, status, notes FROM appointments" . $whereSql . " ORDER BY date DESC";
$reportResult = $conn->query($reportQuery);
$reportRows = $reportResult ? $reportResult->fetch_all(MYSQLI_ASSOC) : [];

$treatmentQuery = "SELECT treatment, COUNT(*) AS total FROM appointments" . $whereSql . " GROUP BY treatment ORDER BY total DESC";
$treatmentData = $conn->query($treatmentQuery);
$treatmentArr = $treatmentData ? $treatmentData->fetch_all(MYSQLI_ASSOC) : [];

$forecastQuery = "SELECT MONTH(date) AS month, COUNT(*) AS total FROM appointments" . $whereSql . " GROUP BY MONTH(date) ORDER BY MONTH(date) ASC";
$forecastData = $conn->query($forecastQuery);
$forecastArr = $forecastData ? $forecastData->fetch_all(MYSQLI_ASSOC) : [];

$statusQuery = "SELECT status, COUNT(*) AS total FROM appointments" . $whereSql . " GROUP BY status ORDER BY total DESC";
$statusData = $conn->query($statusQuery);
$statusArr = $statusData ? $statusData->fetch_all(MYSQLI_ASSOC) : [];

function format_report_value($value): string {
    $value = trim((string) $value);
    return htmlspecialchars($value === "" ? "-" : $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Klinik Pergigian Diyana - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/reports.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <div class="card filter-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="mb-0">Filter Report</h5>
                    </div>

                    <form method="get" action="reports.php" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select">
                                <option value="">All Years</option>
                                <?php foreach ($yearOptions as $yearOption): ?>
                                    <option value="<?= (int) $yearOption ?>" <?= $yearOption === $yearFilter ? "selected" : "" ?>><?= (int) $yearOption ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select">
                                <option value="">All Months</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $monthFilter === $m ? "selected" : "" ?>><?= date("F", mktime(0, 0, 0, $m, 1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Week</label>
                            <select name="week" class="form-select">
                                <option value="">All Weeks</option>
                                <?php foreach ($weekOptions as $weekOption): ?>
                                    <option value="<?= $weekOption ?>" <?= $weekFilter === $weekOption ? "selected" : "" ?>>Week <?= $weekOption ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                            <a href="reports.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card report-table-card">
                <div class="card-body">
                    <h6>Filtered Appointment Data</h6>
                    <?php if ($reportRows): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle report-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Customer ID</th>
                                        <th>Dentist</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Treatment</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportRows as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= format_report_value($row["customer_id"]) ?></td>
                                            <td><?= format_report_value($row["dentist"]) ?></td>
                                            <td><?= format_report_value($row["date"]) ?></td>
                                            <td><?= format_report_value($row["time"]) ?></td>
                                            <td><?= format_report_value($row["treatment"]) ?></td>
                                            <td><?= format_report_value($row["status"]) ?></td>
                                            <td><?= format_report_value($row["notes"]) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No appointment data available for the selected filter.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card chart-box">
                <div class="card-body">
                    <h6>Treatment Breakdown</h6>
                    <canvas id="treatmentChart"></canvas>
                </div>
            </div>

            <div class="card chart-box">
                <div class="card-body">
                    <h6>Forecast Patients</h6>
                    <canvas id="forecastChart"></canvas>
                </div>
            </div>

            <div class="card chart-box">
                <div class="card-body">
                    <h6>Appointment Status</h6>
                    <canvas id="statusChart"></canvas>
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

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 15, font: { size: 12 } }
            }
        }
    };

    const treatmentLabels = <?= json_encode(array_column($treatmentArr, 'treatment')) ?>;
    const treatmentCounts = <?= json_encode(array_column($treatmentArr, 'total')) ?>;
    new Chart(document.getElementById('treatmentChart'), {
        type: 'pie',
        data: {
            labels: treatmentLabels,
            datasets: [{
                data: treatmentCounts,
                backgroundColor: ['#2E8B8B','#4DB6AC','#80CBC4','#B2DFDB','#E0F2F1']
            }]
        },
        options: chartOptions
    });

    const forecastLabels = <?php
        $forecastNames = [];
        foreach ($forecastArr as $entry) {
            $forecastNames[] = date("F", mktime(0, 0, 0, (int) $entry["month"], 1));
        }
        echo json_encode($forecastNames);
    ?>;
    const forecastCounts = <?= json_encode(array_column($forecastArr, 'total')) ?>;
    new Chart(document.getElementById('forecastChart'), {
        type: 'bar',
        data: {
            labels: forecastLabels,
            datasets: [{
                label: 'Patients per Month',
                data: forecastCounts,
                backgroundColor: '#2E8B8B'
            }]
        },
        options: chartOptions
    });

    const statusLabels = <?= json_encode(array_column($statusArr, 'status')) ?>;
    const statusCounts = <?= json_encode(array_column($statusArr, 'total')) ?>;
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: ['#FFC107','#28A745','#DC3545']
            }]
        },
        options: chartOptions
    });
</script>
</body>
</html>