<?php
session_start();
include("../config.php"); // adjust path if needed

$loggedIn = $_SESSION["loggedin"] ?? false;
$username = $_SESSION["username"] ?? "";
$role = $_SESSION["role"] ?? "";

if (($loggedIn !== true && $username === "") || $role !== "customer") {
    header("Location: ../login.php");
    exit();
}

if ($username === "") {
    $userId = $_SESSION["user_id"] ?? null;
    if (!$userId) {
        header("Location: ../login.php");
        exit();
    }

    $userStmt = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    if (!$userRow) {
        header("Location: ../login.php");
        exit();
    }

    $username = $userRow["username"];
    $_SESSION["username"] = $username;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password     = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Basic validation
    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        // Update database: set new password and mark as frequent user
        $sql = "UPDATE users SET password=?, is_first_time=0 WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed, $_SESSION["user_id"]);

        if ($stmt->execute()) {
            $success = "Password updated successfully!";
            // Redirect to patient dashboard
            header("Location: ../patient/dashboard_patient.php");
            exit();
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('../../img/background.jpeg') no-repeat center center fixed;
            background-size: cover;
        }
        .change-box {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            padding: 40px;
            width: 400px;
            margin: 250px auto;
        }
    </style>
</head>
<body>
    <div class="change-box text-center">
        <h3>Change Your Password</h3>
        <form method="POST" action="">
            <div class="mb-3 text-start">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <?php if (!empty($error)): ?>
                <div class="text-danger mb-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="text-success mb-2"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary w-100">Update Password</button>
        </form>
    </div>
</body>
</html>
