<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. CHECK ID
if(!isset($_GET['user_id'])){
    echo "<script>window.location='manage_users.php';</script>";
    exit();
}
$uid = $_GET['user_id'];

// 2. FETCH USER DETAILS
$u_query = $conn->query("SELECT * FROM users WHERE user_id = $uid");
$user = $u_query->fetch_assoc();

if(!$user) die("User not found.");

// 3. FETCH ASSIGNMENTS
// A. Class Assigned (If Class Teacher)
$class_assigned = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $uid")->fetch_assoc();

// B. Subjects Taught
$subjects = $conn->query("SELECT s.*, c.class_name FROM subjects s 
                          JOIN classes c ON s.class_id = c.class_id 
                          WHERE s.teacher_id = $uid");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Staff Profile</h1>
                <p>Viewing details for: <strong><?php echo $user['full_name']; ?></strong></p>
            </div>
            <a href="manage_users.php" class="btn btn-secondary" style="background:#e0e0e0; color:#333;">
                <i class="fas fa-arrow-left"></i> Back to Directory
            </a>
        </div>

        <div class="profile-layout">
            
            <div class="profile-sidebar">
                <div class="card center-content">
                    <div class="avatar-container">
                        <?php 
                            // Check for uploaded avatar, else use UI Avatars API
                            $avatar = $user['avatar'] ? "../uploads/".$user['avatar'] : "https://ui-avatars.com/api/?name=".$user['full_name']."&background=f9f9f9&color=DAA520&size=150"; 
                        ?>
                        <img src="<?php echo $avatar; ?>" alt="Profile">
                    </div>
                    <h2 class="profile-name"><?php echo $user['full_name']; ?></h2>
                    
                    <?php 
                        // Badge Logic
                        $role_display = strtoupper(str_replace('_', ' ', $user['role']));
                        $badge_class = 'badge-default';
                        if($user['role'] == 'admin') $badge_class = 'badge-admin';
                        if($user['role'] == 'class_teacher') $badge_class = 'badge-class';
                        if($user['role'] == 'subject_teacher') $badge_class = 'badge-subject';
                    ?>
                    <span class="role-badge <?php echo $badge_class; ?>"><?php echo $role_display; ?></span>

                    <div class="profile-meta">
                        <div class="meta-row">
                            <i class="fas fa-id-badge"></i> 
                            <span><?php echo $user['teacher_id_no'] ? $user['teacher_id_no'] : 'N/A'; ?></span>
                        </div>
                        <div class="meta-row">
                            <i class="fas fa-envelope"></i> 
                            <span>@<?php echo $user['username']; ?></span>
                        </div>
                        <div class="meta-row">
                            <i class="fas fa-phone-alt"></i> 
                            <span><?php echo $user['phone'] ? $user['phone'] : 'No Phone'; ?></span>
                        </div>
                         <div class="meta-row">
                            <i class="fas fa-calendar-alt"></i> 
                            <span>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>

                    <a href="edit_user.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>

            <div class="profile-main">
                
                <?php if($user['role'] == 'class_teacher'): ?>
                <div class="card highlight-card">
                    <div class="card-icon"><i class="fas fa-crown"></i></div>
                    <div class="card-content">
                        <h3>Class Mentor Assignment</h3>
                        <?php if($class_assigned): ?>
                            <div class="assignment-box">
                                <div class="assignment-details">
                                    <span class="class-name"><?php echo $class_assigned['class_name']; ?></span>
                                    <span class="academic-year">Year: <?php echo $class_assigned['year']; ?></span>
                                </div>
                                <a href="view_class.php?class_id=<?php echo $class_assigned['class_id']; ?>" class="btn btn-sm btn-outline">View Class</a>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">No class assigned yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="section-header">
                        <h3><i class="fas fa-book-reader" style="color:#DAA520;"></i> Teaching Load</h3>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Subject Code</th>
                                    <th>Assigned Class</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($subjects->num_rows > 0): ?>
                                    <?php while($sub = $subjects->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight:600; color:#333;"><?php echo $sub['subject_name']; ?></td>
                                        <td><span class="code-badge"><?php echo $sub['subject_code']; ?></span></td>
                                        <td><?php echo $sub['class_name']; ?></td>
                                        <td style="text-align:right;">
                                            <a href="../subjectTeacher/view_students.php?subject_id=<?php echo $sub['subject_id']; ?>" class="btn-link">View Students</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="empty-table">No subjects assigned to this teacher.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="section-header">
                         <h3><i class="fas fa-info-circle" style="color:#aaa;"></i> Personal Details</h3>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>IC / Passport No</label>
                            <div><?php echo $user['ic_no'] ? $user['ic_no'] : '-'; ?></div>
                        </div>
                         <div class="detail-item">
                            <label>Account Status</label>
                            <div><span style="color:green; font-weight:bold;">Active</span></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    /* Layout */
    .profile-layout { display: grid; grid-template-columns: 320px 1fr; gap: 25px; align-items: start; }
    @media (max-width: 900px) { .profile-layout { grid-template-columns: 1fr; } }

    /* Profile Sidebar */
    .center-content { text-align: center; }
    .avatar-container { width: 140px; height: 140px; margin: 0 auto 20px; border-radius: 50%; padding: 5px; border: 1px solid #eee; }
    .avatar-container img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
    .profile-name { margin: 10px 0 5px; font-size: 1.5rem; color: #333; }
    
    /* Roles Badges */
    .role-badge { padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; margin-bottom: 25px; }
    .badge-admin { background: #333; color: #fff; }
    .badge-class { background: #FFD700; color: #fff; }
    .badge-subject { background: #e3f2fd; color: #1976d2; }

    /* Meta Info */
    .profile-meta { text-align: left; margin-bottom: 25px; border-top: 1px solid #f0f0f0; padding-top: 20px; }
    .meta-row { display: flex; align-items: center; margin-bottom: 12px; color: #555; font-size: 0.95rem; }
    .meta-row i { width: 25px; color: #ccc; text-align: center; margin-right: 10px; }
    .btn-block { display: block; width: 100%; text-align: center; box-sizing: border-box; }

    /* Cards & Assignments */
    .highlight-card { border-left: 5px solid #FFD700; display: flex; align-items: center; padding: 20px; }
    .card-icon { font-size: 2.5rem; color: #FFD700; margin-right: 20px; opacity: 0.8; }
    .card-content { flex: 1; }
    .card-content h3 { margin: 0 0 10px 0; font-size: 1.1rem; color: #555; }
    
    .assignment-box { display: flex; justify-content: space-between; align-items: center; background: #fffcf0; padding: 10px 15px; border-radius: 6px; border: 1px solid #ffe082; }
    .class-name { font-weight: bold; font-size: 1.1rem; color: #333; display: block; }
    .academic-year { font-size: 0.85rem; color: #777; }
    .btn-outline { border: 1px solid #DAA520; color: #DAA520; background: transparent; padding: 5px 12px; }
    .btn-outline:hover { background: #DAA520; color: white; }
    .empty-state { color: #888; font-style: italic; margin: 0; }

    /* Table Styles */
    .section-header { border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px; }
    .section-header h3 { margin: 0; font-size: 1.1rem; }
    .code-badge { background: #f0f0f0; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; font-family: monospace; color: #555; }
    .btn-link { color: #3498db; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
    .btn-link:hover { text-decoration: underline; }
    .empty-table { text-align: center; padding: 30px; color: #999; font-style: italic; }

    /* Details Grid */
    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .detail-item label { display: block; font-size: 0.8rem; color: #999; text-transform: uppercase; margin-bottom: 5px; }
    .detail-item div { font-size: 1rem; color: #333; font-weight: 500; }
</style>
</body>
</html>