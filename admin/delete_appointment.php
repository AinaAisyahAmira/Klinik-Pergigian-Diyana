<?php
include("../config.php");
$id = $_GET['id'];

// Padam appointment
$conn->query("DELETE FROM appointments WHERE id=$id");

header("Location: admin_appointments.php");
exit;
?>