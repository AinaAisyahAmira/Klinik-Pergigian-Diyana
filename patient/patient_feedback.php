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
$success = isset($_GET["submitted"]) && $_GET["submitted"] === "1";
$error = "";

$stmtUser = $conn->prepare("SELECT fullname, username FROM users WHERE username=? LIMIT 1");
$stmtUser->bind_param("s", $username);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$customerName = trim((string)($user["fullname"] ?? ""));
$customerName = $customerName !== "" ? $customerName : ($user["username"] ?? $username);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rating = (float)($_POST["rating"] ?? 0);
    $comment = trim($_POST["comment"] ?? "");

    if ($rating < 1 || $rating > 5 || floor($rating) !== $rating) {
        $error = "Please select a rating from 1 to 5.";
    } elseif ($comment === "") {
        $error = "Please write your feedback before submitting.";
    } elseif (strlen($comment) > 1000) {
        $error = "Feedback must be 1000 characters or less.";
    } else {
        $stmtFeedback = $conn->prepare("INSERT INTO feedback (customer_name, rating, comment) VALUES (?, ?, ?)");
        $stmtFeedback->bind_param("sds", $customerName, $rating, $comment);

        if ($stmtFeedback->execute()) {
            $stmtFeedback->close();
            header("Location: dashboard_patient.php?feedback_submitted=1");
            exit;
        }

        $stmtFeedback->close();
        $error = "Feedback could not be submitted. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard_patient.css">
    <style>
        .feedback-content {
            max-width: 860px;
            margin: 0 auto;
        }
        .feedback-card {
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 34px;
        }
        .feedback-card h5 {
            color: #2e5f6f;
            font-weight: 700;
        }
        .feedback-card .subtitle {
            color: #49656c;
        }
        .feedback-card label {
            color: #2e5f6f;
            font-weight: 600;
        }
        .feedback-card .form-select,
        .feedback-card .form-control {
            border: 1px solid #b6dfe1;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .feedback-card .form-select:focus,
        .feedback-card .form-control:focus {
            border-color: #2e8b8b;
            box-shadow: 0 0 0 3px rgba(46, 139, 139, 0.16);
        }
        .feedback-submit {
            background: #17a2b8;
            border: 0;
            color: #fff;
            font-weight: 600;
            padding: 11px 24px;
            border-radius: 8px;
        }
        .feedback-submit:hover {
            background: #138496;
            color: #fff;
        }
        .rating-hint {
            color: #617b81;
            font-size: 13px;
        }
        @media (max-width: 768px) {
            .feedback-card {
                padding: 24px 18px;
            }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <span class="menu-toggle" onclick="toggleSidebar()">☰</span>
        <h4>🦷 KLINIK PERGIGIAN DIYANA - FEEDBACK</h4>
    </div>
    <a href="../index.php" class="btn btn-light">Logout</a>
</div>

<div class="sidebar show" id="sidebar">
    <div class="logo-box">
        <img src="../img/logo.jpeg" alt="Clinic Logo">
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard_patient.php">Dashboard</a>
        <a class="nav-link" href="our_dentist.php">Our Dentists</a>
        <a class="nav-link" href="patient_past.php">My Records</a>
        <a class="nav-link active" href="patient_feedback.php">Feedback</a>
        <a class="nav-link" href="patient_profile.php">Settings</a>
    </nav>
</div>

<div class="container-fluid flex-grow-1">
    <div class="row">
        <main class="col-md-12 content shift" id="content">
            <div class="feedback-content">
                <div class="feedback-card">
                    <h5 class="mb-2">Share Your Feedback</h5>
                    <p class="subtitle mb-4">Your feedback helps us improve the care and service at our clinic.</p>

                    <?php if ($success): ?>
                        <div class="alert alert-success">Thank you. Your feedback has been submitted successfully.</div>
                    <?php endif; ?>

                    <?php if ($error !== ""): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="patient_feedback.php">
                        <div class="mb-3">
                            <label class="form-label" for="rating">Your Rating</label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="">Select a rating</option>
                                <option value="5" <?= (($_POST["rating"] ?? "") === "5") ? "selected" : "" ?>>5 - Excellent</option>
                                <option value="4" <?= (($_POST["rating"] ?? "") === "4") ? "selected" : "" ?>>4 - Very Good</option>
                                <option value="3" <?= (($_POST["rating"] ?? "") === "3") ? "selected" : "" ?>>3 - Good</option>
                                <option value="2" <?= (($_POST["rating"] ?? "") === "2") ? "selected" : "" ?>>2 - Fair</option>
                                <option value="1" <?= (($_POST["rating"] ?? "") === "1") ? "selected" : "" ?>>1 - Needs Improvement</option>
                            </select>
                            <div class="rating-hint mt-2">Choose a rating from 1 to 5.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="comment">Your Feedback</label>
                            <textarea class="form-control" id="comment" name="comment" rows="6" maxlength="1000" placeholder="Tell us about your experience..." required><?= htmlspecialchars($_POST["comment"] ?? "") ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="dashboard_patient.php" class="btn btn-light">Cancel</a>
                            <button type="submit" class="feedback-submit">Submit Feedback</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="footer">Copyright © 2026 Klinik Pergigian Diyana — All Rights Reserved</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('content').classList.toggle('shift');
}
</script>
</body>
</html>
