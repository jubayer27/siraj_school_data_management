<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['user_id'])) {
    echo "<script>window.location='manage_users.php';</script>";
    exit;
}

$uid = $_GET['user_id'];
$success = "";
$error = "";

// 2. HANDLE FORM SUBMISSION
if (isset($_POST['update_user'])) {
    $name = $_POST['full_name'];
    $role = $_POST['role'];
    $staff_id = $_POST['teacher_id_no'];
    $phone = $_POST['phone'];
    $ic = $_POST['ic_no'];
    $username = $_POST['username'];
    $email = $_POST['email']; // <--- 1. Capture Email

    // Check Duplicate Username
    $dupCheck = $conn->query("SELECT user_id FROM users WHERE username='$username' AND user_id != $uid");

    if ($dupCheck->num_rows > 0) {
        $error = "Username '$username' is already taken.";
    } else {
        // A. Handle Avatar Upload
        $avatar_sql = "";
        if (isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != "") {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir);

            $file_ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid("user_") . "." . $file_ext;

            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_dir . $new_filename)) {
                $avatar_sql = ", avatar='$new_filename'";
            }
        }

        // B. Handle Password Update
        $pass_sql = "";
        if (!empty($_POST['password'])) {
            $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pass_sql = ", password='$new_pass'";
        }

        // C. Execute Profile Update
        // <--- 2. Added email=? to SQL
        $sql = "UPDATE users SET full_name=?, role=?, teacher_id_no=?, phone=?, ic_no=?, username=?, email=? $pass_sql $avatar_sql WHERE user_id=?";
        $stmt = $conn->prepare($sql);
        // <--- 3. Updated types to "sssssssi" (added one 's') and added $email variable
        $stmt->bind_param("sssssssi", $name, $role, $staff_id, $phone, $ic, $username, $email, $uid);
        $stmt->execute();

        // ---------------------------------------------------------
        // D. HANDLE SUBJECT ASSIGNMENTS (UPDATED FOR MANY-TO-MANY)
        // ---------------------------------------------------------
        if ($role != 'admin') {
            $conn->query("DELETE FROM subject_teachers WHERE teacher_id = $uid");

            if (isset($_POST['assigned_subjects']) && !empty($_POST['assigned_subjects'])) {
                $stmt_insert = $conn->prepare("INSERT INTO subject_teachers (subject_id, teacher_id) VALUES (?, ?)");
                
                foreach ($_POST['assigned_subjects'] as $sub_id) {
                    $stmt_insert->bind_param("ii", $sub_id, $uid);
                    $stmt_insert->execute();
                }
            }
        }

        $success = "User profile and subject assignments updated successfully!";
        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 1500);</script>";
    }
}

// 3. FETCH USER DATA
$user = $conn->query("SELECT * FROM users WHERE user_id = $uid")->fetch_assoc();
if (!$user) die("User not found.");

// 4. FETCH CONTEXT (Class Mentorship)
$class_managed = $conn->query("SELECT class_name, year FROM classes WHERE class_teacher_id = $uid")->fetch_assoc();

// ---------------------------------------------------------
// 5. FETCH DATA FOR ASSIGNMENT DISPLAY
// ---------------------------------------------------------

$all_subjects_sql = "SELECT s.subject_id, s.subject_name, s.subject_code, c.class_name
                     FROM subjects s 
                     JOIN classes c ON s.class_id = c.class_id 
                     ORDER BY c.year DESC, c.class_name ASC, s.subject_name ASC";
$all_subjects_res = $conn->query($all_subjects_sql);
$subjects_by_class = [];
while($row = $all_subjects_res->fetch_assoc()){
    $subjects_by_class[$row['class_name']][] = $row;
}

$my_subs_res = $conn->query("SELECT subject_id FROM subject_teachers WHERE teacher_id = $uid");
$my_assigned_ids = [];
while($row = $my_subs_res->fetch_assoc()){
    $my_assigned_ids[] = $row['subject_id'];
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important; margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important; display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Avatar & Card Styles */
    .avatar-upload { position: relative; max-width: 150px; margin: 0 auto 20px; }
    .avatar-preview { width: 150px; height: 150px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-edit { position: absolute; right: 0; bottom: 10px; }
    .avatar-edit input { display: none; }
    .avatar-edit label { width: 36px; height: 36px; background: #DAA520; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); }

    .edit-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02); margin-bottom: 20px; }
    .section-title { font-size: 1rem; color: #DAA520; font-weight: 700; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; }
    
    .role-badge { background: #eee; color: #555; padding: 4px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; letter-spacing: 0.5px; }
    
    /* Subject List Styles */
    .subject-list-container { max-height: 400px; overflow-y: auto; padding-right: 5px; }
    .class-group-header { background: #f8f9fa; padding: 8px 10px; font-weight: bold; color: #555; border-radius: 6px; margin-top: 10px; margin-bottom: 5px; font-size: 0.9rem; }
    .subject-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
    .subject-item:hover { background-color: #fffcf5; }
    .taken-badge { font-size: 0.75rem; color: #e74c3c; background: #fadbd8; padding: 2px 6px; border-radius: 4px; margin-left: 10px; }
    
    /* FIX: Input Icons & Padding Overlap */
    .input-icon { position: relative; }
    .input-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; z-index: 5; }
    
    .form-control, .form-select { 
        padding-left: 40px !important; 
        border-radius: 8px; 
    }
    .form-control:focus { border-color: #FFD700; box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1); }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Edit User Profile</h2>
                    <p class="text-secondary mb-0">Managing: <strong><?php echo $user['full_name']; ?></strong></p>
                </div>
                <a href="manage_users.php" class="btn btn-light border shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i> Back to Directory
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                <div class="row g-4">

                    <div class="col-lg-3">
                        <div class="card edit-card text-center p-4">
                            <div class="avatar-upload">
                                <div class="avatar-preview">
                                    <?php $img = $user['avatar'] ? "../uploads/" . $user['avatar'] : "https://ui-avatars.com/api/?name=" . $user['full_name'] . "&background=random"; ?>
                                    <img id="imagePreview" src="<?php echo $img; ?>">
                                </div>
                                <div class="avatar-edit">
                                    <input type='file' name="avatar" id="imageUpload" accept=".png, .jpg, .jpeg" />
                                    <label for="imageUpload"><i class="fas fa-camera"></i></label>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-1"><?php echo $user['full_name']; ?></h5>
                            <span class="role-badge"><?php echo strtoupper(str_replace('_', ' ', $user['role'])); ?></span>

                            <hr class="my-4">

                            <div class="text-start">
                                <?php if ($user['role'] == 'class_teacher'): ?>
                                    <h6 class="fw-bold text-muted small text-uppercase mb-2"><i class="fas fa-crown me-1 text-warning"></i> Class Mentor</h6>
                                    <div class="p-2 bg-light rounded border border-warning">
                                        <?php if($class_managed): ?>
                                            <div class="fw-bold text-dark"><?php echo $class_managed['class_name']; ?></div>
                                            <small class="text-muted">Year <?php echo $class_managed['year']; ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">No class assigned.</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card edit-card h-100">
                            <div class="card-body p-4">
                                <div class="section-title">Personal Details</div>
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted">Full Name</label>
                                        <div class="input-icon">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Staff ID</label>
                                        <div class="input-icon">
                                            <i class="fas fa-id-badge"></i>
                                            <input type="text" name="teacher_id_no" class="form-control" value="<?php echo $user['teacher_id_no']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">IC Number</label>
                                        <div class="input-icon">
                                            <i class="fas fa-id-card"></i>
                                            <input type="text" name="ic_no" class="form-control" value="<?php echo $user['ic_no']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Phone Number</label>
                                        <div class="input-icon">
                                            <i class="fas fa-phone"></i>
                                            <input type="text" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Email Address</label>
                                        <div class="input-icon">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email" name="email" class="form-control" value="<?php echo isset($user['email']) ? $user['email'] : ''; ?>" placeholder="user@example.com">
                                        </div>
                                    </div>
                                    
                                </div>

                                <div class="section-title">Account Access</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Role</label>
                                        <div class="input-icon">
                                            <i class="fas fa-user-tag"></i>
                                            <select name="role" class="form-select">
                                                <option value="subject_teacher" <?php echo ($user['role'] == 'subject_teacher') ? 'selected' : ''; ?>>Subject Teacher</option>
                                                <option value="class_teacher" <?php echo ($user['role'] == 'class_teacher') ? 'selected' : ''; ?>>Class Teacher</option>
                                                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Username</label>
                                        <div class="input-icon">
                                            <i class="fas fa-sign-in-alt"></i>
                                            <input type="text" name="username" class="form-control" value="<?php echo $user['username']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted">New Password</label>
                                        <div class="input-icon">
                                            <i class="fas fa-lock"></i>
                                            <input type="password" name="password" class="form-control" placeholder="Leave empty to keep current">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card edit-card h-100">
                            <div class="card-body p-4">
                                <div class="section-title">
                                    <i class="fas fa-book-reader me-2"></i> Subject Allocation
                                </div>
                                <p class="small text-muted mb-3">Check the subjects assigned to this teacher. You can assign multiple teachers to one subject.</p>
                                
                                <div class="subject-list-container border rounded p-2 bg-white">
                                    <?php 
                                    if(empty($subjects_by_class)): 
                                        echo "<p class='text-center text-muted py-3'>No classes/subjects found.</p>";
                                    else:
                                        foreach($subjects_by_class as $class_name => $subs): 
                                    ?>
                                        <div class="class-group-header"><?php echo $class_name; ?></div>
                                        <?php foreach($subs as $s): 
                                            // CHECK: Is this subject in the user's assigned list?
                                            $is_assigned = in_array($s['subject_id'], $my_assigned_ids);
                                        ?>
                                        <div class="subject-item">
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="assigned_subjects[]" 
                                                       value="<?php echo $s['subject_id']; ?>" 
                                                       id="sub_<?php echo $s['subject_id']; ?>"
                                                       <?php echo $is_assigned ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="sub_<?php echo $s['subject_id']; ?>">
                                                    <strong><?php echo $s['subject_name']; ?></strong>
                                                    <span class="text-muted" style="font-size:0.75rem;">(<?php echo $s['subject_code']; ?>)</span>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 text-end mb-5">
                        <a href="manage_users.php" class="btn btn-secondary px-4 me-2">Cancel</a>
                        <button type="submit" name="update_user" class="btn btn-warning fw-bold px-5">
                            <i class="fas fa-save me-2"></i> Save All Changes
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Avatar Preview
    document.getElementById('imageUpload').onchange = function (evt) {
        var tgt = evt.target || window.event.srcElement, files = tgt.files;
        if (FileReader && files && files.length) {
            var fr = new FileReader();
            fr.onload = function () { document.getElementById('imagePreview').src = fr.result; }
            fr.readAsDataURL(files[0]);
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>