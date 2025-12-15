<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); exit();
}

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
$subjects = $conn->query("SELECT s.*, c.class_name 
                          FROM subjects s 
                          JOIN classes c ON s.class_id = c.class_id 
                          WHERE s.teacher_id = $uid 
                          ORDER BY c.class_name, s.subject_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important;
        display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Profile Sidebar */
    .profile-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
    .profile-header-bg { height: 100px; background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%); }
    .avatar-wrapper { margin-top: -50px; text-align: center; }
    .avatar-xl { width: 100px; height: 100px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover; }
    
    /* Role Badges */
    .badge-role { font-size: 0.75rem; padding: 5px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    .role-admin { background: #333; color: #fff; }
    .role-class-teacher { background: #FFD700; color: #fff; }
    .role-subject-teacher { background: #e3f2fd; color: #1976d2; }

    /* Info Cards */
    .info-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 20px; }
    .card-header-custom { background: white; padding: 15px 20px; border-bottom: 1px solid #f0f0f0; font-weight: 700; color: #555; display: flex; justify-content: space-between; align-items: center; }
    
    /* Lists */
    .info-list-item { padding: 12px 0; border-bottom: 1px dashed #eee; display: flex; justify-content: space-between; align-items: center; }
    .info-list-item:last-child { border-bottom: none; }
    .label-text { font-size: 0.85rem; color: #888; font-weight: 600; text-transform: uppercase; }
    .value-text { font-weight: 500; color: #333; }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Staff Profile</h2>
                    <p class="text-secondary mb-0">Staff ID: <strong><?php echo $user['teacher_id_no'] ? $user['teacher_id_no'] : 'N/A'; ?></strong></p>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="manage_users.php" class="btn btn-light shadow-sm border">
                        <i class="fas fa-arrow-left me-2"></i> Back
                    </a>
                    <a href="edit_user.php?user_id=<?php echo $uid; ?>" class="btn btn-warning fw-bold shadow-sm">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-lg-4">
                    <div class="card profile-card mb-4">
                        <div class="profile-header-bg"></div>
                        <div class="card-body pt-0 text-center">
                            <div class="avatar-wrapper">
                                <?php $avatar = $user['avatar'] ? "../uploads/".$user['avatar'] : "https://ui-avatars.com/api/?name=".$user['full_name']."&background=random"; ?>
                                <img src="<?php echo $avatar; ?>" class="avatar-xl">
                            </div>
                            <h4 class="mt-3 mb-1 fw-bold"><?php echo $user['full_name']; ?></h4>
                            <p class="text-muted mb-2">@<?php echo $user['username']; ?></p>
                            
                            <?php 
                                $r = $user['role'];
                                $badge = ($r=='admin') ? 'role-admin' : (($r=='class_teacher') ? 'role-class-teacher' : 'role-subject-teacher');
                            ?>
                            <span class="badge badge-role <?php echo $badge; ?>"><?php echo str_replace('_', ' ', $r); ?></span>

                            <hr class="my-4">

                            <div class="text-start px-2">
                                <div class="info-list-item">
                                    <span class="label-text"><i class="fas fa-phone me-2"></i> Phone</span>
                                    <span class="value-text"><?php echo $user['phone'] ? $user['phone'] : '-'; ?></span>
                                </div>
                                <div class="info-list-item">
                                    <span class="label-text"><i class="fas fa-id-card me-2"></i> IC No</span>
                                    <span class="value-text"><?php echo $user['ic_no'] ? $user['ic_no'] : '-'; ?></span>
                                </div>
                                <div class="info-list-item">
                                    <span class="label-text"><i class="fas fa-calendar me-2"></i> Joined</span>
                                    <span class="value-text"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    
                    <?php if($user['role'] == 'class_teacher'): ?>
                    <div class="card info-card border-start border-4 border-warning">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><i class="fas fa-chalkboard-teacher text-warning me-2"></i> Class Mentor Assignment</h5>
                                <?php if($class_assigned): ?>
                                    <p class="mb-0 text-muted">Currently managing: <strong><?php echo $class_assigned['class_name']; ?></strong> (Year <?php echo $class_assigned['year']; ?>)</p>
                                <?php else: ?>
                                    <p class="mb-0 text-danger fst-italic">No class assigned yet.</p>
                                <?php endif; ?>
                            </div>
                            <?php if($class_assigned): ?>
                                <a href="view_class.php?class_id=<?php echo $class_assigned['class_id']; ?>" class="btn btn-outline-warning text-dark btn-sm fw-bold">View Class</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card info-card">
                        <div class="card-header-custom">
                            <span><i class="fas fa-book-reader text-primary me-2"></i> Subject Load</span>
                            <span class="badge bg-light text-dark border"><?php echo $subjects->num_rows; ?> Subjects</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Subject</th>
                                            <th>Code</th>
                                            <th>Class</th>
                                            <th class="text-end pe-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($subjects->num_rows > 0): ?>
                                            <?php while($sub = $subjects->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-dark"><?php echo $sub['subject_name']; ?></td>
                                                <td><span class="badge bg-light text-dark border font-monospace"><?php echo $sub['subject_code']; ?></span></td>
                                                <td><span class="fw-bold text-warning"><?php echo $sub['class_name']; ?></span></td>
                                                <td class="text-end pe-4">
                                                    <a href="view_subject.php?subject_id=<?php echo $sub['subject_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        View Subject
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-5 text-muted">No teaching subjects assigned.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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