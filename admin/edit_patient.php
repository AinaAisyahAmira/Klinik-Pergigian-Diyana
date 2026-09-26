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

if (!$patient) {
	header("Location: patient_list.php");
	exit;
}

// Username can be edited only if the users table has a real `username` column
$canEditUsername = ($schema["username"] === "username");

// Only show fields that exist in at least one table
$labels = ["fullname" => "Full Name", "ic_number" => "IC Number", "email" => "Email", "phone" => "Phone"];
$fields = [];
foreach ($labels as $key => $label) {
	if ($schema["map"][$key]["u"] || $schema["map"][$key]["p"]) {
		$fields[$key] = $label;
	}
}

$errors = [];
$values = [];
$username = (string) ($patient["username"] ?? "");
foreach ($fields as $key => $label) {
	$values[$key] = (string) ($patient[$key] ?? "");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	foreach ($fields as $key => $label) {
		$values[$key] = trim($_POST[$key] ?? "");
	}

	// ---- Username validation ----
	if ($canEditUsername) {
		$username = trim($_POST["username"] ?? "");

		if ($username === "") {
			$errors[] = "Username is required.";
		} elseif (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
			$errors[] = "Username must be 3–30 characters and use only letters, numbers, underscore (_) or dot (.).";
		} else {
			// Make sure no other user already has this username
			$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
			$stmt->bind_param("si", $username, $id);
			$stmt->execute();
			if ($stmt->get_result()->num_rows > 0) {
				$errors[] = "The username \"" . $username . "\" is already taken. Please choose another.";
			}
		}
	}

	// ---- Other fields validation ----
	if (isset($fields["fullname"]) && $values["fullname"] === "") {
		$errors[] = "Full name is required.";
	}
	if (isset($fields["email"]) && $values["email"] !== "" && !filter_var($values["email"], FILTER_VALIDATE_EMAIL)) {
		$errors[] = "Please enter a valid email address.";
	}

	// ---- Save ----
	if (!$errors) {
		try {
			$conn->begin_transaction();

			if ($canEditUsername) {
				update_table($conn, "users", "id", $id, ["username" => $username]);
			}
			save_patient_fields($conn, $schema, $id, $values);

			$conn->commit();
			header("Location: patient_list.php?msg=updated");
			exit;
		} catch (mysqli_sql_exception $e) {
			$conn->rollback();
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
	<title>Edit Patient</title>
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
			<div class="card p-4" style="max-width: 640px;">
				<h5 class="mb-1">Edit Patient</h5>
				<p class="text-muted mb-4">Patient ID <?= (int) $id ?></p>

				<?php foreach ($errors as $err): ?>
					<div class="alert alert-danger"><?= e($err) ?></div>
				<?php endforeach; ?>

				<form method="post">
					<input type="hidden" name="id" value="<?= (int) $id ?>">

					<?php if ($canEditUsername): ?>
						<div class="mb-3">
							<label class="form-label" for="username">Username</label>
							<input
								type="text"
								class="form-control"
								id="username"
								name="username"
								value="<?= e($username) ?>"
								pattern="[A-Za-z0-9_.]{3,30}"
								title="3–30 characters: letters, numbers, underscore or dot"
								required>
							<div class="form-text">The patient will use this new username to log in.</div>
						</div>
					<?php endif; ?>

					<?php foreach ($fields as $key => $label): ?>
						<div class="mb-3">
							<label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label>
							<input
								type="<?= $key === "email" ? "email" : "text" ?>"
								class="form-control"
								id="<?= e($key) ?>"
								name="<?= e($key) ?>"
								value="<?= e($values[$key]) ?>"
								<?= $key === "fullname" ? "required" : "" ?>>
						</div>
					<?php endforeach; ?>

					<button type="submit" class="btn btn-primary">Save Changes</button>
					<a href="patient_list.php" class="btn btn-secondary">Cancel</a>
				</form>
			</div>
		</main>
	</div>
</div>

<?php include('../asset/footer.php'); ?>
<script>
function toggleSidebar() {
	document.getElementById('sidebar').classList.toggle('show');
	document.getElementById('content').classList.toggle('shift');
}
</script>
</body>
</html>