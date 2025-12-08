<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & VALIDATION
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }
if(!isset($_GET['user_id'])){ echo "<script>window.location='manage_users.php';</script>"; exit; }

$uid = $_GET['user_id'];

// 2. HANDLE FORM SUBMISSION
if(isset($_POST['update_user'])){
    $name = $_POST['full_name'];
    $role = $_POST['role'];
    $staff_id = $_POST['teacher_id_no'];
    $phone = $_POST['phone'];
    $ic = $_POST['ic_no'];
    $username = $_POST['username'];
    
    // Check for Duplicate Username (excluding current user)
    $dupCheck = $conn->query("SELECT user_id FROM users WHERE username='$username' AND user_id != $uid");
    if($dupCheck->num_rows > 0){
        $error = "Username '$username' is already taken by another user.";
    } else {
        // A. Handle Avatar Upload
        $avatar_sql = ""; 
        if(isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != ""){
            $target_dir = "../uploads/";
            if(!is_dir($target_dir)) mkdir($target_dir); // Create dir if not exists
            
            $file_ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid("user_") . "." . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if(move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)){
                $avatar_sql = ", avatar='$new_filename'";
            }
        }

        // B. Handle Password Update
        $pass_sql = "";
        if(!empty($_POST['password'])){
            $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pass_sql = ", password='$new_pass'";
        }

        // C. Execute Update
        $sql = "UPDATE users SET full_name=?, role=?, teacher_id_no=?, phone=?, ic_no=?, username=? $pass_sql $avatar_sql WHERE user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $name, $role, $staff_id, $phone, $ic, $username, $uid);
        
        if($stmt->execute()){
            $success = "Profile updated successfully!";
            // Auto-refresh to show changes
            echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 1500);</script>";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

// 3. FETCH USER DATA
$user = $conn->query("SELECT * FROM users WHERE user_id = $uid")->fetch_assoc();
if(!$user) die("User not found.");

// 4. FETCH CONTEXT (Teaching Load)
$subjects_taught = $conn->query("SELECT s.subject_name, s.subject_code, c.class_name FROM subjects s JOIN classes c ON s.class_id = c.class_id WHERE s.teacher_id = $uid");
$class_managed = $conn->query("SELECT class_name, year FROM classes WHERE class_teacher_id = $uid")->fetch_assoc();
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Edit User Profile</h1>
                <p>Managing account for: <strong><?php echo $user['full_name']; ?></strong></p>
            </div>
            <a href="manage_users.php" class="btn btn-secondary" style="background:#e0e0e0; color:#333;">
                <i class="fas fa-arrow-left"></i> Back to Directory
            </a>
        </div>

        <?php if(isset($success)) echo "<div class='alert-box success'><i class='fas fa-check-circle'></i> $success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert-box error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <form method="POST" enctype="multipart/form-data" autocomplete="off">
            <div class="profile-grid">
                
                <div class="col-left">
                    <div class="card center-content">
                        <div class="avatar-upload">
                            <div class="avatar-preview">
                                <?php $img = $user['avatar'] ? "../uploads/".$user['avatar'] : "https://ui-avatars.com/api/?name=".$user['full_name']."&background=FFD700&color=fff"; ?>
                                <img id="imagePreview" src="<?php echo $img; ?>" alt="User Avatar">
                            </div>
                            <div class="avatar-edit">
                                <input type='file' name="avatar" id="imageUpload" accept=".png, .jpg, .jpeg" />
                                <label for="imageUpload"><i class="fas fa-camera"></i> Change Photo</label>
                            </div>
                        </div>
                        <h3 style="margin:15px 0 5px;"><?php echo $user['full_name']; ?></h3>
                        <span class="role-badge"><?php echo strtoupper(str_replace('_', ' ', $user['role'])); ?></span>
                    </div>

                    <div class="card info-card">
                        <h4><i class="fas fa-briefcase" style="color:#DAA520;"></i> Current Assignments</h4>
                        
                        <?php if($user['role'] == 'class_teacher' && $class_managed): ?>
                            <div class="assignment-item">
                                <strong>Class Mentor:</strong><br>
                                <?php echo $class_managed['class_name']; ?> (<?php echo $class_managed['year']; ?>)
                            </div>
                        <?php endif; ?>

                        <div class="assignment-item">
                            <strong>Subjects Taught:</strong>
                            <ul style="padding-left:20px; margin:5px 0; color:#666;">
                            <?php if($subjects_taught->num_rows > 0): ?>
                                <?php while($sub = $subjects_taught->fetch_assoc()): ?>
                                    <li><?php echo $sub['subject_name']; ?> <small>(<?php echo $sub['class_name']; ?>)</small></li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li><i>No subjects assigned.</i></li>
                            <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-right">
                    <div class="card">
                        <div class="section-title">Personal Details</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Full Name</label>
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Staff ID / Code</label>
                                <div class="input-icon">
                                    <i class="fas fa-id-badge"></i>
                                    <input type="text" name="teacher_id_no" value="<?php echo $user['teacher_id_no']; ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>IC Number</label>
                                <div class="input-icon">
                                    <i class="fas fa-id-card"></i>
                                    <input type="text" name="ic_no" value="<?php echo $user['ic_no']; ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <div class="input-icon">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" name="phone" value="<?php echo $user['phone']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="section-title" style="margin-top:30px;">System Access</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>System Role</label>
                                <div class="input-icon">
                                    <i class="fas fa-user-tag"></i>
                                    <select name="role">
                                        <option value="subject_teacher" <?php if($user['role']=='subject_teacher') echo 'selected'; ?>>Subject Teacher</option>
                                        <option value="class_teacher" <?php if($user['role']=='class_teacher') echo 'selected'; ?>>Class Teacher</option>
                                        <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Login Username</label>
                                <div class="input-icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <input type="text" name="username" value="<?php echo $user['username']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label>Change Password</label>
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" placeholder="Enter new password only if changing it" autocomplete="new-password">
                                </div>
                                <small style="color:#888;">Leave empty to keep the current password.</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_user" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('imageUpload').onchange = function (evt) {
    var tgt = evt.target || window.event.srcElement,
        files = tgt.files;
    if (FileReader && files && files.length) {
        var fr = new FileReader();
        fr.onload = function () {
            document.getElementById('imagePreview').src = fr.result;
        }
        fr.readAsDataURL(files[0]);
    }
}
</script>

<style>
    /* Layout */
    .profile-grid { display: grid; grid-template-columns: 300px 1fr; gap: 25px; align-items: start; }
    @media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }

    /* Alerts */
    .alert-box { padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .alert-box.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-box.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    /* Avatar Upload */
    .avatar-upload { position: relative; max-width: 150px; margin: 10px auto; }
    .avatar-preview { width: 150px; height: 150px; position: relative; border-radius: 50%; border: 4px solid #f8f8f8; box-shadow: 0px 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
    .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-edit { position: absolute; right: 0; bottom: 0; }
    .avatar-edit input { display: none; }
    .avatar-edit label { display: inline-block; width: 34px; height: 34px; margin-bottom: 0; border-radius: 100%; background: #DAA520; color: white; border: 2px solid #fff; box-shadow: 0px 2px 4px 0px rgba(0,0,0,0.2); cursor: pointer; font-weight: normal; transition: all .2s ease-in-out; text-align: center; line-height: 34px; }
    .avatar-edit label:hover { background: #f1c40f; }

    /* Cards & Form */
    .center-content { text-align: center; }
    .role-badge { background: #eee; color: #555; padding: 4px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; letter-spacing: 0.5px; }
    .info-card h4 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 0; color: #444; }
    .section-title { font-size: 1.1rem; font-weight: 600; color: #DAA520; margin-bottom: 15px; border-left: 4px solid #DAA520; padding-left: 10px; }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .full-width { grid-column: 1 / -1; }
    
    .input-icon { position: relative; }
    .input-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; }
    .input-icon input, .input-icon select { padding-left: 35px; width: 100%; }
    
    .form-actions { margin-top: 30px; text-align: right; border-top: 1px solid #eee; padding-top: 20px; }
    .btn-lg { padding: 12px 30px; font-size: 1rem; }
    .assignment-item { background: #fafafa; padding: 10px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #ddd; }
</style>
</body>
</html>