<?php
session_start();
include("../config.php");
require_once __DIR__ . "/patient_helpers.php";

if (!isset($_SESSION["username"]) || ($_SESSION["role"] ?? "") !== "admin") {
	header("Location: ../login.php");
	exit;
}

$schema = patient_schema($conn);
$result = null;
$error = "";
try {
	$result = $conn->query(patient_select_sql($schema));
} catch (mysqli_sql_exception $e) {
	$error = $e->getMessage();
}

$messages = [
	"updated"  => "Patient details updated successfully.",
	"password" => "Password has been reset successfully.",
];
$msg = $messages[$_GET["msg"] ?? ""] ?? "";

// ---- Search ----
$search = trim((string) ($_GET["q"] ?? ""));

// Lowercase and strip spaces/dashes so "900101-07-1234" matches "900101071234"
function normalize_search(string $value): string {
	return strtolower(preg_replace('/[\s\-]+/', '', $value));
}

$patients = [];
if ($result) {
	$needle = normalize_search($search);
	while ($row = $result->fetch_assoc()) {
		if ($needle === ""
			|| str_contains(normalize_search((string) $row["fullname"]), $needle)
			|| str_contains(normalize_search((string) $row["ic_number"]), $needle)
		) {
			$patients[] = $row;
		}
	}
}

function show($value): string {
	$value = trim((string) $value);
	return htmlspecialchars($value === "" ? "-" : $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>

<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Patient List</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../css/admin_appointment.css">
	<link rel="stylesheet" href="../css/dashboard_admin.css">
</head>
<body>

<div class="header">
	<div class="header-left">
		<span class="menu-toggle" onclick="toggleSidebar()">☰</span>
		<h3>🦷 Patient Management</h3>
	</div>
	<a href="../index.php" class="btn btn-light">Logout</a>
</div>

<div class="sidebar" id="sidebar">
	<div class="logo-box"><img src="../img/logo.jpeg" alt="Clinic Logo"></div>
	<nav class="nav flex-column">
		<a class="nav-link" href="dashboard_admin.php">Dashboard</a>
		<a class="nav-link" href="admin_appointments.php">Appointments</a>
		<a class="nav-link active" href="patient_list.php">Patient List</a>
		<a class="nav-link" href="register_patient.php">Register Patient</a>
		<a class="nav-link" href="reports.php">Reports</a>
		<a class="nav-link" href="settings.php">Settings</a>
	</nav>
</div>

<div class="container-fluid flex-grow-1">
	<div class="row">
		<main class="col-md-12 content" id="content">
			<div class="card p-4">
				<h5 class="mb-4">All Patients</h5>

				<?php if ($msg !== ""): ?>
					<div class="alert alert-success"><?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?></div>
				<?php endif; ?>

				<?php if ($error !== ""): ?>
					<div class="alert alert-danger">Database error: <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
				<?php endif; ?>

				<!-- Search bar -->
				<form method="get" action="patient_list.php" class="row g-2 mb-3">
					<div class="col-md-6 col-lg-5">
						<input type="text" name="q" class="form-control"
							placeholder="Search by patient name or IC number"
							value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>" autofocus>
					</div>
					<div class="col-auto">
						<button type="submit" class="btn btn-primary">Search</button>
						<?php if ($search !== ""): ?>
							<a href="patient_list.php" class="btn btn-outline-secondary">Clear</a>
						<?php endif; ?>
					</div>
				</form>

				<?php if ($search !== ""): ?>
					<p class="text-muted mb-2">
						<?= count($patients) ?> result<?= count($patients) === 1 ? "" : "s" ?> for
						"<strong><?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?></strong>"
					</p>
				<?php endif; ?>

				<div class="table-responsive">
					<table class="table table-bordered table-hover align-middle">
						<thead>
							<tr>
								<th>Full Name</th>
								<th>Username</th>
								<th>IC Number</th>
								<th>Email</th>
								<th>Phone</th>
								<th>Registered</th>
								<th class="text-center">Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php if (count($patients) > 0): ?>
								<?php foreach ($patients as $row): ?>
									<tr>
										<td><?= show($row["fullname"]) ?></td>
										<td><?= show($row["username"]) ?></td>
										<td><?= show($row["ic_number"]) ?></td>
										<td><?= show($row["email"]) ?></td>
										<td><?= show($row["phone"]) ?></td>
										<td><?= show($row["created_at"]) ?></td>
										<td class="text-center text-nowrap">
											<a href="edit_patient.php?id=<?= (int) $row["id"] ?>" class="btn btn-sm btn-primary">Edit</a>
											<?php if ($schema["password"]): ?>
												<a href="reset_password.php?id=<?= (int) $row["id"] ?>" class="btn btn-sm btn-warning">Reset Password</a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else: ?>
								<tr>
									<td colspan="7" class="text-center">
										<?= $search !== "" ? "No patients match your search" : "No patients found" ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</main>
	</div>
</div>

<?php include("../asset/footer.php"); ?>

<script>
function toggleSidebar() {
	document.getElementById('sidebar').classList.toggle('show');
	document.getElementById('content').classList.toggle('shift');
}
</script>
</body>
</html>