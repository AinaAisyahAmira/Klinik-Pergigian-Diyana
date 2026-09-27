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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: patient_profile.php");
    exit;
}

$userId = $_SESSION["user_id"] ?? null;
if (!$userId) {
    echo "<script>alert('Session expired. Please log in again.'); window.location.href='../login.php';</script>";
    exit;
}

$oldUsername = $_SESSION["username"];
$username = trim((string) ($_POST["username"] ?? ""));
$fullname = trim((string) ($_POST["fullname"] ?? ""));
$email = trim((string) ($_POST["email"] ?? ""));
$phone = trim((string) ($_POST["phone"] ?? ""));
$currentPassword = (string) ($_POST["password"] ?? "");
$newPassword = (string) ($_POST["new_password"] ?? "");

if ($username === "" || $fullname === "" || $email === "" || $phone === "") {
    echo "<script>alert('All profile fields are required.'); window.location.href='patient_profile.php';</script>";
    exit;
}

// Check user record first
$userStmt = $conn->prepare("SELECT id, username, password FROM users WHERE id=? LIMIT 1");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userRow = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$userRow) {
    echo "<script>alert('User not found.'); window.location.href='../login.php';</script>";
    exit;
}

if ($currentPassword !== "") {
    $storedPassword = (string) $userRow["password"];
    $passwordMatches = password_verify($currentPassword, $storedPassword);

    if (!$passwordMatches && strlen($storedPassword) < 60) {
        $passwordMatches = hash_equals($storedPassword, $currentPassword);
    }

    if (!$passwordMatches) {
        echo "<script>alert('Current password is incorrect.'); window.location.href='patient_profile.php';</script>";
        exit;
    }
}

if ($username !== $oldUsername) {
    $duplicateStmt = $conn->prepare("SELECT id FROM users WHERE username=? AND id!=? LIMIT 1");
    $duplicateStmt->bind_param("si", $username, $userId);
    $duplicateStmt->execute();
    $duplicateExists = $duplicateStmt->get_result()->fetch_assoc();
    $duplicateStmt->close();

    if ($duplicateExists) {
        echo "<script>alert('Username already exists. Please choose another one.'); window.location.href='patient_profile.php';</script>";
        exit;
    }
}

if ($newPassword !== "") {
    if (strlen($newPassword) < 6) {
        echo "<script>alert('New password must be at least 6 characters.'); window.location.href='patient_profile.php';</script>";
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET username=?, fullname=?, email=?, phone=?, password=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $username, $fullname, $email, $phone, $hashedPassword, $userId);
} else {
    $sql = "UPDATE users SET username=?, fullname=?, email=?, phone=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $username, $fullname, $email, $phone, $userId);
}

if ($stmt->execute()) {
    $_SESSION["username"] = $username;
    $_SESSION["loggedin"] = true;
    $_SESSION["role"] = "customer";

    echo "<script>alert('Profile updated successfully!'); window.location.href='patient_profile.php';</script>";
    exit;
}

echo "<script>alert('Failed to update profile. Please try again.'); window.location.href='patient_profile.php';</script>";
?>