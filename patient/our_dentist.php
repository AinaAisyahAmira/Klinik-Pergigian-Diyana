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

// Ambil doktor yang dipilih
$selectedDentist = isset($_GET['dentist']) ? $_GET['dentist'] : null;

$notificationEntries = [];
$notificationStmt = $conn->prepare("SELECT date, time, treatment, status, notes
    FROM appointments
    WHERE customer_id = ? AND TRIM(COALESCE(notes, '')) <> ''
    ORDER BY date DESC, time DESC, id DESC
    LIMIT 5");
if ($notificationStmt) {
    $notificationStmt->bind_param("i", $_SESSION['user_id']);
    $notificationStmt->execute();
    $notificationResult = $notificationStmt->get_result();
    while ($row = $notificationResult->fetch_assoc()) {
        $notificationEntries[] = $row;
    }
    $notificationStmt->close();
}


// Bila customer submit appointment
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $date      = trim($_POST["date"] ?? "");
    $time      = trim($_POST["time"] ?? "");
    $treatment = trim($_POST["treatment"] ?? "");
    $dentist   = trim($_POST["dentist"] ?? "");


    // Semak tarikh
    $validDate = DateTime::createFromFormat('Y-m-d', $date) !== false;


    // Semak masa
    $validTime = (bool)preg_match(
        '/^\d{2}:\d{2}(:\d{2})?$/',
        $time
    );


    if (
        $date === "" ||
        $time === "" ||
        $treatment === "" ||
        $dentist === "" ||
        !$validDate ||
        !$validTime
    ) {

        echo "<script>
            alert('Please choose a treatment, date, and time before confirming.');
            window.location='our_dentist.php?dentist=" . urlencode($dentist) . "';
        </script>";

        exit;
    }


    // Dapatkan ID customer
    $sqlUser = "SELECT id FROM users WHERE username=?";

    $stmtUser = $conn->prepare($sqlUser);

    $stmtUser->bind_param(
        "s",
        $_SESSION["username"]
    );

    $stmtUser->execute();

    $userRow = $stmtUser
        ->get_result()
        ->fetch_assoc();

    $stmtUser->close();


    if (!$userRow) {

        echo "<script>
            alert('Your account could not be found. Please log in again.');
            window.location='../login.php';
        </script>";

        exit;
    }


    $customer_id = $userRow["id"];


    // Semak slot sudah diambil
    $sqlCheck = "
        SELECT id
        FROM appointments
        WHERE dentist = ?
        AND date = ?
        AND time = ?
        AND status IN ('Pending', 'Approved', 'Done')
    ";

    $stmtCheck = $conn->prepare($sqlCheck);

    $stmtCheck->bind_param(
        "sss",
        $dentist,
        $date,
        $time
    );

    $stmtCheck->execute();

    $alreadyTaken =
        $stmtCheck->get_result()->num_rows > 0;

    $stmtCheck->close();


    if ($alreadyTaken) {

        echo "<script>
            alert('Sorry, that slot was just taken — please pick another time.');
            window.location='our_dentist.php?dentist=" . urlencode($dentist) . "';
        </script>";

        exit;
    }


    // Simpan appointment
    $sqlInsert = "
        INSERT INTO appointments
        (customer_id, dentist, date, time, treatment, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ";

    $stmtInsert = $conn->prepare($sqlInsert);

    $stmtInsert->bind_param(
        "issss",
        $customer_id,
        $dentist,
        $date,
        $time,
        $treatment
    );

    $stmtInsert->execute();

    $stmtInsert->close();


    echo "<script>
        alert('Appointment booked successfully!');
        window.location='dashboard_patient.php';
    </script>";

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

<title>
    Our Dentist - Klinik Pergigian Diyana
</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- CSS -->

<link
    rel="stylesheet"
    href="../css/our_doctor.css?v=20260924"
>

</head>

<body>

<!-- ================================================= -->

<!-- HEADER                                            -->

<!-- ================================================= -->

<div class="header">

<div class="header-left">

    <span
        class="menu-toggle"
        onclick="toggleSidebar()"
    >
        ☰
    </span>

    <h4>
        🦷 KLINIK PERGIGIAN DIYANA - PATIENT
    </h4>

</div>


<!-- Notifications -->

<div class="notification-panel">
    <details>
        <summary>
            🔔 <span>Notifications</span>
            <?php if (!empty($notificationEntries)): ?>
                <span class="notification-badge"><?= count($notificationEntries) ?></span>
            <?php endif; ?>
        </summary>
        <div class="notification-menu">
            <?php if (!empty($notificationEntries)): ?>
                <?php foreach ($notificationEntries as $note): ?>
                    <div class="notification-item">
                        <div class="notification-title"><?= htmlspecialchars($note['status'] ?? 'Update') ?></div>
                        <div class="notification-text"><?= htmlspecialchars($note['notes']) ?></div>
                        <div class="notification-meta"><?= htmlspecialchars($note['date']) ?> • <?= htmlspecialchars($note['time']) ?> • <?= htmlspecialchars($note['treatment']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-empty">No doctor notifications yet.</div>
            <?php endif; ?>
        </div>
    </details>
</div>

<!-- Logout -->

<a
    href="../index.php"
    class="btn btn-light"
>
    Logout
</a>

</div>

<style>
.notification-panel { display:flex; align-items:center; }
.notification-panel details { position:relative; }
.notification-panel summary {
    list-style:none; cursor:pointer; display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.18);
    color:#fff; border-radius:999px; padding:10px 14px; font-weight:600; border:1px solid rgba(255,255,255,0.4);
}
.notification-panel summary::-webkit-details-marker { display:none; }
.notification-badge { background:#ffeb3b; color:#1f2937; border-radius:999px; padding:2px 7px; font-size:12px; font-weight:700; }
.notification-menu { position:absolute; right:0; top:calc(100% + 12px); width:340px; max-height:300px; overflow-y:auto; background:#fff; border-radius:14px; box-shadow:0 16px 28px rgba(0,0,0,0.15); padding:10px; z-index:50; }
.notification-item { border-bottom:1px solid #eef2f7; padding:10px 8px; }
.notification-item:last-child { border-bottom:none; }
.notification-title { font-weight:700; color:#0f172a; margin-bottom:4px; }
.notification-text { color:#475569; font-size:0.92rem; line-height:1.5; margin-bottom:5px; }
.notification-meta { color:#64748b; font-size:0.76rem; }
.notification-empty { color:#64748b; font-size:0.9rem; padding:10px 8px; }
</style>

<!-- ================================================= -->

<!-- SIDEBAR                                           -->

<!-- ================================================= -->

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
        class="nav-link active"
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
        class="nav-link"
        href="patient_profile.php"
    >
        Settings
    </a>


</nav>

</div>

<!-- ================================================= -->

<!-- MAIN CONTENT                                      -->

<!-- ================================================= -->

<div class="container-fluid flex-grow-1">

<div class="row">

    <div
        class="col-md-12 content"
        id="content"
    >


        <!-- Page Title -->

        <h4 class="page-title">
            Find A Dentist
        </h4>



        <!-- ================================================= -->
        <!-- DOCTOR LIST                                        -->
        <!-- ================================================= -->

        <div class="doctor-container">


            <!-- Dr Nur Diyana -->

            <div class="doctor-card">

                <img
                    src="../img/drdiyana.jpeg"
                    alt="Dr Nur Diyana"
                >


                <div class="doctor-info">

                    <h6>
                        Dr Nur Diyana
                    </h6>

                    <p>
                        Dentist
                    </p>


                    <a
                        href="our_dentist.php?dentist=Dr%20Nur%20Diyana"
                        class="select-btn"
                    >
                        Select
                    </a>

                </div>

            </div>



            <!-- Dr Huda Sofiah -->

            <div class="doctor-card">

                <img
                    src="../img/drhuda.jpeg"
                    alt="Dr Huda Sofiah"
                >


                <div class="doctor-info">

                    <h6>
                        Dr Huda Sofiah
                    </h6>

                    <p>
                        Dentist
                    </p>


                    <a
                        href="our_dentist.php?dentist=Dr%20Huda%20Sofiah"
                        class="select-btn"
                    >
                        Select
                    </a>

                </div>

            </div>

            <!-- Dr Afif Azmi -->

            <div class="doctor-card">

                <img
                    src="../img/drafif.png"
                    alt="Dr Afif Azmi"
                >


                <div class="doctor-info">

                    <h6>
                        Dr Afif Azmi
                    </h6>

                    <p>
                        Dentist
                    </p>


                    <a
                        href="our_dentist.php?dentist=Dr%20Afif%20Azmi"
                        class="select-btn"
                    >
                        Select
                    </a>

                </div>

            </div>

            <!-- Dr Mohd Ghazlan -->

            <div class="doctor-card">

                <img
                    src="../img/drmohd.png"
                    alt="Dr Mohd Ghazlan"
                >


                <div class="doctor-info">

                    <h6>
                        Dr Mohd Ghazlan
                    </h6>

                    <p>
                        Dentist
                    </p>


                    <a
                        href="our_dentist.php?dentist=Dr%20Mohd%20Ghazlan"
                        class="select-btn"
                    >
                        Select
                    </a>

                </div>

            </div>

            <!-- Dr Marisa Shanthini -->

            <div class="doctor-card">

                <img
                    src="../img/drmarisa.png"
                    alt="Dr Marisa Shanthini"
                >


                <div class="doctor-info">

                    <h6>
                        Dr Marisa Shanthini
                    </h6>

                    <p>
                        Dentist
                    </p>


                    <a
                        href="our_dentist.php?dentist=Dr%20Marisa%20Shanthini"
                        class="select-btn"
                    >
                        Select
                    </a>

                </div>

            </div>


        </div>



        <!-- ================================================= -->
        <!-- BOOK APPOINTMENT                                   -->
        <!-- ================================================= -->

        <?php if ($selectedDentist): ?>


            <div class="booking-card">


                <h4>

                    Book Appointment with

                    <?= htmlspecialchars(
                        ucwords($selectedDentist)
                    ) ?>

                </h4>



                <form
                    method="POST"
                    class="booking-form"
                    id="bookingForm"
                >


                    <!-- Dentist -->

                    <input
                        type="hidden"
                        name="dentist"
                        id="hiddenDentist"
                        value="<?= htmlspecialchars(
                            ucwords($selectedDentist)
                        ) ?>"
                    >



                    <!-- Treatment -->

                    <div class="mb-3">

                        <label
                            for="treatment"
                            class="form-label"
                        >
                            Treatment Type
                        </label>


                        <select
                            name="treatment"
                            id="treatment"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select Treatment
                            </option>

                            <option value="Scaling">
                                Scaling
                            </option>

                            <option value="Filling">
                                Filling
                            </option>

                            <option value="Extraction">
                                Extraction
                            </option>

                            <option value="Braces">
                                Braces
                            </option>

                            <option value="Whitening">
                                Whitening
                            </option>

                        </select>

                    </div>



                    <!-- Calendar -->

                    <div class="mb-3">

                        <label class="form-label">
                            Select Date
                        </label>


                        <div
                            id="calendarWrap"
                            class="calendar-wrap"
                        >


                            <div class="calendar-header">


                                <button
                                    type="button"
                                    id="prevMonthBtn"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    &laquo;
                                </button>


                                <span
                                    id="calMonthLabel"
                                    class="fw-semibold"
                                ></span>


                                <button
                                    type="button"
                                    id="nextMonthBtn"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    &raquo;
                                </button>


                            </div>



                            <div class="calendar-daynames">

                                <span>Sun</span>
                                <span>Mon</span>
                                <span>Tue</span>
                                <span>Wed</span>
                                <span>Thu</span>
                                <span>Fri</span>
                                <span>Sat</span>

                            </div>



                            <div
                                id="calendarDays"
                                class="calendar-days"
                            ></div>


                        </div>

                    </div>



                    <!-- Time -->

                    <div
                        class="mb-3"
                        id="timeSlotSection"
                        style="display:none;"
                    >

                        <label class="form-label">
                            Select Time
                        </label>


                        <div
                            id="timeSlotList"
                            class="time-slot-list"
                        ></div>

                    </div>



                    <!-- Message -->

                    <div
                        id="bookingMsg"
                        class="alert alert-warning d-none"
                    ></div>



                    <!-- Hidden Date -->

                    <input
                        type="hidden"
                        name="date"
                        id="hiddenDate"
                    >


                    <!-- Hidden Time -->

                    <input
                        type="hidden"
                        name="time"
                        id="hiddenTime"
                    >



                    <!-- Confirm -->

                    <button
                        type="submit"
                        class="btn btn-success"
                        id="submitBookingBtn"
                        disabled
                    >
                        Confirm Booking
                    </button>


                </form>


            </div>


        <?php endif; ?>


    </div>

</div>

</div>

<!-- ================================================= -->

<!-- FOOTER                                            -->

<!-- ================================================= -->

<div class="footer">


Copyright © 2026 Klinik Pergigian Diyana — All Rights Reserved


</div>

<!-- ================================================= -->

<!-- SIDEBAR JAVASCRIPT                                -->

<!-- ================================================= -->

<script>

function toggleSidebar() {

    document
        .getElementById('sidebar')
        .classList.toggle('show');

    document
        .getElementById('content')
        .classList.toggle('shift');

}

</script>

<!-- ================================================= -->

<!-- BOOKING JAVASCRIPT                                -->

<!-- ================================================= -->

<?php if ($selectedDentist): ?>

<script>

window.BOOKING_DENTIST =
    <?= json_encode(
        ucwords($selectedDentist)
    ) ?>;

</script>

<script src="dentist_booking.js?v=20260925"></script>

<?php endif; ?>

<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>