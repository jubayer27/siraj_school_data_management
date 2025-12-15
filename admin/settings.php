<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. HANDLE GENERAL SETTINGS UPDATE
if (isset($_POST['update_settings'])) {
    $updates = [
        'school_name' => $_POST['school_name'],
        'school_address' => $_POST['school_address'],
        'school_phone' => $_POST['school_phone'],
        'current_year' => $_POST['current_year'],
        'current_term' => $_POST['current_term']
    ];

    foreach ($updates as $key => $val) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?");
        $stmt->bind_param("ss", $val, $key);
        $stmt->execute();
    }
    $success = "System configuration updated successfully!";
}

// 3. HANDLE PASSWORD CHANGE
if (isset($_POST['change_password'])) {
    $uid = $_SESSION['user_id'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass === $confirm_pass) {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE user_id=$uid");
        $pass_msg = "Password changed successfully.";
        $pass_type = "success";
    } else {
        $pass_msg = "Passwords do not match.";
        $pass_type = "error";
    }
}

// 4. FETCH CURRENT SETTINGS
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 0 !important;
        display: block !important;
    }

    .container-fluid {
        padding: 30px !important;
    }

    /* Settings Cards */
    .settings-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        margin-bottom: 25px;
        overflow: hidden;
    }

    .card-header-custom {
        background: #fff;
        padding: 20px 25px;
        border-bottom: 1px solid #f0f0f0;
    }

    .card-header-custom h5 {
        margin: 0;
        font-weight: 700;
        color: #333;
    }

    .form-label {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #777;
        margin-bottom: 8px;
    }

    .form-control,
    .form-select {
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        font-size: 0.95rem;
    }

    .form-control:focus {
        border-color: #DAA520;
        box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.1);
    }

    .btn-save {
        background: #DAA520;
        color: #000;
        font-weight: 700;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .btn-save:hover {
        background: #c59d00;
        color: #000;
    }

    /* Alert Boxes */
    .alert-box {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
        display: flex;
        align-items: center;
    }

    .alert-box.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-box.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-box i {
        margin-right: 10px;
        font-size: 1.2rem;
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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">System Settings</h2>
                    <p class="text-secondary mb-0">Configure school details and admin security.</p>
                </div>
            </div>

            <div class="row">

                <div class="col-lg-8">
                    <?php if (isset($success))
                        echo "<div class='alert-box success'><i class='fas fa-check-circle'></i> $success</div>"; ?>

                    <form method="POST">
                        <div class="card settings-card">
                            <div class="card-header-custom">
                                <h5><i class="fas fa-university text-warning me-2"></i> School Profile</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">School Name</label>
                                        <input type="text" name="school_name" class="form-control"
                                            value="<?php echo $settings['school_name'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">School Address</label>
                                        <textarea name="school_address" class="form-control"
                                            rows="2"><?php echo $settings['school_address'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Official Phone</label>
                                        <input type="text" name="school_phone" class="form-control"
                                            value="<?php echo $settings['school_phone'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card settings-card">
                            <div class="card-header-custom">
                                <h5><i class="fas fa-calendar-alt text-primary me-2"></i> Academic Session</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Current Academic Year</label>
                                        <input type="number" name="current_year" class="form-control"
                                            value="<?php echo $settings['current_year'] ?? date('Y'); ?>">
                                        <div class="form-text">Used as default for new classes.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Active Term / Semester</label>
                                        <select name="current_term" class="form-select">
                                            <option value="Term 1" <?php echo ($settings['current_term'] == 'Term 1') ? 'selected' : ''; ?>>Term 1 (Jan - May)</option>
                                            <option value="Midterm" <?php echo ($settings['current_term'] == 'Midterm') ? 'selected' : ''; ?>>Midterm
                                            </option>
                                            <option value="Final" <?php echo ($settings['current_term'] == 'Final') ? 'selected' : ''; ?>>Final Term
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mb-5">
                            <button type="submit" name="update_settings" class="btn btn-save shadow-sm">
                                <i class="fas fa-save me-2"></i> Save Configurations
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <?php if (isset($pass_msg))
                        echo "<div class='alert-box $pass_type'><i class='fas fa-info-circle'></i> $pass_msg</div>"; ?>

                    <div class="card settings-card bg-dark text-white">
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <i class="fas fa-user-shield fa-3x text-warning"></i>
                            </div>
                            <h5 class="fw-bold">Admin Account</h5>
                            <p class="small opacity-75">You are logged in as super admin. Ensure your password is
                                secure.</p>
                        </div>
                    </div>

                    <div class="card settings-card">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-lock text-danger me-2"></i> Change Password</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-dark fw-bold">
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card settings-card border border-warning bg-warning bg-opacity-10">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark"><i class="fas fa-database me-2"></i> Database Status</h6>
                            <p class="small text-muted mb-0">System is connected. <br>Host: localhost</p>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>