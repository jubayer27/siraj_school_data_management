<?php
session_start();
include '../config/db.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'subject_teacher' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$tid = $_SESSION['user_id'];

// ==========================================
// 1.5. CLASS SELECTION INTERCEPTOR
// ==========================================
if (isset($_POST['select_class_id'])) {
    $_SESSION['my_class_id'] = intval($_POST['select_class_id']);
    header("Location: dashboard.php");
    exit();
}

$teacher_classes = [];
$show_class_modal = false;
$cid = 0;
$class_name = "No Class Assigned";

$q_classes = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $tid");

if ($q_classes->num_rows > 0) {
    while ($row = $q_classes->fetch_assoc()) {
        $teacher_classes[] = $row;
    }

    if (isset($_SESSION['my_class_id'])) {
        $found = false;
        foreach ($teacher_classes as $tc) {
            if ($tc['class_id'] == $_SESSION['my_class_id']) {
                $cid = $tc['class_id'];
                $class_name = $tc['class_name'];
                $found = true;
                break;
            }
        }
        if (!$found) { unset($_SESSION['my_class_id']); header("Location: dashboard.php"); exit(); }
    
    } elseif ($q_classes->num_rows == 1) {
        $cid = $teacher_classes[0]['class_id'];
        $class_name = $teacher_classes[0]['class_name'];
        $_SESSION['my_class_id'] = $cid;
    } else {
        $show_class_modal = true;
    }
}

// ==========================================
// 2. DATA: CLASS STATS
// ==========================================
$class_stats = ['total' => 0, 'boys' => 0, 'girls' => 0];
$students_prev = null;

if ($cid) {
    $class_stats = $conn->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as boys,
                            SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as girls
                           FROM students WHERE class_id = $cid")->fetch_assoc();

    $students_prev = $conn->query("SELECT * FROM students WHERE class_id = $cid ORDER BY student_name ASC LIMIT 5");
}

// ==========================================
// 3. DATA: SUBJECT TEACHING & NOTICES
// ==========================================
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

$total_teaching_students = $conn->query("SELECT count(DISTINCT sse.student_id) as c 
                                         FROM student_subject_enrollment sse 
                                         JOIN subject_teachers st ON sse.subject_id = st.subject_id 
                                         WHERE st.teacher_id = $tid")->fetch_assoc()['c'];

// --- NEW: FETCH NOTICES ---
$notices = $conn->query("SELECT * FROM notices 
                         WHERE audience IN ('all', 'class_teacher') 
                         ORDER BY created_at DESC LIMIT 4");

include 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; font-family: 'Segoe UI', sans-serif; }
    
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important;
        display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Card Styling */
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 24px; transition: 0.2s; }
    .card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

    .icon-square { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 15px; }
    
    .bg-light-blue { background: #e3f2fd; color: #1565c0; }
    .bg-light-gold { background: #fff8e1; color: #fbc02d; }
    .bg-light-purple { background: #f3e5f5; color: #8e44ad; }
    .bg-light-green { background: #e8f5e9; color: #2e7d32; }

    .progress-thin { height: 6px; border-radius: 3px; background-color: #eee; margin-top: 5px; }
    .modal-backdrop.show { opacity: 0.8; }

    /* --- NOTICE BOARD STYLES --- */
    .notice-list { max-height: 350px; overflow-y: auto; padding: 10px; }
    .notice-item { display: flex; gap: 15px; padding: 15px; border-bottom: 1px solid #f0f0f0; margin-bottom: 10px; border-radius: 8px; background: #fff; border: 1px solid #f0f0f0; }
    .notice-item:last-child { margin-bottom: 0; }
    
    .notice-item.alert { background: #fff5f5; border-left: 4px solid #e74c3c; }
    .notice-item.info { background: #f8faff; border-left: 4px solid #3498db; }
    .notice-item.event { background: #fffcf0; border-left: 4px solid #f1c40f; }

    .notice-date { font-size: 0.75rem; font-weight: bold; color: #999; width: 40px; text-align: center; line-height: 1.2; padding-top: 2px; }
    .notice-content h6 { margin: 0 0 5px; font-weight: 700; color: #333; font-size: 0.95rem; }
    .notice-content p { margin: 0; font-size: 0.85rem; color: #666; line-height: 1.4; }
    
    .event-tag { margin-top: 6px; font-size: 0.7rem; color: #d35400; background: rgba(230, 126, 34, 0.1); display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: 600; }

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
                        Class Mentor: <strong class="text-primary"><?php echo $class_name; ?></strong> 
                        <?php if(count($teacher_classes) > 1): ?>
                            <a href="logout_class.php" class="btn btn-xs btn-outline-secondary ms-2" style="padding: 0px 6px; font-size: 0.75rem;">Switch</a>
                        <?php endif; ?>
                        <span class="mx-2">|</span>
                        Teaching: <strong><?php echo $subject_count; ?> Subjects</strong>
                    </p>
                </div>
                <?php if($cid): ?>
                <div>
                    <a href="master_marksheet.php" class="btn btn-dark shadow-sm">
                        <i class="fas fa-table me-2"></i> Master Marksheet
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card p-3 border-start border-4 border-primary">
                        <div class="d-flex align-items-center">
                            <div class="icon-square bg-light-blue"><i class="fas fa-users-cog"></i></div>
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
                            <div class="icon-square bg-light-gold"><i class="fas fa-book-open"></i></div>
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
                            <div class="icon-square bg-light-purple"><i class="fas fa-chalkboard-teacher"></i></div>
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
                            <div class="icon-square bg-light-green"><i class="fas fa-bell"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $notices->num_rows; ?></h3>
                                <small class="text-secondary fw-bold text-uppercase">Recent Notices</small>
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
                                            <th class="text-center">Students</th>
                                            <th>Midterm Status</th>
                                            <th class="text-end pe-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if($subject_count > 0):
                                            $my_subjects->data_seek(0);
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
                                                    <span>Progress</span>
                                                    <span class="fw-bold"><?php echo $pct; ?>%</span>
                                                </div>
                                                <div class="progress progress-thin">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $pct; ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="grade_book.php?subject_id=<?php echo $sub['subject_id']; ?>" class="btn btn-sm btn-outline-primary">
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
                    
                    <div class="card mb-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-user-graduate me-2 text-primary"></i> My Class List</h5>
                            <?php if($cid): ?><a href="my_class_students.php" class="btn btn-sm btn-light text-primary fw-bold">View All</a><?php endif; ?>
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
                                            <a href="view_student.php?student_id=<?php echo $stu['student_id']; ?>" class="btn btn-sm btn-light text-secondary"><i class="fas fa-chevron-right"></i></a>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-users-slash fs-1 mb-2 opacity-25"></i>
                                    <p class="small mb-0"><?php echo ($cid) ? "No students assigned yet." : "Class not selected."; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-white py-3 border-bottom-0">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-bell me-2 text-warning"></i> Admin Notices</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="notice-list">
                                <?php if ($notices->num_rows > 0): ?>
                                    <?php while ($n = $notices->fetch_assoc()): ?>
                                        <div class="notice-item <?php echo $n['type']; ?>">
                                            <div class="notice-date">
                                                <div><?php echo date('d', strtotime($n['created_at'])); ?></div>
                                                <div style="font-size:0.6rem; text-transform:uppercase;"><?php echo date('M', strtotime($n['created_at'])); ?></div>
                                            </div>
                                            <div class="notice-content">
                                                <h6><?php echo $n['title']; ?></h6>
                                                <p><?php echo $n['message']; ?></p>
                                                <?php if ($n['event_date']): ?>
                                                    <span class="event-tag"><i class="far fa-calendar-check me-1"></i> Event: <?php echo date('d M Y', strtotime($n['event_date'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="far fa-bell-slash fs-3 mb-2 opacity-25"></i>
                                        <p class="small">No notices for Class Teachers.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php if($show_class_modal): ?>
<div class="modal fade show" id="classSelectModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" style="display: block; background: rgba(0,0,0,0.8);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-chalkboard-teacher me-2"></i> Select Class to Manage</h5>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted mb-4">You are assigned as the Class Teacher for multiple classes. Please select one to proceed.</p>
                <div class="d-grid gap-3">
                    <?php foreach($teacher_classes as $tc): ?>
                        <form method="POST">
                            <button type="submit" name="select_class_id" value="<?php echo $tc['class_id']; ?>" 
                                class="btn btn-outline-primary btn-lg w-100 fw-bold shadow-sm d-flex justify-content-between align-items-center" style="padding:15px 20px;">
                                <span><i class="fas fa-users me-3"></i> <?php echo $tc['class_name']; ?></span>
                                <span class="badge bg-secondary rounded-pill"><?php echo $tc['year']; ?></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>