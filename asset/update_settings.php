<?php
session_start();
include("../config.php");

// Pastikan admin dah login
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/settings.php");
    exit;
}

$username = $_SESSION["username"];
$password = $_POST["password"] ?? "";
$new_password = $_POST["new_password"] ?? "";

// Semak kalau ada password baru
if (!empty($new_password)) {
    $password = $new_password;
}

if (empty($password)) {
    header("Location: ../admin/settings.php");
    exit;
}

// Kemas kini data dalam database
$sql = "UPDATE users SET password=? WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $password, $username);

if ($stmt->execute()) {
    echo "<script>alert('Maklumat berjaya dikemas kini!'); window.location.href='../admin/settings.php';</script>";
} else {
    echo "<script>alert('Ralat semasa mengemas kini maklumat.'); window.location.href='../admin/settings.php';</script>";
}
?>