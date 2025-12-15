<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'subject_teacher' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['student_id'])) {
    echo "<script>window.history.back();</script>";
    exit();
}
$sid = $_GET['student_id'];
$teacher_id = $_SESSION['user_id'];

// 2. SECURITY CHECK
// Ensure teacher teaches this student
if ($_SESSION['role'] == 'subject_teacher') {
    $access_check = $conn->query("SELECT count(*) as c 
                                  FROM student_subject_enrollment sse 
                                  JOIN subjects s ON sse.subject_id = s.subject_id 
                                  WHERE sse.student_id = $sid AND s.teacher_id = $teacher_id")->fetch_assoc()['c'];

    if ($access_check == 0) {
        echo "<div class='main-content' style='margin-left:260px; padding:30px;'><div class='alert alert-danger'>Access Denied: You do not teach this student.</div></div>";
        exit();
    }
}

// 3. FETCH STUDENT INFO
$sql = "SELECT s.*, c.class_name, c.year FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE s.student_id = $sid";
$student = $conn->query($sql)->fetch_assoc();

if (!$student) die("Student not found.");

// 4. FETCH MARKS (Restricted to Subject Teacher's Subjects)
$marks_sql = "SELECT s.subject_name, s.subject_code, sm.exam_type, sm.mark_obtained, sm.grade, sm.created_at
              FROM student_marks sm
              JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id
              JOIN subjects s ON sse.subject_id = s.subject_id
              WHERE sse.student_id = $sid AND s.teacher_id = $teacher_id
              ORDER BY sm.created_at DESC";
$marks_res = $conn->query($marks_sql);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    /* FORCE FULL WIDTH LAYOUT */
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important;
        display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* PROFILE CARD STYLES */
    .profile-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
    .profile-header-bg { height: 100px; background: linear-gradient(135deg, #FFD700, #FDB931); }
    .avatar-wrapper { margin-top: -50px; text-align: center; }
    .avatar-xl { width: 110px; height: 110px; object-fit: cover; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    
    /* TABS STYLES */
    .nav-tabs .nav-link { color: #555; border: none; border-bottom: 3px solid transparent; padding: 12px 20px; font-weight: 600; }
    .nav-tabs .nav-link.active { color: #DAA520; border-bottom-color: #DAA520; background: none; }
    .tab-content { padding: 25px; background: #fff; border-radius: 0 0 12px 12px; border: 1px solid #dee2e6; border-top: none; }

    /* DATA LABEL STYLES */
    .info-label { font-size: 0.75rem; text-transform: uppercase; color: #888; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 3px; display: block; }
    .info-value { font-size: 0.95rem; font-weight: 500; color: #333; margin-bottom: 15px; display: block; }
    .section-title { font-size: 1rem; font-weight: 700; color: #DAA520; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px; }

    /* READ ONLY INDICATOR */
    .read-only-badge { font-size: 0.7rem; background: #eee; color: #777; padding: 2px 8px; border-radius: 4px; margin-left: 10px; }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="student_list.php" class="text-decoration-none text-muted">My Students</a></li>
                            <li class="breadcrumb-item active">Profile View</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold text-dark mb-0">
                        <?php echo $student['student_name']; ?>
                        <span class="read-only-badge"><i class="fas fa-lock me-1"></i> Read Only</span>
                    </h2>
                </div>
                
                <button onclick="window.history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to List
                </button>
            </div>

            <div class="row g-4">
                <div class="col-lg-3">
                    <div class="card profile-card mb-3">
                        <div class="profile-header-bg"></div>
                        <div class="card-body pt-0">
                            <div class="avatar-wrapper">
                                <?php $img = $student['photo'] ? "../uploads/".$student['photo'] : "https://ui-avatars.com/api/?name=".$student['student_name']."&background=random"; ?>
                                <img src="<?php echo $img; ?>" class="avatar-xl">
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold mb-1"><?php echo $student['student_name']; ?></h5>
                                <p class="text-muted font-monospace small mb-2"><?php echo $student['school_register_no']; ?></p>
                                <span class="badge bg-warning text-dark px-3 rounded-pill"><?php echo $student['class_name']; ?></span>
                            </div>
                            <hr>
                            <div>
                                <span class="info-label"><i class="fas fa-venus-mars me-1"></i> Gender</span>
                                <span class="info-value"><?php echo $student['gender']; ?></span>
                                
                                <span class="info-label"><i class="fas fa-phone-alt me-1"></i> Contact</span>
                                <span class="info-value"><?php echo $student['phone'] ? $student['phone'] : 'N/A'; ?></span>
                                
                                <span class="info-label"><i class="fas fa-id-card me-1"></i> IC No</span>
                                <span class="info-value"><?php echo $student['ic_no']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card shadow-sm border-0">
                        <ul class="nav nav-tabs px-3 pt-2" id="profileTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#academic">
                                    <i class="fas fa-chart-line me-2"></i> Academic Performance
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#personal">
                                    <i class="fas fa-user me-2"></i> Personal Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#family">
                                    <i class="fas fa-users me-2"></i> Guardian Info
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            
                            <div class="tab-pane fade show active" id="academic">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="section-title mb-0 border-0 p-0">Results in Your Subjects</h5>
                                    <a href="manage_marks.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-pen-alt me-1"></i> Manage Marks
                                    </a>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-3">Subject</th>
                                                <th>Exam</th>
                                                <th class="text-center">Score</th>
                                                <th class="text-center">Grade</th>
                                                <th class="text-end pe-3">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($marks_res->num_rows > 0): ?>
                                                <?php while ($m = $marks_res->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-3">
                                                        <span class="fw-bold text-dark d-block"><?php echo $m['subject_name']; ?></span>
                                                        <small class="text-muted font-monospace"><?php echo $m['subject_code']; ?></small>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo $m['exam_type']; ?></span></td>
                                                    <td class="text-center fw-bold fs-5"><?php echo $m['mark_obtained']; ?></td>
                                                    <td class="text-center">
                                                        <?php 
                                                            $g = $m['grade'];
                                                            $color = ($g=='A'||$g=='B')?'success':(($g=='C')?'warning':'danger');
                                                        ?>
                                                        <span class="badge bg-<?php echo $color; ?>-subtle text-<?php echo $color; ?> border border-<?php echo $color; ?>-subtle px-3 py-1">
                                                            <?php echo $g; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-3 text-muted small">
                                                        <?php echo date('d M Y', strtotime($m['created_at'])); ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center py-5 text-muted">No marks recorded for your subjects yet.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="personal">
                                <h5 class="section-title">Basic Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <span class="info-label">Full Name</span>
                                        <span class="info-value"><?php echo $student['student_name']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Register No</span>
                                        <span class="info-value font-monospace"><?php echo $student['school_register_no']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Date of Birth</span>
                                        <span class="info-value"><?php echo $student['birthdate']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Place of Birth</span>
                                        <span class="info-value"><?php echo $student['birth_place']; ?></span>
                                    </div>
                                    <div class="col-12">
                                        <span class="info-label">Home Address</span>
                                        <span class="info-value"><?php echo $student['address']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="family">
                                <div class="row">
                                    <div class="col-md-6 border-end">
                                        <h5 class="section-title text-primary">Father's Info</h5>
                                        <span class="info-label">Name</span> <span class="info-value"><?php echo $student['father_name']; ?></span>
                                        <span class="info-label">Contact</span> <span class="info-value"><?php echo $student['father_phone']; ?></span>
                                        <span class="info-label">Job</span> <span class="info-value"><?php echo $student['father_job']; ?></span>
                                    </div>
                                    <div class="col-md-6 ps-4">
                                        <h5 class="section-title text-danger">Mother's Info</h5>
                                        <span class="info-label">Name</span> <span class="info-value"><?php echo $student['mother_name']; ?></span>
                                        <span class="info-label">Contact</span> <span class="info-value"><?php echo $student['mother_phone']; ?></span>
                                        <span class="info-label">Job</span> <span class="info-value"><?php echo $student['mother_job']; ?></span>
                                    </div>
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