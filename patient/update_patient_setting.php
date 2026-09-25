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

// Pastikan datang daripada form
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../patient/patient_profile.php");
    exit;
}


// Username asal daripada session
$old_username = $_SESSION["username"];

// Ambil data daripada form
$username = $_POST["username"] ?? "";
$fullname = $_POST["fullname"] ?? "";
$email = $_POST["email"] ?? "";
$phone = $_POST["phone"] ?? "";

$password = $_POST["password"] ?? "";
$new_password = $_POST["new_password"] ?? "";


// Jika username kosong
if (empty($username)) {

    echo "
    <script>
        alert('Username cannot be empty.');
        window.location.href='../patient/patient_profile.php';
    </script>
    ";

    exit;
}


// Jika password baru diisi
if (!empty($new_password)) {

    $sql = "UPDATE users
            SET username=?, fullname=?, email=?, phone=?, password=?
            WHERE username=?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssssss",
        $username,
        $fullname,
        $email,
        $phone,
        $new_password,
        $old_username
    );

} else {

    // Kalau tak tukar password
    $sql = "UPDATE users
            SET username=?, fullname=?, email=?, phone=?
            WHERE username=?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssss",
        $username,
        $fullname,
        $email,
        $phone,
        $old_username
    );

}


// Jalankan update
if ($stmt->execute()) {

    // Update session username
    $_SESSION["username"] = $username;

    echo "
    <script>
        alert('Maklumat berjaya dikemas kini!');
        window.location.href='../patient/patient_profile.php';
    </script>
    ";

} else {

    echo "
    <script>
        alert('Ralat semasa mengemas kini maklumat.');
        window.location.href='../patient/patient_profile.php';
    </script>
    ";

}

?>