<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY: ADMIN ONLY
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$msg = "";
$msg_type = "";

if (isset($_POST['confirm_reset'])) {
    $current_admin_id = $_SESSION['user_id'];

    // DISABLE FOREIGN KEY CHECKS TO ALLOW TRUNCATION
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // 1. TRUNCATE CHILD TABLES (Data)
    $tables = [
        'student_marks',
        'student_subject_enrollment',
        'student_transfers',
        'subject_teachers',
        'subjects',
        'students',
        'classes',
        'notices',
        'exam_types'
    ];

    foreach ($tables as $table) {
        $conn->query("TRUNCATE TABLE $table");
    }

    // 2. CLEAR USERS (Except You)
    // We use DELETE instead of TRUNCATE here to preserve the current session
    $del_users = $conn->query("DELETE FROM users WHERE user_id != $current_admin_id");

    // RE-ENABLE FOREIGN KEY CHECKS
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    if ($del_users) {
        $msg = "System reset complete. All data deleted (except your admin account).";
        $msg_type = "success";
    } else {
        $msg = "Error resetting system: " . $conn->error;
        $msg_type = "danger";
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
    }

    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 30px !important;
    }

    .danger-zone {
        border: 2px dashed #dc3545;
        background: #fff5f5;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        max-width: 600px;
        margin: 50px auto;
    }

    @media (max-width: 992px) {
        .main-content {
            width: 100% !important;
            margin-left: 0 !important;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <h2 class="fw-bold text-dark mb-4">System Maintenance</h2>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> shadow-sm">
                    <i class="fas fa-info-circle me-2"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="danger-zone shadow-sm">
                <i class="fas fa-exclamation-triangle text-danger fa-4x mb-3"></i>
                <h3 class="text-danger fw-bold">Factory Reset</h3>
                <p class="text-muted mb-4">
                    This will <strong>permanently delete</strong> all Students, Classes, Subjects, Marks, and other
                    Users.<br>
                    <span class="text-dark fw-bold">Only your current Admin account will be saved.</span>
                </p>

                <form method="POST"
                    onsubmit="return confirm('CRITICAL WARNING: This cannot be undone. Are you absolutely sure?');">
                    <button type="submit" name="confirm_reset" class="btn btn-danger btn-lg fw-bold px-5">
                        <i class="fas fa-trash-alt me-2"></i> WIPE ALL DATA
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
</body>

</html>