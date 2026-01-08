<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$msg = "";
$msg_type = "";

// 2. LOGIC: UPDATE PROFILE
if (isset($_POST['update_profile'])) {
    $fullname = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']); // <--- [CHANGED] Capture Email
    $phone = $conn->real_escape_string($_POST['phone']);
    $ic = $conn->real_escape_string($_POST['ic_no']);
    $username = $conn->real_escape_string($_POST['username']);

    $avatar_sql = "";
    if (!empty($_FILES['avatar']['name'])) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir))
            mkdir($target_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $new_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_name;

        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $avatar_sql = ", avatar = '$new_name'";
            }
        }
    }

    // [CHANGED] Added email='$email' to SQL query
    $sql = "UPDATE users SET full_name='$fullname', email='$email', phone='$phone', ic_no='$ic', username='$username' $avatar_sql WHERE user_id='$user_id'";

    if ($conn->query($sql)) {
        $msg = "Profile updated successfully!";
        $msg_type = "success";
        $_SESSION['full_name'] = $fullname;
    } else {
        $msg = "Error updating database.";
        $msg_type = "danger";
    }
}

// 3. LOGIC: CHANGE PASSWORD
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $res = $conn->query("SELECT password FROM users WHERE user_id = '$user_id'");
    $row = $res->fetch_assoc();

    if (password_verify($current, $row['password'])) {
        if ($new === $confirm) {
            if (strlen($new) >= 6) {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$new_hash' WHERE user_id = '$user_id'");
                $msg = "Password changed successfully!";
                $msg_type = "success";
            } else {
                $msg = "Password must be at least 6 characters.";
                $msg_type = "danger";
            }
        } else {
            $msg = "New passwords do not match.";
            $msg_type = "danger";
        }
    } else {
        $msg = "Current password is incorrect.";
        $msg_type = "danger";
    }
}

// 4. FETCH USER DATA
$user = $conn->query("SELECT * FROM users WHERE user_id = '$user_id'")->fetch_assoc();

// 5. FETCH ROLE SPECIFIC INFO
$role_display = "Staff";
$role_detail = "";

if ($role == 'class_teacher') {
    $role_display = "Class Teacher";
    $c_q = $conn->query("SELECT class_name FROM classes WHERE class_teacher_id = '$user_id'");
    if ($c_q->num_rows > 0) {
        $role_detail = "Managed Class: <strong class='text-primary'>" . $c_q->fetch_assoc()['class_name'] . "</strong>";
    } else {
        $role_detail = "No Class Assigned";
    }
} elseif ($role == 'subject_teacher') {
    $role_display = "Subject Teacher";
    $s_q = $conn->query("SELECT count(*) as c FROM subject_teachers WHERE teacher_id = '$user_id'");
    $count = $s_q->fetch_assoc()['c'];
    $role_detail = "Teaching Load: <strong class='text-warning'>" . $count . " Subjects</strong>";
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    /* GLOBAL LAYOUT */
    body {
        background-color: #f4f6f9;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow-x: hidden;
    }

    .main-content {
        margin-left: 260px;
        padding: 30px;
        min-height: 100vh;
        width: calc(100% - 260px);
    }

    /* CARD STYLING */
    .dashboard-card {
        border: none;
        border-radius: 12px;
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        height: 100%;
        overflow: hidden;
    }

    /* PROFILE SIDEBAR (Left Column) */
    .profile-header-bg {
        height: 120px;
        background: linear-gradient(135deg, #FFD700, #FDB931);
        position: relative;
    }

    .profile-avatar-wrapper {
        position: relative;
        margin-top: -60px;
        text-align: center;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid white;
        object-fit: cover;
        background: #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .profile-meta {
        text-align: center;
        padding: 20px 25px 30px;
    }

    .info-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
    }

    .info-list-item:last-child {
        border-bottom: none;
    }

    .info-label {
        color: #7f8c8d;
        font-weight: 600;
    }

    .info-value {
        color: #2c3e50;
        font-weight: 700;
    }

    /* SETTINGS PANEL (Right Column) */
    .nav-tabs {
        border-bottom: 1px solid #eee;
        padding: 0 20px;
    }

    .nav-link {
        color: #7f8c8d;
        font-weight: 600;
        border: none;
        padding: 15px 25px;
        transition: 0.3s;
    }

    .nav-link.active {
        color: #2c3e50;
        background: transparent;
        border-bottom: 3px solid #FFD700;
    }

    .nav-link:hover {
        color: #333;
    }

    .form-section {
        padding: 30px;
    }

    .form-group label {
        font-weight: 700;
        color: #555;
        font-size: 0.8rem;
        text-transform: uppercase;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .form-control {
        border-radius: 8px;
        padding: 12px;
        border: 1px solid #e0e0e0;
        background-color: #fcfcfc;
    }

    .form-control:focus {
        background-color: #fff;
        border-color: #FFD700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .btn-save {
        background: #2c3e50;
        color: white;
        font-weight: 700;
        padding: 12px 30px;
        border-radius: 8px;
        border: none;
        transition: 0.2s;
    }

    .btn-save:hover {
        background: #03830eff;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(44, 62, 80, 0.2);
    }

    /* RESPONSIVE */
    @media (max-width: 992px) {
        .main-content {
            margin-left: 0;
            width: 100%;
            padding: 20px;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid p-0">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark m-0">My Profile</h2>
                    <p class="text-muted m-0">Manage your account settings and personal information.</p>
                </div>
            </div>

            <?php if ($msg): ?>
                <div
                    class="alert alert-<?php echo $msg_type; ?> shadow-sm rounded-3 border-0 d-flex align-items-center mb-4">
                    <i class="fas fa-info-circle me-2 fs-5"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <div class="profile-header-bg"></div>
                        <div class="profile-avatar-wrapper">
                            <?php $avatar = !empty($user['avatar']) ? "../uploads/" . $user['avatar'] : "https://ui-avatars.com/api/?name=" . $user['full_name'] . "&background=random&size=128"; ?>
                            <img src="<?php echo $avatar; ?>" class="profile-avatar">
                        </div>
                        <div class="profile-meta">
                            <h4 class="fw-bold text-dark mb-1"><?php echo $user['full_name']; ?></h4>
                            <div class="badge bg-light text-dark border mb-3"><?php echo $role_display; ?></div>

                            <div class="text-start mt-3">
                                <div class="info-list-item">
                                    <span class="info-label"><i class="fas fa-id-badge me-2 text-warning"></i>Teacher
                                        ID</span>
                                    <span class="info-value"><?php echo $user['teacher_id_no']; ?></span>
                                </div>

                                <div class="info-list-item">
                                    <span class="info-label"><i
                                            class="fas fa-envelope me-2 text-warning"></i>Email</span>
                                    <span class="info-value"><?php echo $user['email'] ? $user['email'] : '-'; ?></span>
                                </div>

                                <div class="info-list-item">
                                    <span class="info-label"><i
                                            class="fas fa-user me-2 text-warning"></i>Username</span>
                                    <span class="info-value"><?php echo $user['username']; ?></span>
                                </div>
                                <div class="info-list-item">
                                    <span class="info-label"><i class="fas fa-phone me-2 text-warning"></i>Phone</span>
                                    <span class="info-value"><?php echo $user['phone'] ? $user['phone'] : '-'; ?></span>
                                </div>
                                <div class="info-list-item bg-light p-2 rounded mt-2 border-0">
                                    <span class="info-label text-dark">Status:</span>
                                    <span class="info-value"><?php echo $role_detail; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <div class="card-header bg-white p-0">
                            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab"
                                        data-bs-target="#details" type="button">
                                        <i class="fas fa-user-edit me-2"></i> Personal Details
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="security-tab" data-bs-toggle="tab"
                                        data-bs-target="#security" type="button">
                                        <i class="fas fa-lock me-2"></i> Security
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content" id="profileTabsContent">

                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                <div class="form-section">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row g-4">
                                            <div class="col-md-6 form-group">
                                                <label>Full Name</label>
                                                <input type="text" name="full_name" class="form-control"
                                                    value="<?php echo $user['full_name']; ?>" required>
                                            </div>

                                            <div class="col-md-6 form-group">
                                                <label>Email Address</label>
                                                <input type="email" name="email" class="form-control"
                                                    value="<?php echo isset($user['email']) ? $user['email'] : ''; ?>"
                                                    placeholder="name@example.com">
                                            </div>

                                            <div class="col-md-6 form-group">
                                                <label>Login Username</label>
                                                <input type="text" name="username" class="form-control"
                                                    value="<?php echo $user['username']; ?>" required>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>Contact Number</label>
                                                <input type="text" name="phone" class="form-control"
                                                    value="<?php echo $user['phone']; ?>">
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>IC / ID Number</label>
                                                <input type="text" name="ic_no" class="form-control"
                                                    value="<?php echo $user['ic_no']; ?>">
                                            </div>
                                            <div class="col-12 form-group">
                                                <label>Profile Picture</label>
                                                <input type="file" name="avatar" class="form-control" accept="image/*">
                                                <div class="form-text small"><i class="fas fa-info-circle me-1"></i> Max
                                                    size 2MB. Formats: JPG, PNG.</div>
                                            </div>
                                            <div class="col-12 text-end mt-4">
                                                <button type="submit" name="update_profile" class="btn btn-save">
                                                    <i class="fas fa-check-circle me-2"></i> Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <div class="form-section">
                                    <div class="alert alert-warning border-0 d-flex align-items-center mb-4">
                                        <i class="fas fa-shield-alt fa-2x me-3 opacity-50"></i>
                                        <div class="small">
                                            <strong>Password Requirements:</strong><br>
                                            At least 6 characters long. Use a mix of letters and numbers for better
                                            security.
                                        </div>
                                    </div>
                                    <form method="POST">
                                        <div class="row g-4">
                                            <div class="col-12 form-group">
                                                <label>Current Password</label>
                                                <input type="password" name="current_password" class="form-control"
                                                    placeholder="Type current password to verify" required>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>New Password</label>
                                                <input type="password" name="new_password" class="form-control"
                                                    required>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>Confirm New Password</label>
                                                <input type="password" name="confirm_password" class="form-control"
                                                    required>
                                            </div>
                                            <div class="col-12 text-end mt-4">
                                                <button type="submit" name="change_password" class="btn btn-save">
                                                    <i class="fas fa-key me-2"></i> Update Password
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

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