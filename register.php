<?php

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

function fail(string $message): never {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Invalid request method.');
}

/* ------------------------------------------------------------------ */
/* 0. Collect + validate input                                         */
/* ------------------------------------------------------------------ */
$name     = trim($_POST['name']     ?? '');
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$phone    = trim($_POST['phone']    ?? '');
$nic      = trim($_POST['nic']      ?? '');
$password = (string)($_POST['password'] ?? '');

$dentist   = trim($_POST['dentist']   ?? '');
$treatment = trim($_POST['treatment'] ?? '');
$date      = trim($_POST['date']      ?? '');
$time      = trim($_POST['time']      ?? '');

if ($name === '' || $username === '' || $email === '' || $phone === '' || $nic === '' || $password === '') {
    fail('Please fill in every field.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('That email address doesn\'t look right.');
}
if (strlen($password) < 6) {
    fail('Password must be at least 6 characters.');
}
if ($dentist === '' || $treatment === '' || $date === '' || $time === '') {
    fail('Please choose a dentist, treatment, date and time.');
}

if (!$conn->begin_transaction()) {
    fail('Could not start the registration. Please try again.');
}

try {
    /* ------------------------------------------------------------------ */
    /* 1. Find or create the customer account                              */
    /* ------------------------------------------------------------------ */
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ? OR username = ? OR ic_number = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Could not prepare the account lookup.');
    }
    $stmt->bind_param('sss', $email, $username, $nic);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        throw new Exception("That username, email or NIC is already registered. Please log in instead.");
    } else {
        // New patient — create the account.
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, role, fullname, ic_number, email, phone, is_first_time)
             VALUES (?, ?, 'customer', ?, ?, ?, ?, 1)"
        );
        if (!$stmt) {
            throw new Exception('Could not prepare the account registration.');
        }
        $stmt->bind_param('ssssss', $username, $hashed, $name, $nic, $email, $phone);
        if (!$stmt->execute()) {
            throw new Exception('Could not create the patient account. Please try again.');
        }
        $customer_id = $stmt->insert_id;
        $stmt->close();
    }

    /* ------------------------------------------------------------------ */
    /* 2. Re-check the slot is still free                                  */
    /* ------------------------------------------------------------------ */
    $stmt = $conn->prepare(
        "SELECT id FROM appointments
         WHERE dentist = ? AND date = ? AND time = ? AND status IN ('Pending','Approved','Done')"
    );
    if (!$stmt) {
        throw new Exception('Could not prepare the appointment.');
    }
    $stmt->bind_param('sss', $dentist, $date, $time);
    $stmt->execute();
    $clash = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($clash) {
        throw new Exception("Sorry, that slot was just taken. Please pick another time.");
    }

    /* ------------------------------------------------------------------ */
    /* 3. Insert the appointment                                           */
    /* ------------------------------------------------------------------ */
    $stmt = $conn->prepare(
        "INSERT INTO appointments (customer_id, dentist, date, time, treatment, status)
         VALUES (?, ?, ?, ?, ?, 'Pending')"
    );
    if (!$stmt) {
        throw new Exception('Could not prepare the appointment booking.');
    }
    $stmt->bind_param('issss', $customer_id, $dentist, $date, $time, $treatment);
    $stmt->execute();
    $appointment_id = $stmt->insert_id;
    $stmt->close();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    fail($e->getMessage());
}

/* ------------------------------------------------------------------ */
/* 4. Log them in and confirm                                          */
/* ------------------------------------------------------------------ */
$_SESSION['customer_id'] = $customer_id;
$_SESSION['customer_name'] = $name;

echo json_encode([
    'success'        => true,
    'appointment_id' => $appointment_id,
    'dentist'        => $dentist,
    'date'           => $date,
    'time'           => $time,
]);
exit;
?>
