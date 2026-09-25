<?php
session_start();
include("../config.php");
require_once __DIR__ . "/patient_helpers.php";

if (!isset($_SESSION["username"]) || ($_SESSION["role"] ?? "") !== "admin") {
	header("Location: ../login.php");
	exit;
}

$schema = patient_schema($conn);
$id = (int) ($_POST["id"] ?? $_GET["id"] ?? 0);
$patient = $id > 0 ? load_patient($conn, $schema, $id) : null;

if (!$patient || !$schema["password"]) {
	header("Location: patient_list.php");
	exit;
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$new = $_POST["new_password"] ?? "";
	$confirm = $_POST["confirm_password"] ?? "";

	if (strlen($new) < 6) {
		$errors[] = "Password must be at least 6 characters.";
	}
	if ($new !== $confirm) {
		$errors[] = "Passwords do not match.";
	}

	if (!$errors) {
		try {
			$col = $schema["password"];

			// Read the current password to store the new one in the same format
			$stmt = $conn->prepare("SELECT `$col` FROM users WHERE id = ?");
			$stmt->bind_param("i", $id);
			$stmt->execute();
			$old = $stmt->get_result()->fetch_row()[0] ?? "";

			$stored = hash_like_existing($new, $old);

			$stmt = $conn->prepare("UPDATE users SET `$col` = ? WHERE id = ?");
			$stmt->bind_param("si", $stored, $id);
			$stmt->execute();

			header("Location: patient_list.php?msg=password");
			exit;
		} catch (mysqli_sql_exception $e) {
			$errors[] = "Database error: " . $e->getMessage();
		}
	}
}

function e($v): string {
	return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>

<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reset Password</title>
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
			<div class="card p-4" style="max-width: 520px;">
				<h5 class="mb-1">Reset Password</h5>
				<p class="text-muted mb-4">
					Patient: <?= e($patient["fullname"] ?? "-") ?>
					(username: <?= e($patient["username"] ?? "-") ?>)
				</p>

				<?php foreach ($errors as $err): ?>
					<div class="alert alert-danger"><?= e($err) ?></div>
				<?php endforeach; ?>

				<form method="post" autocomplete="off">
					<input type="hidden" name="id" value="<?= (int) $id ?>">

					<div class="mb-3">
						<label class="form-label" for="new_password">New Password</label>
						<input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
					</div>

					<div class="mb-3">
						<label class="form-label" for="confirm_password">Confirm New Password</label>
						<input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
					</div>

					<div class="form-check mb-3">
						<input class="form-check-input" type="checkbox" id="showPw" onclick="togglePw()">
						<label class="form-check-label" for="showPw">Show password</label>
					</div>

					<button type="submit" class="btn btn-warning">Reset Password</button>
					<a href="patient_list.php" class="btn btn-secondary">Cancel</a>
				</form>
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
function togglePw() {
	const type = document.getElementById('showPw').checked ? 'text' : 'password';
	document.getElementById('new_password').type = type;
	document.getElementById('confirm_password').type = type;
}
</script>
</body>
</html>