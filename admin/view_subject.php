<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); exit();
}

if(!isset($_GET['subject_id'])){
    echo "<script>window.location='manage_subjects.php';</script>";
    exit();
}
$sid = $_GET['subject_id'];

// 2. FETCH SUBJECT DETAILS
$sql = "SELECT s.*, c.class_name, c.year, u.full_name as teacher_name, u.phone, u.avatar, u.user_id as teacher_id 
        FROM subjects s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        LEFT JOIN users u ON s.teacher_id = u.user_id 
        WHERE s.subject_id = $sid";
$sub = $conn->query($sql)->fetch_assoc();

if(!$sub) die("Subject not found.");

// 3. FETCH ENROLLED STUDENTS
$stu_sql = "SELECT st.*, sse.enrollment_date 
            FROM student_subject_enrollment sse 
            JOIN students st ON sse.student_id = st.student_id 
            WHERE sse.subject_id = $sid 
            ORDER BY st.student_name ASC";
$students = $conn->query($stu_sql);
$enrolled_count = $students->num_rows;
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    /* Layout Fix */
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important;
        display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Profile Cards */
    .subject-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; background: white; }
    .subject-icon-box { background: #fffcf5; padding: 40px 0; text-align: center; border-bottom: 1px dashed #eee; }
    
    /* Teacher Avatar */
    .avatar-md { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-right: 15px; }
    
    /* Table Styling */
    .table-hover tbody tr:hover { background-color: #fcfcfc; }
    
    /* Code Badge */
    .code-badge { background: #333; color: white; padding: 5px 12px; border-radius: 6px; font-family: monospace; letter-spacing: 1px; font-size: 0.9rem; }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Subject Profile</h2>
                    <p class="text-secondary mb-0">Overview for <strong><?php echo $sub['subject_name']; ?></strong></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="manage_subjects.php" class="btn btn-light shadow-sm border">
                        <i class="fas fa-arrow-left me-2"></i> Back
                    </a>
                    <a href="edit_subject.php?subject_id=<?php echo $sid; ?>" class="btn btn-warning fw-bold shadow-sm">
                        <i class="fas fa-edit me-2"></i> Edit Subject
                    </a>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-lg-4">
                    
                    <div class="card subject-card mb-4">
                        <div class="subject-icon-box">
                            <i class="fas fa-book-open text-warning fa-4x mb-3"></i>
                            <h4 class="fw-bold text-dark mb-2"><?php echo $sub['subject_name']; ?></h4>
                            <span class="code-badge"><?php echo $sub['subject_code']; ?></span>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <span class="text-muted text-uppercase small fw-bold"><i class="fas fa-chalkboard me-2"></i> Class</span>
                                <span class="fw-bold text-dark"><?php echo $sub['class_name']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <span class="text-muted text-uppercase small fw-bold"><i class="fas fa-calendar-alt me-2"></i> Year</span>
                                <span class="fw-bold text-dark"><?php echo $sub['year']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted text-uppercase small fw-bold"><i class="fas fa-users me-2"></i> Enrolled</span>
                                <span class="badge bg-success-subtle text-success fs-6 px-3"><?php echo $enrolled_count; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card subject-card">
                        <div class="card-header bg-white py-3 border-bottom fw-bold text-dark">
                            <i class="fas fa-user-tie text-primary me-2"></i> Assigned Teacher
                        </div>
                        <div class="card-body p-4">
                            <?php if($sub['teacher_name']): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <?php $avatar = $sub['avatar'] ? "../uploads/".$sub['avatar'] : "https://ui-avatars.com/api/?name=".$sub['teacher_name']; ?>
                                    <img src="<?php echo $avatar; ?>" class="avatar-md">
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo $sub['teacher_name']; ?></div>
                                        <small class="text-muted"><i class="fas fa-phone me-1"></i> <?php echo $sub['phone'] ? $sub['phone'] : 'N/A'; ?></small>
                                    </div>
                                </div>
                                <a href="view_user.php?user_id=<?php echo $sub['teacher_id']; ?>" class="btn btn-outline-primary w-100 btn-sm">View Profile</a>
                            <?php else: ?>
                                <div class="text-center py-3 text-muted fst-italic">
                                    <i class="fas fa-exclamation-circle me-1"></i> No teacher assigned yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <div class="col-lg-8">
                    <div class="card subject-card h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold m-0 text-dark"><i class="fas fa-list text-success me-2"></i> Student Roster</h5>
                            <a href="manage_all_marks.php?subject_id=<?php echo $sid; ?>&class_id=<?php echo $sub['class_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-pen-alt me-1"></i> Manage Marks
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 600px; overflow-y:auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th class="ps-4">Reg No</th>
                                            <th>Student Name</th>
                                            <th>Gender</th>
                                            <th>Enrolled Date</th>
                                            <th class="text-end pe-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($students->num_rows > 0): ?>
                                            <?php while($stu = $students->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge bg-light text-dark border font-monospace"><?php echo $stu['school_register_no']; ?></span>
                                                </td>
                                                <td class="fw-bold text-dark"><?php echo $stu['student_name']; ?></td>
                                                <td><?php echo $stu['gender']; ?></td>
                                                <td class="text-muted small"><?php echo date('d M Y', strtotime($stu['enrollment_date'])); ?></td>
                                                <td class="text-end pe-4">
                                                    <a href="view_student.php?student_id=<?php echo $stu['student_id']; ?>" class="btn btn-sm btn-info text-white">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center py-5 text-muted">No students currently enrolled in this subject.</td></tr>
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