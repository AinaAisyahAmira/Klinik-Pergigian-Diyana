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

// Dapatkan maklumat patient

$sql = "SELECT * FROM users WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Klinik Pergigian Diyana - Patient Settings</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Guna CSS yang sama dengan dashboard patient -->
    <link
        rel="stylesheet"
        href="../css/dashboard_patient.css"
    >

</head>


<body>


<!-- ================= HEADER ================= -->

<div class="header">

    <div class="header-left">

        <span
            class="menu-toggle"
            onclick="toggleSidebar()"
        >
            ☰
        </span>

        <h4>
            🦷 KLINIK PERGIGIAN DIYANA - PATIENT SETTINGS
        </h4>

    </div>


    <a
        href="../index.php"
        class="btn btn-light"
    >
        Logout
    </a>

</div>



<!-- ================= SIDEBAR ================= -->

<div
    class="sidebar"
    id="sidebar"
>

    <div class="logo-box">

        <img
            src="../img/logo.jpeg"
            alt="Clinic Logo"
        >

    </div>


    <nav class="nav flex-column">

        <a
            class="nav-link"
            href="dashboard_patient.php"
        >
            Dashboard
        </a>


        <a
            class="nav-link"
            href="our_dentist.php"
        >
            Our Dentists
        </a>


        <a
            class="nav-link"
            href="patient_past.php"
        >
            My Records
        </a>


        <a
            class="nav-link"
            href="patient_feedback.php"
        >
            Feedback
        </a>


        <a
            class="nav-link active"
            href="patient_profile.php"
        >
            Settings
        </a>

    </nav>

</div>



<!-- ================= MAIN CONTENT ================= -->

<div class="container-fluid flex-grow-1">

    <div class="row">

        <div
            class="col-md-12 content"
            id="content"
        >


            <div class="card p-4">


                <h5 class="mb-4">
                    Settings
                </h5>


                <!-- Edit Icon -->

                <div class="text-center mb-3">

                    <div
                        style="
                        font-size:50px;
                        margin-bottom:10px;
                        "
                    >
                        👤
                    </div>

                    <p class="text-muted">
                        Patient Account
                    </p>

                </div>



                <!-- Settings Form -->

                <form
                    method="POST"
                    action="../asset/update_patient_settings.php"
                    class="row g-3"
                >


                    <!-- Username -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                        >

                    </div>



                    <!-- Full Name -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="fullname"
                            class="form-control"
                            value="<?= htmlspecialchars($user['fullname'] ?? '') ?>"
                        >

                    </div>



                    <!-- Email -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                        >

                    </div>



                    <!-- Phone -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Phone No
                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>"

                        >

                    </div>



                    <!-- Current Password -->

                    <div class="col-md-6">

                        <label class="form-label">
                            Current Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter current password"
                        >

                    </div>



                    <!-- New Password -->

                    <div class="col-md-6">

                        <label class="form-label">
                            New Password
                        </label>

                        <input
                            type="password"
                            name="new_password"
                            class="form-control"
                            placeholder="Enter new password"
                        >

                    </div>



                    <!-- Update Button -->

                    <div class="col-12 text-center mt-3">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Update
                        </button>

                    </div>


                </form>


            </div>


        </div>

    </div>

</div>



<!-- ================= FOOTER ================= -->

<div class="footer">

    Copyright © 2026 Klinik Pergigian Diyana — All Rights Reserved

</div>



<!-- ================= JAVASCRIPT ================= -->

<script>

function toggleSidebar() {

    document
        .getElementById("sidebar")
        .classList.toggle("show");


    document
        .getElementById("content")
        .classList.toggle("shift");

}

</script>


</body>

</html>