<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); 
    exit(); 
}

$teacher_id = $_SESSION['user_id'];

// 2. FETCH CLASS INFO
$class_q = $conn->query("SELECT class_id, class_name, year FROM classes WHERE class_teacher_id = $teacher_id");
$my_class = $class_q->fetch_assoc();

$cid = $my_class ? $my_class['class_id'] : 0;
$class_name = $my_class ? $my_class['class_name'] : "No Class Assigned";

// 3. FETCH STUDENTS
$students = null;
if($cid){
    $students = $conn->query("SELECT * FROM students WHERE class_id = $cid ORDER BY student_name ASC");
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    /* FORCE FULL WIDTH LAYOUT */
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    .main-content {
        position: absolute;
        top: 0; right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 0 !important;
        display: block !important;
    }

    .container-fluid { padding: 30px !important; max-width: 100% !important; }

    /* CARD & TABLE STYLING */
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    
    .search-box {
        position: relative;
    }
    .search-box input {
        padding-left: 40px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        height: 45px;
    }
    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }

    .avatar-md {
        width: 40px; height: 40px;
        object-fit: cover; border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .table-hover tbody tr:hover { background-color: #fffcf5; }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #e8f5e9;
        color: #2e7d32;
    }

    /* RESPONSIVE */
    @media (max-width: 992px) {
        .main-content { width: 100% !important; margin-left: 0 !important; position: relative; }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Class Register</h2>
                    <p class="text-secondary mb-0">Managing students for <strong><?php echo $class_name; ?></strong></p>
                </div>
                
                <?php if($cid): ?>
                <div class="d-flex gap-2">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6">
                        <i class="fas fa-users me-1"></i> <?php echo $students->num_rows; ?> Students
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <?php if(!$cid): ?>
                <div class="alert alert-warning border-0 shadow-sm">
                    <i class="fas fa-exclamation-circle me-2"></i> You are not assigned to any class.
                </div>
            <?php else: ?>

            <div class="card">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-0">Student List</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="studentSearch" class="form-control" placeholder="Search by name, ID, or phone...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="studentTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 text-secondary text-uppercase" style="font-size: 0.75rem;">Profile</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Register No</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Gender</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Parent Contact</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Status</th>
                                    <th class="text-end pe-4 text-secondary text-uppercase" style="font-size: 0.75rem;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($students->num_rows > 0): ?>
                                    <?php while($row = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <?php $pic = $row['photo'] ? "../uploads/".$row['photo'] : "https://ui-avatars.com/api/?name=".$row['student_name']."&background=random"; ?>
                                                <img src="<?php echo $pic; ?>" class="avatar-md me-3">
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo $row['student_name']; ?></div>
                                                    <small class="text-muted" style="font-size: 0.75rem;">IC: <?php echo $row['ic_no']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="font-monospace text-secondary"><?php echo $row['school_register_no']; ?></td>
                                        <td>
                                            <?php if($row['gender'] == 'Male'): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Male</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Female</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($row['father_phone'] || $row['mother_phone']): ?>
                                                <div class="d-flex flex-column">
                                                    <span class="text-dark"><i class="fas fa-phone-alt me-1 text-warning"></i> <?php echo $row['father_phone'] ? $row['father_phone'] : $row['mother_phone']; ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge">Active</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="view_profile.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-sm btn-outline-dark">
                                                <i class="fas fa-id-card me-1"></i> Profile
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">No students found in this class.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('studentSearch').addEventListener('keyup', function() {
    let searchValue = this.value.toLowerCase();
    let rows = document.querySelectorAll('#studentTable tbody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>