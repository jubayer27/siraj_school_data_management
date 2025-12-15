<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

// 2. HANDLE DELETE
if(isset($_GET['delete_id'])){
    $did = $_GET['delete_id'];
    
    // Optional: Delete photo file
    $img_q = $conn->query("SELECT photo FROM students WHERE student_id = $did");
    $img = $img_q->fetch_assoc();
    if($img['photo'] && file_exists("../uploads/".$img['photo'])){
        unlink("../uploads/".$img['photo']);
    }

    $conn->query("DELETE FROM students WHERE student_id = $did");
    echo "<script>window.location='manage_students.php?msg=deleted';</script>";
}

// 3. HANDLE QUICK REGISTRATION
if(isset($_POST['add_student'])){
    $name = $_POST['student_name'];
    $reg_no = $_POST['school_register_no'];
    $ic = $_POST['ic_no'];
    $gender = $_POST['gender'];
    $cid = $_POST['class_id'];
    $enroll_date = date('Y-m-d'); // Default to today

    // Check Duplicate ID
    $chk = $conn->query("SELECT student_id FROM students WHERE school_register_no = '$reg_no'");
    if($chk->num_rows > 0){
        $error = "Student ID '$reg_no' already exists.";
    } else {
        // Handle Photo
        $photo_name = "";
        if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
            $target_dir = "../uploads/";
            if(!is_dir($target_dir)) mkdir($target_dir);
            $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            $photo_name = uniqid("stu_") . "." . $ext;
            move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo_name);
        }

        // Insert Basic Info (Full info can be added in Edit Page)
        $stmt = $conn->prepare("INSERT INTO students (student_name, school_register_no, ic_no, gender, class_id, photo, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $name, $reg_no, $ic, $gender, $cid, $photo_name, $enroll_date);
        
        if($stmt->execute()){
            $success = "Student registered successfully!";
            echo "<script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

// 4. STATISTICS
$total_students = $conn->query("SELECT count(*) as c FROM students")->fetch_assoc()['c'];
$boys = $conn->query("SELECT count(*) as c FROM students WHERE gender = 'Male'")->fetch_assoc()['c'];
$girls = $conn->query("SELECT count(*) as c FROM students WHERE gender = 'Female'")->fetch_assoc()['c'];

// 5. FILTER & SEARCH LOGIC
$filter_class = isset($_GET['class_filter']) ? $_GET['class_filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT s.*, c.class_name 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        WHERE 1=1";

if($filter_class) $sql .= " AND s.class_id = $filter_class";
if($search) $sql .= " AND (s.student_name LIKE '%$search%' OR s.school_register_no LIKE '%$search%' OR s.ic_no LIKE '%$search%')";

$sql .= " ORDER BY s.student_id DESC";
$students = $conn->query($sql);

// Fetch Classes for Dropdown
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important; margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important; display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Stats Cards */
    .stat-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: 0.2s; }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

    /* Form Card */
    #addStudentForm { border-top: 5px solid #FFD700; transition: all 0.3s ease; }
    
    /* Table */
    .table-hover tbody tr:hover { background-color: #fcfcfc; }
    .avatar-sm { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Student Directory</h2>
                    <p class="text-secondary mb-0">Manage admissions and profiles.</p>
                </div>
                <button onclick="toggleForm()" class="btn btn-warning fw-bold text-dark shadow-sm">
                    <i class="fas fa-user-plus me-2"></i> Register Student
                </button>
            </div>

            <?php if(isset($success)): ?>
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted'): ?>
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="fas fa-trash-alt me-2"></i> Student record deleted.</div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $total_students; ?></h3>
                                <span class="text-muted small">Total Enrollment</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="fas fa-male"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $boys; ?></h3>
                                <span class="text-muted small">Boys</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                                <i class="fas fa-female"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $girls; ?></h3>
                                <span class="text-muted small">Girls</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" id="addStudentForm" style="display:none;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark m-0"><i class="fas fa-user-plus text-warning me-2"></i> Quick Registration</h5>
                        <button type="button" class="btn-close" onclick="toggleForm()"></button>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Student Name</label>
                                <input type="text" name="student_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Register No / ID</label>
                                <input type="text" name="school_register_no" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Class Assignment</label>
                                <select name="class_id" class="form-select">
                                    <option value="">-- Unassigned --</option>
                                    <?php 
                                    $classes->data_seek(0);
                                    while($c = $classes->fetch_assoc()) echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>"; 
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">IC / Birth Cert No</label>
                                <input type="text" name="ic_no" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Student Photo</label>
                                <input type="file" name="photo" class="form-control" accept=".jpg,.png,.jpeg">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light me-2" onclick="toggleForm()">Cancel</button>
                            <button type="submit" name="add_student" class="btn btn-warning fw-bold text-dark px-4">Create Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4">
                            <select name="class_filter" class="form-select">
                                <option value="">All Classes</option>
                                <?php 
                                $classes->data_seek(0);
                                while($c = $classes->fetch_assoc()): 
                                    $sel = ($filter_class == $c['class_id']) ? 'selected' : '';
                                    echo "<option value='{$c['class_id']}' $sel>{$c['class_name']}</option>";
                                endwhile; 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by Name, ID or IC..." value="<?php echo $search; ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary fw-bold">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Student Profile</th>
                                    <th>Class</th>
                                    <th>ID / IC</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($students->num_rows > 0): ?>
                                    <?php while($row = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <?php $avatar = $row['photo'] ? "../uploads/".$row['photo'] : "https://ui-avatars.com/api/?name=".$row['student_name']."&background=random"; ?>
                                                <img src="<?php echo $avatar; ?>" class="avatar-sm me-3">
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo $row['student_name']; ?></div>
                                                    <div class="small text-muted"><?php echo $row['gender']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($row['class_name']): ?>
                                                <span class="badge bg-warning text-dark"><?php echo $row['class_name']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="font-monospace text-dark"><?php echo $row['school_register_no']; ?></div>
                                            <div class="small text-muted font-monospace"><?php echo $row['ic_no']; ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="view_student.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-sm btn-info text-white me-1" title="View"><i class="fas fa-eye"></i></a>
                                            <a href="edit_student.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-sm btn-warning text-dark me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="manage_students.php?delete_id=<?php echo $row['student_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this student? This will also remove their marks and enrollment history.');"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No students found matching your criteria.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function toggleForm() {
    var x = document.getElementById("addStudentForm");
    if (x.style.display === "none") {
        x.style.display = "block";
        x.scrollIntoView({behavior: "smooth"});
    } else {
        x.style.display = "none";
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>