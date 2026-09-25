<?php
session_start();
include("../config.php");

$loggedIn = $_SESSION["loggedin"] ?? false;
$username = $_SESSION["username"] ?? "";
$role = $_SESSION["role"] ?? "";

if (($loggedIn !== true && $username === "") || $role !== "admin") {
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

// Dapatkan maklumat admin
$username = $_SESSION["username"];
$sql = "SELECT * FROM users WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/sidebar_admin.css">
    <link rel="stylesheet" href="../css/body.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
</head>
<body>

 <div class="header">
    <div class="header-title">
        <h4>KLINIK PERGIGIAN DIYANA</h4>
        <h6 class="text-muted">ADMIN CONSOLE</h6>
    </div>
    <a href="../index.php" class="btn btn-light">Logout</a>
</div>
    <!-- Main Content -->
     <div class="container-fluid flex-grow-1">
    <div class="row">
        <!-- Sidebar -->
            <?php include ('../asset/sidebar_admin.php'); ?>

        <!-- Main Content -->
        <div class="col-md-10 content">

            <div class="settings-head">
                <div class="eyebrow">Configuration</div>
                <h1>Settings</h1>
            </div>

            <div class="settings-card">

                <div class="profile-block">
                    <div class="avatar-wrap">
                        <div class="avatar-circle"><?= htmlspecialchars(strtoupper(substr($user['fullname'] ?? $user['username'], 0, 1))) ?></div>
                        <div class="edit-icon" id="editIconBtn">✎</div>
                    </div>
                    <div class="profile-name"><?= htmlspecialchars($user['fullname'] ?? $user['username']) ?></div>
                    <div class="profile-role">Administrator</div>
                    <button type="button" id="editToggleBtn" class="btn btn-outline-clinic btn-sm mt-1">Edit profile</button>
                </div>

                <hr class="section-rule">

                <form method="POST" action="update_settings.php" class="row g-3" id="settingsForm">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control editable-field" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control editable-field" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone No</label>
                        <input type="text" name="phone" class="form-control editable-field" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="password" class="form-control editable-field" disabled>
                        <div class="field-hint">Required only if setting a new password.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control editable-field" disabled>
                    </div>
                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-clinic" id="updateBtn" disabled>Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const editBtn = document.getElementById('editToggleBtn');
    const editIcon = document.getElementById('editIconBtn');
    const updateBtn = document.getElementById('updateBtn');
    const fields = document.querySelectorAll('.editable-field');

    function enableEditing() {
        fields.forEach(f => f.disabled = false);
        updateBtn.disabled = false;
        editBtn.textContent = 'Editing…';
    }

    editBtn.addEventListener('click', enableEditing);
    editIcon.addEventListener('click', enableEditing);
</script>

<?php include ('../asset/footer.php'); ?>
</body>
</html>