<?php
session_start();
include '../config/db.php';

// 1. SECURITY: Ensure User is Logged In
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // 'class_teacher' or 'subject_teacher'
$msg = "";
$msg_type = "";

// 2. HANDLE PROFILE UPDATE
if (isset($_POST['update_profile'])) {
    $fullname = $conn->real_escape_string($_POST['full_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $ic = $conn->real_escape_string($_POST['ic_no']);
    $username = $conn->real_escape_string($_POST['username']);

    // Avatar Upload Logic
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

    $sql = "UPDATE users SET full_name='$fullname', phone='$phone', ic_no='$ic', username='$username' $avatar_sql WHERE user_id='$user_id'";
    if ($conn->query($sql)) {
        $msg = "Personal details updated successfully!";
        $msg_type = "success";
        $_SESSION['full_name'] = $fullname; // Update session
    } else {
        $msg = "Error updating profile: " . $conn->error;
        $msg_type = "danger";
    }
}

// 3. HANDLE PASSWORD CHANGE
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
                $msg = "New password must be at least 6 characters.";
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

// 5. FETCH DEDICATED ROLE INFO
$role_info = "";
if ($role == 'class_teacher') {
    // Get the Class Name this teacher is in charge of
    $c_q = $conn->query("SELECT class_name, year FROM classes WHERE class_teacher_id = '$user_id'");
    if ($c_q->num_rows > 0) {
        $c_data = $c_q->fetch_assoc();
        $role_info = "Class Teacher of <strong>" . $c_data['class_name'] . " (" . $c_data['year'] . ")</strong>";
    } else {
        $role_info = "No class assigned yet.";
    }
} elseif ($role == 'subject_teacher') {
    // Get list of subjects this teacher teaches
    $s_q = $conn->query("SELECT s.subject_name, c.class_name 
                         FROM subject_teachers st 
                         JOIN subjects s ON st.subject_id = s.subject_id 
                         JOIN classes c ON s.class_id = c.class_id 
                         WHERE st.teacher_id = '$user_id'");
    $subjects_list = [];
    while ($sub = $s_q->fetch_assoc()) {
        $subjects_list[] = $sub['subject_name'] . " (" . $sub['class_name'] . ")";
    }
    if (!empty($subjects_list)) {
        $role_info = "Teaches: " . implode(", ", $subjects_list);
    } else {
        $role_info = "No subjects assigned yet.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<style>
    body {
        background-color: #f4f6f9;
    }

    .main-content {
        margin-left: 260px;
        padding: 30px;
        min-height: 100vh;
    }

    /* Profile Card */
    .profile-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .profile-header-bg {
        height: 120px;
        background: linear-gradient(135deg, #FFD700, #ffb900);
    }

    .profile-body {
        padding: 0 30px 30px;
        position: relative;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid white;
        object-fit: cover;
        margin-top: -60px;
        background: #eee;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .role-badge {
        background: #2c3e50;
        color: #FFD700;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: inline-block;
        margin-top: 10px;
    }

    .nav-tabs .nav-link {
        color: #555;
        font-weight: 600;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 15px 20px;
    }

    .nav-tabs .nav-link.active {
        color: #2c3e50;
        border-bottom-color: #FFD700;
        background: transparent;
    }

    .nav-tabs .nav-link:hover {
        color: #000;
    }

    .form-label {
        font-weight: 700;
        font-size: 0.85rem;
        color: #555;
        text-transform: uppercase;
    }

    .form-control {
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #eee;
        background-color: #f9f9f9;
    }

    .form-control:focus {
        background-color: #fff;
        border-color: #FFD700;
        box-shadow: none;
    }

    .btn-gold {
        background: #FFD700;
        color: #000;
        font-weight: bold;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        transition: 0.3s;
    }

    .btn-gold:hover {
        background: #e6c200;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
    }

    @media (max-width: 992px) {
        .main-content {
            margin-left: 0;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> shadow-sm rounded-3 border-0 d-flex align-items-center mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <div class="profile-card text-center">
                    <div class="profile-header-bg"></div>
                    <div class="profile-body">
                        <?php $avatar = !empty($user['avatar']) ? "../uploads/" . $user['avatar'] : "https://ui-avatars.com/api/?name=" . $user['full_name'] . "&background=random"; ?>
                        <img src="<?php echo $avatar; ?>" class="profile-avatar">

                        <h4 class="fw-bold mt-3 text-dark mb-1">
                            
                            <?php echo $user['full_name']; ?>
                        </h4>

                                                    <div class="text-muted small mb-2">
                            <?php echo $user['teacher_id_no']; ?>
                        </div
                           >

                        <span class="role-badge">
                            <?php echo str_replace('_', ' ', strtoupper($role)); ?>
                        </span>

                        <div class="mt-4 p-3 bg-light rounded text-start">
                            <h6 class="fw-bold text-secondary text-uppercase small mb-3">Dedicated Info</h6>
                            <p class="mb-1 text-dark small"><i class="fas fa-briefcase me-2 text-warning"></i>
                                <?php echo $role_info; ?>
                            </p>
                            <p class="mb-0 text-dark small"><i class="fas fa-envelope me-2 text-warning"></i>
                                <?php echo $user['username']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-card">
                    <div class="card-header bg-white border-bottom p-0">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="edit-tab" data-bs-toggle="tab"
                                    data-bs-target="#edit" type="button">Edit Profile</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="password-tab" data-bs-toggle="tab"
                                    data-bs-target="#password" type="button">Change Password</button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4">
                        <div class="tab-content" id="myTabContent">

                            <div class="tab-pane fade show active" id="edit">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" name="full_name" class="form-control"
                                                value="<?php echo $user['full_name']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input type="text" name="username" class="form-control"
                                                value="<?php echo $user['username']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" name="phone" class="form-control"
                                                value="<?php echo $user['phone']; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">IC Number</label>
                                            <input type="text" name="ic_no" class="form-control"
                                                value="<?php echo $user['ic_no']; ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Profile Picture</label>
                                            <input type="file" name="avatar" class="form-control" accept="image/*">
                                            <small class="text-muted">Allowed: JPG, PNG. Max size: 2MB</small>
                                        </div>
                                        <div class="col-12 text-end mt-4">
                                            <button type="submit" name="update_profile" class="btn btn-gold">
                                                <i class="fas fa-save me-2"></i> Save Changes
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="password">
                                <form method="POST">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="alert alert-warning border-0 small">
                                                <i class="fas fa-lock me-2"></i> For security, please choose a strong
                                                password.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" name="current_password" class="form-control"
                                                required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" name="confirm_password" class="form-control"
                                                required>
                                        </div>
                                        <div class="col-12 text-end mt-4">
                                            <button type="submit" name="change_password" class="btn btn-gold">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>