<?php
$currentAdminPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar show" id="sidebar">
	<div class="logo-box">
		<img src="../img/logo.jpeg" alt="Clinic Logo">
	</div>
	<nav class="nav flex-column">
		<a class="nav-link <?= $currentAdminPage === 'dashboard_admin.php' ? 'active' : '' ?>" href="dashboard_admin.php">Dashboard</a>
		<a class="nav-link <?= $currentAdminPage === 'admin_appointments.php' ? 'active' : '' ?>" href="admin_appointments.php">Appointments</a>
		<a class="nav-link <?= $currentAdminPage === 'patient_list.php' ? 'active' : '' ?>" href="patient_list.php">Patient List</a>
		<a class="nav-link <?= $currentAdminPage === 'register_patient.php' ? 'active' : '' ?>" href="register_patient.php">Register Patient</a>
		<a class="nav-link <?= $currentAdminPage === 'reports.php' ? 'active' : '' ?>" href="reports.php">Reports</a>
		<a class="nav-link <?= in_array($currentAdminPage, ['settings.php', 'admin_setting.php'], true) ? 'active' : '' ?>" href="settings.php">Settings</a>
	</nav>
</div>
