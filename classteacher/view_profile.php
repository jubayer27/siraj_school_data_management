<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); exit(); 
}

$sid = $_GET['student_id'];

// 2. HANDLE EDIT SUBMISSION (Expanded for common fields)
if(isset($_POST['update_student'])){
    $name = $_POST['student_name'];
    $ic = $_POST['ic_no'];
    $phone = $_POST['phone'];
    $addr = $_POST['address'];
    $father = $_POST['father_name'];
    $father_ph = $_POST['father_phone'];
    $mother = $_POST['mother_name'];
    $mother_ph = $_POST['mother_phone'];
    
    $stmt = $conn->prepare("UPDATE students SET student_name=?, ic_no=?, phone=?, address=?, father_name=?, father_phone=?, mother_name=?, mother_phone=? WHERE student_id=?");
    $stmt->bind_param("ssssssssi", $name, $ic, $phone, $addr, $father, $father_ph, $mother, $mother_ph, $sid);
    
    if($stmt->execute()){
        echo "<script>alert('Student details updated successfully!'); echo '<script>window.location.href=window.location.href;</script>';</script>";
    } else {
        echo "<script>alert('Error updating details.');</script>";
    }
}

// 3. FETCH FULL STUDENT DATA
$stu_q = $conn->query("SELECT s.*, c.class_name, c.year FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE s.student_id = $sid");
$student = $stu_q->fetch_assoc();

if(!$student) die("Student not found.");

// 4. FETCH ACADEMIC MARKS
$marks_res = $conn->query("SELECT sub.subject_name, sub.subject_code, sm.exam_type, sm.mark_obtained, sm.grade 
                           FROM student_marks sm
                           JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id
                           JOIN subjects sub ON sse.subject_id = sub.subject_id
                           WHERE sse.student_id = $sid
                           ORDER BY sm.exam_type DESC, sub.subject_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important; margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important; display: block !important;
    }
    .container-fluid { padding: 30px !important; }
    
    /* Profile Card */
    .profile-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
    .profile-header-bg { height: 100px; background: linear-gradient(135deg, #FFD700, #FDB931); }
    .avatar-wrapper { margin-top: -50px; text-align: center; }
    .avatar-xl { width: 110px; height: 110px; object-fit: cover; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    
    /* Tabs */
    .nav-tabs .nav-link { color: #555; border: none; border-bottom: 3px solid transparent; padding: 12px 20px; font-weight: 600; }
    .nav-tabs .nav-link.active { color: #DAA520; border-bottom-color: #DAA520; background: none; }
    .tab-content { padding: 25px; background: #fff; border-radius: 0 0 12px 12px; border: 1px solid #dee2e6; border-top: none; }

    /* Info Lists */
    .info-label { font-size: 0.75rem; text-transform: uppercase; color: #888; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 3px; display: block; }
    .info-value { font-size: 0.95rem; font-weight: 500; color: #333; margin-bottom: 15px; display: block; }
    
    .section-title { font-size: 1rem; font-weight: 700; color: #DAA520; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 8px; }

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
                            <li class="breadcrumb-item"><a href="my_class_students.php">Class List</a></li>
                            <li class="breadcrumb-item active">Student Profile</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold text-dark mb-0"><?php echo $student['student_name']; ?></h2>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="fas fa-user-edit me-2"></i> Edit
                    </button>
                    <a href="print_marksheet.php?student_id=<?php echo $sid; ?>" target="_blank" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i> Print Transcript
                    </a>
                </div>
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
                                <span class="info-label"><i class="fas fa-id-card me-1"></i> IC Number</span>
                                <span class="info-value"><?php echo $student['ic_no']; ?></span>
                                
                                <span class="info-label"><i class="fas fa-venus-mars me-1"></i> Gender</span>
                                <span class="info-value"><?php echo $student['gender']; ?></span>
                                
                                <span class="info-label"><i class="fas fa-phone-alt me-1"></i> Phone</span>
                                <span class="info-value"><?php echo $student['phone'] ? $student['phone'] : '-'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card shadow-sm">
                        <ul class="nav nav-tabs px-3 pt-2" id="profileTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#personal">
                                    <i class="fas fa-user me-2"></i> Personal Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#family">
                                    <i class="fas fa-users me-2"></i> Family & Guardian
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#cocurriculum">
                                    <i class="fas fa-medal me-2"></i> Co-Curriculum
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#academic">
                                    <i class="fas fa-graduation-cap me-2"></i> Academic
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            
                            <div class="tab-pane fade show active" id="personal">
                                <h5 class="section-title">Identity & Background</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <span class="info-label">Full Name</span>
                                        <span class="info-value"><?php echo $student['student_name']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Date of Birth</span>
                                        <span class="info-value"><?php echo $student['birthdate']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Place of Birth</span>
                                        <span class="info-value"><?php echo $student['birth_place']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Birth Cert No.</span>
                                        <span class="info-value"><?php echo $student['birth_cert_no']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Race</span>
                                        <span class="info-value"><?php echo $student['race']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Religion</span>
                                        <span class="info-value"><?php echo $student['religion']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Nationality</span>
                                        <span class="info-value"><?php echo $student['nationality']; ?></span>
                                    </div>
                                </div>
                                
                                <h5 class="section-title mt-4">Contact & Status</h5>
                                <div class="row">
                                    <div class="col-md-8">
                                        <span class="info-label">Home Address</span>
                                        <span class="info-value"><?php echo $student['address']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Date Enrolled</span>
                                        <span class="info-value"><?php echo $student['enrollment_date']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Previous School</span>
                                        <span class="info-value"><?php echo $student['previous_school']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Is Orphan?</span>
                                        <span class="info-value"><?php echo $student['is_orphan']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Baitulmal Recipient?</span>
                                        <span class="info-value"><?php echo $student['is_baitulmal_recipient']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="family">
                                <div class="row">
                                    <div class="col-md-6 border-end">
                                        <h5 class="section-title text-primary"><i class="fas fa-male me-2"></i> Father's Details</h5>
                                        <span class="info-label">Name</span> <span class="info-value"><?php echo $student['father_name']; ?></span>
                                        <span class="info-label">IC No</span> <span class="info-value"><?php echo $student['father_ic']; ?></span>
                                        <span class="info-label">Phone</span> <span class="info-value"><?php echo $student['father_phone']; ?></span>
                                        <span class="info-label">Occupation</span> <span class="info-value"><?php echo $student['father_job']; ?></span>
                                        <span class="info-label">Salary</span> <span class="info-value">RM <?php echo $student['father_salary']; ?></span>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="section-title text-danger"><i class="fas fa-female me-2"></i> Mother's Details</h5>
                                        <span class="info-label">Name</span> <span class="info-value"><?php echo $student['mother_name']; ?></span>
                                        <span class="info-label">IC No</span> <span class="info-value"><?php echo $student['mother_ic']; ?></span>
                                        <span class="info-label">Phone</span> <span class="info-value"><?php echo $student['mother_phone']; ?></span>
                                        <span class="info-label">Occupation</span> <span class="info-value"><?php echo $student['mother_job']; ?></span>
                                        <span class="info-label">Salary</span> <span class="info-value">RM <?php echo $student['mother_salary']; ?></span>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="section-title">Guardian (If Applicable)</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <span class="info-label">Name</span> <span class="info-value"><?php echo $student['guardian_name']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Contact</span> <span class="info-value"><?php echo $student['guardian_phone']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <span class="info-label">Relation</span> <span class="info-value">
                                            <?php echo ($student['guardian_name']) ? 'Guardian' : '-'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="cocurriculum">
                                <h5 class="section-title">Uniform Bodies</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <span class="info-label">Unit Name</span>
                                        <span class="info-value fw-bold text-primary"><?php echo $student['uniform_unit']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Position Held</span>
                                        <span class="info-value"><?php echo $student['uniform_position']; ?></span>
                                    </div>
                                </div>
                                
                                <h5 class="section-title">Clubs & Associations</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <span class="info-label">Club Name</span>
                                        <span class="info-value fw-bold text-success"><?php echo $student['club_association']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Position Held</span>
                                        <span class="info-value"><?php echo $student['club_position']; ?></span>
                                    </div>
                                </div>
                                
                                <h5 class="section-title">Sports & Games</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <span class="info-label">Sport Name</span>
                                        <span class="info-value fw-bold text-warning"><?php echo $student['sports_game']; ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="info-label">Position Held</span>
                                        <span class="info-value"><?php echo $student['sports_position']; ?></span>
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <span class="info-label">Sports House</span>
                                        <span class="info-value text-uppercase fw-bold"><?php echo $student['sports_house']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="academic">
                                <h5 class="section-title">Examination Results</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Exam Type</th>
                                                <th>Subject</th>
                                                <th class="text-center">Mark</th>
                                                <th class="text-center">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if($marks_res->num_rows > 0): ?>
                                                <?php while($m = $marks_res->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo $m['exam_type']; ?></td>
                                                    <td><?php echo $m['subject_name']; ?> <small class="text-muted">(<?php echo $m['subject_code']; ?>)</small></td>
                                                    <td class="text-center fw-bold"><?php echo $m['mark_obtained']; ?></td>
                                                    <td class="text-center">
                                                        <?php 
                                                        $g = $m['grade'];
                                                        $badge = ($g=='A'||$g=='B')?'success':(($g=='C')?'warning':'danger');
                                                        echo "<span class='badge bg-$badge'>$g</span>";
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-center text-muted py-4">No academic records found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div> </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Update Student Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><h6 class="text-primary fw-bold">Student Info</h6></div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="student_name" class="form-control" value="<?php echo $student['student_name']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IC Number</label>
                            <input type="text" name="ic_no" class="form-control" value="<?php echo $student['ic_no']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo $student['phone']; ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo $student['address']; ?>">
                        </div>

                        <div class="col-12 mt-3"><h6 class="text-primary fw-bold">Parents Info</h6></div>
                        <div class="col-md-6">
                            <label class="form-label">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" value="<?php echo $student['father_name']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father's Phone</label>
                            <input type="text" name="father_phone" class="form-control" value="<?php echo $student['father_phone']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" value="<?php echo $student['mother_name']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mother's Phone</label>
                            <input type="text" name="mother_phone" class="form-control" value="<?php echo $student['mother_phone']; ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_student" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>