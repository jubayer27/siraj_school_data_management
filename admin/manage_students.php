<?php
session_start();
// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- FIX: DEFINING UPLOAD PATH (Absolute Path) ---
$upload_dir = realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR;

// Safety check: verify folder exists and is writable
if (!$upload_dir || !is_dir($upload_dir)) {
    $upload_dir = dirname(__DIR__) . "/uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
}

// 2. HANDLE DELETE
if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);

    // Get photo to delete file
    $img_q = $conn->query("SELECT photo FROM students WHERE student_id = $did");
    $img = $img_q->fetch_assoc();

    if ($img && !empty($img['photo'])) {
        $file_path = $upload_dir . $img['photo'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $conn->query("DELETE FROM students WHERE student_id = $did");
    $conn->query("DELETE FROM student_subject_enrollment WHERE student_id = $did");
    $conn->query("DELETE FROM student_marks WHERE student_id = $did");

    echo "<script>window.location='manage_students.php?msg=deleted';</script>";
}

// 3. HANDLE REGISTRATION (ADD STUDENT)
if (isset($_POST['add_student'])) {
    $name = $_POST['student_name'];
    $reg_no = $_POST['school_register_no'];
    $ic = $_POST['ic_no'];
    $gender = $_POST['gender'];
    $cid = !empty($_POST['class_id']) ? $_POST['class_id'] : NULL;
    $enroll_date = date('Y-m-d');

    $chk = $conn->query("SELECT student_id FROM students WHERE school_register_no = '$reg_no'");
    if ($chk->num_rows > 0) {
        $error = "Error: Student ID '$reg_no' already exists.";
    } else {
        $photo_name = NULL;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            if (!is_writable($upload_dir)) {
                $error = "Permission Denied: Server cannot write to '$upload_dir'.";
            } else {
                $file_name = $_FILES['photo']['name'];
                $file_tmp = $_FILES['photo']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($file_ext, $allowed)) {
                    $new_filename = "stu_" . time() . "_" . uniqid() . "." . $file_ext;
                    $target_file = $upload_dir . $new_filename;

                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $photo_name = $new_filename;
                    } else {
                        $error = "Warning: Failed to move uploaded file.";
                    }
                } else {
                    $error = "Invalid file type.";
                }
            }
        }

        if (!isset($error)) {
            if ($cid) {
                $stmt = $conn->prepare("INSERT INTO students (student_name, school_register_no, ic_no, gender, class_id, photo, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiss", $name, $reg_no, $ic, $gender, $cid, $photo_name, $enroll_date);
            } else {
                $stmt = $conn->prepare("INSERT INTO students (student_name, school_register_no, ic_no, gender, class_id, photo, enrollment_date) VALUES (?, ?, ?, ?, NULL, ?, ?)");
                $stmt->bind_param("ssssss", $name, $reg_no, $ic, $gender, $photo_name, $enroll_date);
            }

            if ($stmt->execute()) {
                $success = "Student registered successfully!";
                echo "<script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>";
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}

// 4. STATISTICS
$total_students = $conn->query("SELECT count(*) as c FROM students")->fetch_assoc()['c'];
$boys = $conn->query("SELECT count(*) as c FROM students WHERE gender = 'Male'")->fetch_assoc()['c'];
$girls = $conn->query("SELECT count(*) as c FROM students WHERE gender = 'Female'")->fetch_assoc()['c'];

// 5. FILTER & SEARCH
$filter_class = isset($_GET['class_filter']) ? $_GET['class_filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT s.*, c.class_name 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        WHERE 1=1";

if ($filter_class)
    $sql .= " AND s.class_id = $filter_class";
if ($search)
    $sql .= " AND (s.student_name LIKE '%$search%' OR s.school_register_no LIKE '%$search%' OR s.ic_no LIKE '%$search%')";

$sql .= " ORDER BY s.student_id DESC LIMIT 100";
$students = $conn->query($sql);

$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
        font-family: 'Segoe UI', sans-serif;
    }

    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 40px !important;
        display: block !important;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        border: 1px solid #f0f0f0;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 15px;
    }

    #addStudentForm {
        border-top: 4px solid #FFD700;
        display: none;
    }

    .avatar-sm {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-right: 12px;
        background-color: #eee;
    }

    /* UPDATED: Search Bar Styling */
    .filter-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }

    .search-group {
        position: relative;
    }

    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
        font-size: 1rem;
        pointer-events: none;
        /* Allows click to pass through to input */
        z-index: 10;
    }

    .search-input {
        padding-left: 45px !important;
        /* Space for icon */
        border-radius: 8px;
        height: 48px;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
        width: 100%;
        transition: all 0.2s;
    }

    .search-input:focus {
        background-color: #fff;
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }

    .filter-select {
        height: 48px;
        border-radius: 8px;
    }

    .btn-h-45 {
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 992px) {
        .main-content {
            width: 100% !important;
            margin-left: 0 !important;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid p-0">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Student Directory</h2>
                    <p class="text-secondary mb-0">Manage enrollment and profiles.</p>
                </div>
                <button onclick="toggleForm()" class="btn btn-warning fw-bold text-dark shadow-sm px-4">
                    <i class="fas fa-user-plus me-2"></i> Add Student
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success d-flex align-items-center mb-4 shadow-sm border-0"><i
                        class="fas fa-check-circle me-2 fa-lg"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm border-0"><i
                        class="fas fa-exclamation-triangle me-2 fa-lg"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="fas fa-trash-alt me-2"></i>
                    Student record deleted.</div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-users"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $total_students; ?></h3><span
                                class="text-muted small fw-bold">TOTAL STUDENTS</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-mars"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $boys; ?></h3><span
                                class="text-muted small fw-bold">BOYS</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-venus"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $girls; ?></h3><span
                                class="text-muted small fw-bold">GIRLS</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4 border-0" id="addStudentForm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0"><i class="fas fa-user-plus text-warning me-2"></i> New Registration</h5>
                        <button type="button" class="btn-close" onclick="toggleForm()"></button>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">FULL NAME</label>
                                <input type="text" name="student_name" class="form-control" required
                                    placeholder="Full Name as per IC">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">REGISTER ID</label>
                                <input type="text" name="school_register_no" class="form-control" required
                                    placeholder="e.g. S-2024-001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">CLASS</label>
                                <select name="class_id" class="form-select bg-light">
                                    <option value="">-- Unassigned --</option>
                                    <?php $classes->data_seek(0);
                                    while ($c = $classes->fetch_assoc()): ?>
                                        <option value="<?php echo $c['class_id']; ?>"><?php echo $c['class_name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">IC NUMBER</label>
                                <input type="text" name="ic_no" class="form-control" required
                                    placeholder="Without dashes">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">GENDER</label>
                                <select name="gender" class="form-select bg-light">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">PROFILE PHOTO (Optional)</label>
                                <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Max size 2MB. Formats: JPG, PNG.</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-light me-2" onclick="toggleForm()">Cancel</button>
                            <button type="submit" name="add_student" class="btn btn-primary fw-bold px-4">Register
                                Student</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card filter-card mb-4 border-0">
                <div class="card-body p-3">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4">
                            <select name="class_filter" class="form-select filter-select bg-light border-0">
                                <option value="">Filter by Class: All</option>
                                <?php $classes->data_seek(0);
                                while ($c = $classes->fetch_assoc()):
                                    $sel = ($filter_class == $c['class_id']) ? 'selected' : '';
                                    echo "<option value='{$c['class_id']}' $sel>{$c['class_name']}</option>";
                                endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="search-group">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="search" class="form-control search-input"
                                    placeholder="Search by Name, ID, or IC..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary fw-bold w-100 btn-h-45">Apply</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 text-secondary small fw-bold text-uppercase">Student</th>
                                    <th class="text-secondary small fw-bold text-uppercase">Class</th>
                                    <th class="text-secondary small fw-bold text-uppercase">ID / IC</th>
                                    <th class="text-center text-secondary small fw-bold text-uppercase">Status</th>
                                    <th class="text-end pe-4 text-secondary small fw-bold text-uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students->num_rows > 0): ?>
                                    <?php while ($row = $students->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    // FIX: Display logic for image with absolute path check
                                                    $is_file = (!empty($row['photo']) && file_exists($upload_dir . $row['photo']));
                                                    $img_url = $is_file
                                                        ? "../uploads/" . $row['photo']
                                                        : "https://ui-avatars.com/api/?name=" . urlencode($row['student_name']) . "&background=random&color=fff";
                                                    ?>
                                                    <img src="<?php echo $img_url; ?>" class="avatar-sm">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo $row['student_name']; ?></div>
                                                        <div class="small text-muted"><?php echo $row['gender']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($row['class_name']): ?>
                                                    <span
                                                        class="badge bg-warning text-dark"><?php echo $row['class_name']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark small"><?php echo $row['school_register_no']; ?>
                                                </div>
                                                <div class="text-muted small font-monospace"><?php echo $row['ic_no']; ?></div>
                                            </td>
                                            <td class="text-center"><span
                                                    class="badge bg-success-subtle text-success">Active</span></td>
                                            <td class="text-end pe-4">
                                                <a href="view_student.php?student_id=<?php echo $row['student_id']; ?>"
                                                    class="btn btn-sm btn-light border me-1"><i
                                                        class="fas fa-eye text-primary"></i></a>
                                                <a href="edit_student.php?student_id=<?php echo $row['student_id']; ?>"
                                                    class="btn btn-sm btn-light border me-1"><i
                                                        class="fas fa-edit text-warning"></i></a>
                                                <a href="manage_students.php?delete_id=<?php echo $row['student_id']; ?>"
                                                    class="btn btn-sm btn-light border text-danger"
                                                    onclick="return confirm('Delete this student?');"><i
                                                        class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No students found.</td>
                                    </tr>
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
            x.scrollIntoView({ behavior: "smooth", block: "center" });
        } else {
            x.style.display = "none";
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>