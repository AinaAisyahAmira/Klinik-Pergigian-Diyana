<?php
$servername = "localhost";
$username   = "root";     // ikut setting MySQL awak
$password   = "";         // kalau ada password letak sini
$dbname     = "dental_clinic";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function ensureUsersSchema($conn) {
    $columnsResult = $conn->query("SHOW COLUMNS FROM users");
    $columns = [];

    if ($columnsResult) {
        while ($row = $columnsResult->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }

    $migrations = [
        'fullname' => "ALTER TABLE users ADD COLUMN fullname VARCHAR(100) NULL AFTER role",
        'ic_number' => "ALTER TABLE users ADD COLUMN ic_number VARCHAR(30) NULL AFTER fullname",
        'email' => "ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL AFTER ic_number",
        'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL AFTER email",
        'is_first_time' => "ALTER TABLE users ADD COLUMN is_first_time TINYINT(1) NOT NULL DEFAULT 1 AFTER phone"
    ];

    foreach ($migrations as $field => $sql) {
        if (!in_array($field, $columns, true)) {
            $conn->query($sql);
        }
    }
}

function normalizePhoneForWhatsApp($phone) {
    $digits = preg_replace('/\D+/', '', (string)$phone);

    if ($digits === '') {
        return '';
    }

    if (strlen($digits) === 10 && substr($digits, 0, 1) === '0') {
        return '6' . substr($digits, 1);
    }

    if (strlen($digits) === 11 && substr($digits, 0, 1) === '6') {
        return $digits;
    }

    if (strlen($digits) >= 9) {
        return $digits;
    }

    return '';
}

function buildAppointmentNotificationMessage($status, $treatment, $date, $time, $notes) {
    $statusText = trim((string)$status) !== '' ? trim((string)$status) : 'Updated';
    $treatmentText = trim((string)$treatment) !== '' ? trim((string)$treatment) : 'Appointment';
    $dateText = trim((string)$date) !== '' ? trim((string)$date) : 'your scheduled date';
    $timeText = trim((string)$time) !== '' ? trim((string)$time) : 'your scheduled time';
    $notesText = trim((string)$notes) !== '' ? trim((string)$notes) : 'No extra note added.';

    return "Klinik Pergigian Diyana\n\n" .
        "Appointment status: " . $statusText . "\n" .
        "Treatment: " . $treatmentText . "\n" .
        "Date: " . $dateText . "\n" .
        "Time: " . $timeText . "\n\n" .
        "Doctor note:\n" . $notesText . "\n\n" .
        "Thank you.\nKlinik Pergigian Diyana";
}

function sendAppointmentNotificationToPatient($conn, $customerId, $status, $treatment, $date, $time, $notes) {
    if ((int)$customerId <= 0) {
        return ['email' => '', 'whatsapp_url' => '', 'email_sent' => false, 'phone' => ''];
    }

    $stmt = $conn->prepare(
        "SELECT COALESCE(u.email, p.email) AS patient_email,
                COALESCE(u.phone, p.phone) AS patient_phone
         FROM users u
         LEFT JOIN patients p ON p.patient_id = u.id
         WHERE u.id = ? LIMIT 1"
    );

    $result = ['email' => '', 'whatsapp_url' => '', 'email_sent' => false, 'phone' => ''];

    if (!$stmt) {
        return $result;
    }

    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $contact = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$contact) {
        return $result;
    }

    $result['email'] = trim((string)($contact['patient_email'] ?? ''));
    $result['phone'] = trim((string)($contact['patient_phone'] ?? ''));

    $subject = 'Appointment Update - Klinik Pergigian Diyana';
    $message = buildAppointmentNotificationMessage($status, $treatment, $date, $time, $notes);

    if ($result['email'] !== '' && filter_var($result['email'], FILTER_VALIDATE_EMAIL)) {
        $headers = "From: no-reply@klinikpergigian.com\r\n" .
            "Reply-To: no-reply@klinikpergigian.com\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n";

        $result['email_sent'] = @mail($result['email'], $subject, $message, $headers);
    }

    $waNumber = normalizePhoneForWhatsApp($result['phone']);
    if ($waNumber !== '') {
        $result['whatsapp_url'] = 'https://wa.me/' . rawurlencode($waNumber) . '?text=' . rawurlencode($message);
    }

    return $result;
}

ensureUsersSchema($conn);
?>
