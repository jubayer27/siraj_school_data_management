<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

// 2. HANDLE DELETION
if(isset($_GET['delete_id'])){
    $did = $_GET['delete_id'];
    if($did != $_SESSION['user_id']){ 
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $did);
        $stmt->execute();
        echo "<script>window.location='manage_users.php?msg=deleted';</script>";
    } else {
        $error = "System Safety: You cannot delete your own admin account.";
    }
}

// 3. HANDLE ADD USER
if(isset($_POST['add_user'])){
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $ic_no = $_POST['ic_no'];
    $staff_id = $_POST['teacher_id_no'];

    // Check duplicate username
    $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
    if($check->num_rows > 0){
        $error = "Error: Username '$username' is already taken.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, phone, ic_no, teacher_id_no, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $username, $password, $full_name, $role, $phone, $ic_no, $staff_id);
        
        if($stmt->execute()){
            $success = "New user created successfully!";
            // Clear post data to prevent resubmission
            echo "<script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

// 4. BUILD QUERY (With Avatar & Filters)
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT u.*, 
        (SELECT GROUP_CONCAT(s.subject_name SEPARATOR ', ') FROM subjects s WHERE s.teacher_id = u.user_id) as teaching_subjects 
        FROM users u WHERE 1=1";

if($filter_role) $sql .= " AND u.role = '$filter_role'";
if($search_query) $sql .= " AND (u.full_name LIKE '%$search_query%' OR u.teacher_id_no LIKE '%$search_query%')";

$sql .= " ORDER BY u.created_at DESC";
$users = $conn->query($sql);
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Staff & User Directory</h1>
                <p>Manage system access and teaching staff.</p>
            </div>
            <button onclick="toggleForm()" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Staff
            </button>
        </div>

        <?php if(isset($success)) echo "<div class='alert-box success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert-box error'>$error</div>"; ?>
        <?php if(isset($_GET['msg'])){
            if($_GET['msg']=='deleted') echo "<div class='alert-box error'>User account deleted.</div>";
            if($_GET['msg']=='updated') echo "<div class='alert-box success'>User profile updated successfully.</div>";
        } ?>

        <div class="card" id="addUserForm" style="display:none; border-top: 5px solid #FFD700;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Create New Profile</h3>
                <button type="button" onclick="toggleForm()" style="background:none; border:none; color:#999; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" class="form-grid" autocomplete="off">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="subject_teacher">Subject Teacher</option>
                        <option value="class_teacher">Class Teacher</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Staff ID / Teacher No</label>
                    <input type="text" name="teacher_id_no" placeholder="e.g. T-2025-001">
                </div>
                
                <div class="form-group">
                    <label>IC Number</label>
                    <input type="text" name="ic_no">
                </div>
                <div class="form-group">
                    <label>Phone Contact</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>Login Username</label>
                    <input type="text" name="username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <div style="grid-column: 1 / -1; margin-top:15px; text-align:right;">
                    <button type="button" onclick="toggleForm()" class="btn btn-secondary" style="margin-right:10px;">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>

        <div class="card" style="padding:15px; background:#fdfdfd; display:flex; gap:15px; align-items:center;">
            <form method="GET" style="display:flex; gap:10px; width:100%;">
                <select name="role" style="width:200px;">
                    <option value="">All Roles</option>
                    <option value="subject_teacher" <?php if($filter_role=='subject_teacher') echo 'selected'; ?>>Subject Teacher</option>
                    <option value="class_teacher" <?php if($filter_role=='class_teacher') echo 'selected'; ?>>Class Teacher</option>
                    <option value="admin" <?php if($filter_role=='admin') echo 'selected'; ?>>Admin</option>
                </select>
                <input type="text" name="search" placeholder="Search by Name or ID..." value="<?php echo $search_query; ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <?php if($filter_role || $search_query): ?>
                    <a href="manage_users.php" class="btn btn-danger" style="background:#888;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Staff Info</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Teaching Subjects</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($users->num_rows > 0): ?>
                            <?php while($row = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center;">
                                        <?php 
                                            $avatar = $row['avatar'] ? "../uploads/".$row['avatar'] : "https://ui-avatars.com/api/?name=".$row['full_name']."&background=f0f0f0&color=DAA520";
                                        ?>
                                        <img src="<?php echo $avatar; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:12px; border:1px solid #eee;">
                                        
                                        <div>
                                            <div style="font-weight:600; color:#333;"><?php echo $row['full_name']; ?></div>
                                            <div style="font-size:0.8rem; color:#888;">ID: <?php echo $row['teacher_id_no'] ? $row['teacher_id_no'] : 'N/A'; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:0.9rem;"><i class="fas fa-phone" style="color:#ccc;"></i> <?php echo $row['phone'] ? $row['phone'] : '-'; ?></div>
                                    <div style="font-size:0.8rem; color:#888;">@<?php echo $row['username']; ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $badgeColor = '#eee'; $textColor = '#333';
                                        if($row['role']=='admin') { $badgeColor='#333'; $textColor='#fff'; }
                                        if($row['role']=='class_teacher') { $badgeColor='#FFD700'; $textColor='#fff'; }
                                        if($row['role']=='subject_teacher') { $badgeColor='#f0f8ff'; $textColor='#2980b9'; }
                                    ?>
                                    <span style="background:<?php echo $badgeColor; ?>; color:<?php echo $textColor; ?>; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:bold; text-transform:uppercase;">
                                        <?php echo str_replace('_', ' ', $row['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['teaching_subjects']): ?>
                                        <div style="font-size:0.85rem; color:#555; max-width:250px; line-height:1.4;">
                                            <?php echo $row['teaching_subjects']; ?>
                                        </div>
                                    <?php elseif($row['role'] != 'admin'): ?>
                                        <span style="color:#ccc; font-style:italic; font-size:0.85rem;">No subjects assigned</span>
                                    <?php else: ?>
                                        <span style="color:#ccc;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <a href="view_user.php?user_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm" title="View Profile" style="background:#5bc0de;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="edit_user.php?user_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm" title="Edit" style="background:#f0ad4e;">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if($row['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="manage_users.php?delete_id=<?php echo $row['user_id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to remove this user?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:#888;">No users found matching your criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleForm() {
    var x = document.getElementById("addUserForm");
    if (x.style.display === "none") {
        x.style.display = "block";
        // Scroll to form
        x.scrollIntoView({behavior: "smooth"});
    } else {
        x.style.display = "none";
    }
}
</script>

<style>
    .alert-box { padding:15px; border-radius:4px; margin-bottom:20px; font-weight:500; }
    .alert-box.success { background:#e8f5e9; color:#2e7d32; border:1px solid #c3e6cb; }
    .alert-box.error { background:#ffebee; color:#c62828; border:1px solid #f5c6cb; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    .btn-secondary { background: #e0e0e0; color: #333; }
    .btn-secondary:hover { background: #d0d0d0; }
</style>
</body>
</html>