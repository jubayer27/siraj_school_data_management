<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'subject_teacher' && $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); 
    exit(); 
}

$tid = $_SESSION['user_id'];

// ==========================================
// 2. DATA: CLASS TEACHER ROLE (Home Room)
// ==========================================
$class_q = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $tid");
$my_class = $class_q->fetch_assoc();

$cid = $my_class ? $my_class['class_id'] : 0;
$class_name = $my_class ? $my_class['class_name'] : "No Class Assigned";

$class_stats = ['total'=>0, 'boys'=>0, 'girls'=>0];
$students_prev = null;

if($cid){
    $class_stats = $conn->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as boys,
                            SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as girls
                           FROM students WHERE class_id = $cid")->fetch_assoc();
    
    // Recent Class Students
    $students_prev = $conn->query("SELECT * FROM students WHERE class_id = $cid ORDER BY student_name ASC LIMIT 5");
}

// ==========================================
// 3. DATA: SUBJECT TEACHER ROLE (Teaching)
// ==========================================
// UPDATED: Fetch subjects linked via subject_teachers table
$teaching_sql = "SELECT s.subject_id, s.subject_name, s.subject_code, c.class_name,
                 (SELECT COUNT(*) FROM student_subject_enrollment WHERE subject_id = s.subject_id) as total_students,
                 (SELECT COUNT(*) FROM student_marks sm 
                  JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id 
                  WHERE sse.subject_id = s.subject_id AND sm.exam_type = 'Midterm') as graded_count
                 FROM subjects s 
                 JOIN classes c ON s.class_id = c.class_id 
                 JOIN subject_teachers st ON s.subject_id = st.subject_id
                 WHERE st.teacher_id = $tid";

$my_subjects = $conn->query($teaching_sql);
$subject_count = $my_subjects->num_rows;

// UPDATED: Calculate total unique students taught (Teaching Load)
$total_teaching_students = $conn->query("SELECT count(DISTINCT sse.student_id) as c 
                                         FROM student_subject_enrollment sse 
                                         JOIN subject_teachers st ON sse.subject_id = st.subject_id 
                                         WHERE st.teacher_id = $tid")->fetch_assoc()['c'];

// Notices
$notices = $conn->query("SELECT * FROM notices WHERE audience IN ('all', 'class_teacher', 'subject_teacher') ORDER BY created_at DESC LIMIT 3");
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

    /* Card Styling */
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); height: 100%; transition: 0.2s; }
    .card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

    .icon-square { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 15px; }
    
    /* Custom Colors */
    .bg-light-blue { background: #e3f2fd; color: #1565c0; }
    .bg-light-gold { background: #fff8e1; color: #fbc02d; }
    .bg-light-purple { background: #f3e5f5; color: #8e44ad; }
    .bg-light-green { background: #e8f5e9; color: #2e7d32; }

    /* Progress Bar */
    .progress-thin { height: 6px; border-radius: 3px; background-color: #eee; margin-top: 5px; }
    
    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Teacher Dashboard</h2>
                    <p class="text-secondary mb-0">
                        Class Mentor: <strong><?php echo $class_name; ?></strong> | 
                        Teaching: <strong><?php echo $subject_count; ?> Subjects</strong>
                    </p>
                </div>
                <?php if($cid): ?>
                <div>
                    <a href="master_marksheet.php" class="btn btn-dark">
                        <i class="fas fa-table me-2"></i> Master Marksheet
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card p-3 border-start border-4 border-primary">
                        <div class="d-flex align-items-center">
                            <div class="icon-square bg-light-blue">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $class_stats['total']; ?></h3>
                                <small class="text-secondary fw-bold text-uppercase">My Class Size</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card p-3 border-start border-4 border-warning">
                        <div class="d-flex align-items-center">
                            <div class="icon-square bg-light-gold">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $subject_count; ?></h3>
                                <small class="text-secondary fw-bold text-uppercase">Subjects Taught</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-square bg-light-purple">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $total_teaching_students; ?></h3>
                                <small class="text-secondary fw-bold text-uppercase">Total Students</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-square bg-light-green">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $notices->num_rows; ?></h3>
                                <small class="text-secondary fw-bold text-uppercase">New Notices</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header bg-white py-3 border-bottom-0">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-book me-2 text-warning"></i> My Teaching Subjects</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Subject & Class</th>
                                            <th class="text-center">Enrolled</th>
                                            <th>Grading Status</th>
                                            <th class="text-end pe-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if($subject_count > 0):
                                            $my_subjects->data_seek(0); // Reset pointer
                                            while($sub = $my_subjects->fetch_assoc()): 
                                                $pct = $sub['total_students'] > 0 ? round(($sub['graded_count'] / $sub['total_students']) * 100) : 0;
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="fw-bold text-dark d-block"><?php echo $sub['subject_name']; ?></span>
                                                <small class="text-muted"><?php echo $sub['class_name']; ?> &bull; <?php echo $sub['subject_code']; ?></small>
                                            </td>
                                            <td class="text-center fw-bold"><?php echo $sub['total_students']; ?></td>
                                            <td style="width: 30%;">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>Midterm</span>
                                                    <span class="fw-bold"><?php echo $pct; ?>%</span>
                                                </div>
                                                <div class="progress progress-thin">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $pct; ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="../subjectTeacher/manage_marks.php?subject_id=<?php echo $sub['subject_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-pen-alt me-1"></i> Grade
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">No teaching subjects assigned yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-user-graduate me-2 text-primary"></i> My Class List</h5>
                            <?php if($cid): ?><a href="my_class_students.php" class="btn btn-sm btn-light text-primary">View All</a><?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if($cid && $students_prev && $students_prev->num_rows > 0): ?>
                                <ul class="list-group list-group-flush">
                                <?php while($stu = $students_prev->fetch_assoc()): ?>
                                    <li class="list-group-item px-4 py-2 border-bottom-0">
                                        <div class="d-flex align-items-center">
                                            <?php $img = $stu['photo'] ? "../uploads/".$stu['photo'] : "https://ui-avatars.com/api/?name=".$stu['student_name']."&background=random"; ?>
                                            <img src="<?php echo $img; ?>" class="rounded-circle me-3" width="36" height="36" style="object-fit:cover;">
                                            <div class="flex-grow-1">
                                                <span class="fw-bold text-dark d-block"><?php echo $stu['student_name']; ?></span>
                                                <small class="text-muted font-monospace"><?php echo $stu['school_register_no']; ?></small>
                                            </div>
                                            <a href="view_student_full.php?student_id=<?php echo $stu['student_id']; ?>" class="btn btn-sm btn-light text-secondary"><i class="fas fa-chevron-right"></i></a>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fs-1 mb-3 opacity-25"></i>
                                    <p>No students assigned to your class.</p>
                                </div>
                            <?php endif; ?>
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