<?php
/**
 * get_availability.php
 *
 * Reads the `appointments` table (id, customer_id, dentist, date, time,
 * treatment, status, notes) and answers two questions for the calendar:
 *
 *   ?action=month&dentist=...&year=...&month=...
 *     -> { "2026-08-19": "available", "2026-08-20": "full", ... }
 *
 *   ?action=day&dentist=...&date=2026-08-19
 *     -> { "slots": [ {"time":"09:00:00","status":"open"}, ... ] }
 *
 * Uses the mysqli connection ($conn) from config.php.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php'; // gives us $conn (mysqli)

/**
 * Returns the list of bookable start times ("HH:MM:SS") for a given
 * day-of-week (0 = Sunday ... 6 = Saturday), generated hourly between
 * the clinic's open/close times:
 *   Monday - Friday : 09:00 - 20:00
 *   Saturday/Sunday : 09:00 - 17:30
 * The last slot generated always starts before closing time (e.g. a
 * 19:00 slot on weekdays, 17:00 on weekends) — adjust $stepMinutes if
 * you want 30-minute slots instead of hourly.
 */
function get_clinic_hours(int $dow): array {
    $isWeekday = ($dow >= 1 && $dow <= 5); // Mon-Fri
    $open  = $isWeekday ? '09:00' : '09:00';
    $close = $isWeekday ? '20:00' : '17:30';
    $stepMinutes = 60;

    $hours = [];
    $cursor  = strtotime($open);
    $closeTs = strtotime($close);
    while ($cursor < $closeTs) {
        $hours[] = date('H:i:00', $cursor);
        $cursor = strtotime("+{$stepMinutes} minutes", $cursor);
    }
    return $hours;
}

$action  = $_GET['action']  ?? '';
$dentist = $_GET['dentist'] ?? '';

if ($dentist === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing dentist']);
    exit;
}

/* ------------------------------------------------------------------ */
/* action=month                                                        */
/* ------------------------------------------------------------------ */
if ($action === 'month') {
    $year  = (int)($_GET['year']  ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));

    if ($month < 1 || $month > 12 || $year < 1970 || $year > 2100) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid year/month']);
        exit;
    }

    $start = sprintf('%04d-%02d-01', $year, $month);
    $end   = date('Y-m-t', strtotime($start)); // last day of that month

    // Only Pending/Done bookings block a slot — Cancelled frees it back up.
    $sql = "SELECT date, COUNT(*) AS taken
            FROM appointments
            WHERE dentist = ?
              AND date BETWEEN ? AND ?
              AND status IN ('Pending','Approved','Done')
            GROUP BY date";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Query prepare failed', 'detail' => $conn->error]);
        exit;
    }
    $stmt->bind_param('sss', $dentist, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();

    $taken = [];
    while ($row = $res->fetch_assoc()) {
        $taken[$row['date']] = (int)$row['taken'];
    }
    $stmt->close();

    $result = [];
    $cursor = strtotime($start);
    $endTs  = strtotime($end);

    while ($cursor <= $endTs) {
        $d   = date('Y-m-d', $cursor);
        $dow = (int)date('w', $cursor); // 0 = Sunday ... 6 = Saturday
        $totalSlots = count(get_clinic_hours($dow));

        // Clinic is now open every day (with shorter weekend hours), so a day
        // only becomes "unavailable" if you add a manual day-off table later.
        if (($taken[$d] ?? 0) >= $totalSlots) {
            $result[$d] = 'full';
        } else {
            $result[$d] = 'available';
        }
        $cursor = strtotime('+1 day', $cursor);
    }

    echo json_encode($result);
    exit;
}

/* ------------------------------------------------------------------ */
/* action=day                                                          */
/* ------------------------------------------------------------------ */
if ($action === 'day') {
    $date = $_GET['date'] ?? '';
    if ($date === '' || !DateTime::createFromFormat('Y-m-d', $date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid date']);
        exit;
    }

    $sql = "SELECT TIME_FORMAT(time, '%H:%i:%s') AS time
            FROM appointments
            WHERE dentist = ? AND date = ?
              AND status IN ('Pending','Approved','Done')";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Query prepare failed', 'detail' => $conn->error]);
        exit;
    }
    $stmt->bind_param('ss', $dentist, $date);
    $stmt->execute();
    $res = $stmt->get_result();

    $takenTimes = [];
    while ($row = $res->fetch_assoc()) {
        $takenTimes[] = $row['time'];
    }
    $stmt->close();

    $dow = (int)date('w', strtotime($date)); // 0 = Sunday ... 6 = Saturday
    $slots = [];
    foreach (get_clinic_hours($dow) as $t) {
        $slots[] = [
            'time'   => $t,
            'status' => in_array($t, $takenTimes, true) ? 'booked' : 'open',
        ];
    }

    echo json_encode(['slots' => $slots]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);