<?php
session_start();
include("config.php"); // sambungan DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Cari akaun berdasarkan username
    $sql = "SELECT * FROM users WHERE username=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error = "Ralat pangkalan data. Sila cuba lagi.";
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if (isset($result) && $result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // Verify hashed passwords first; keep existing plain-text accounts working.
        $storedPassword = (string) $row["password"];
        $passwordMatches = password_verify($password, $storedPassword);

        if (!$passwordMatches && strlen($storedPassword) < 60) {
            $passwordMatches = hash_equals($storedPassword, $password);
        }

        if (!$passwordMatches) {
            $error = "Username atau password salah!";
        } else {
            // Upgrade a legacy plain-text password after a successful login.
            if (strlen($storedPassword) < 60) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upgrade = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                $upgrade->bind_param("si", $newHash, $row["id"]);
                $upgrade->execute();
                $upgrade->close();
            }

            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $row["username"];
            $_SESSION["role"]     = $row["role"];
            $_SESSION["user_id"]  = $row["id"];

            // Redirect ikut role
            if ($row["role"] == "admin") {
                header("Location: admin/dashboard_admin.php");
            } elseif ($row["role"] == "doctor") {
                header("Location: staff/dashboard_staff.php");
            } elseif ($row["role"] == "customer") {
                // Tambah logik first-time vs frequent
                if (isset($row["is_first_time"]) && $row["is_first_time"] == 1) {
                    header("Location: patient/change_password.php");
                } else {
                    header("Location: patient/dashboard_patient.php");
                }
            } else {
                $error = "Akaun ini belum mempunyai halaman untuk role tersebut.";
            }

            if (empty($error)) {
                exit;
            }
            session_unset();
        }
    } elseif (empty($error)) {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('../img/background.jpeg') no-repeat center center fixed;
            background-size: cover;
        }
        .login-box {
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
    <div class="login-box text-center">
        <h3>Login</h3>
        <form method="POST" action="">
            <div class="mb-3 text-start">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <?php if (!empty($error)): ?>
                <div class="text-danger mb-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>