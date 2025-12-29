<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'subject_teacher' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['subject_id'])) {
    header("Location: my_subjects.php");
    exit();
}

$subject_id = intval($_GET['subject_id']);
$teacher_id = $_SESSION['user_id'];

// 2. SECURITY CHECK & SUBJECT INFO
// Verify teacher is assigned to this subject via junction table
$check_sql = "SELECT s.subject_name, s.subject_code, c.class_name 
              FROM subjects s
              JOIN classes c ON s.class_id = c.class_id
              JOIN subject_teachers st ON s.subject_id = st.subject_id
              WHERE s.subject_id = ? AND st.teacher_id = ?";

$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $subject_id, $teacher_id);
$stmt->execute();
$sub_info = $stmt->get_result()->fetch_assoc();

if (!$sub_info) {
    die("<div class='main-content' style='margin-left:260px; padding:30px;'><div class='alert alert-danger'>Access Denied or Subject Not Found.</div></div>");
}

// 3. FETCH ENROLLED STUDENTS
$sql = "SELECT st.student_id, st.student_name, st.school_register_no, st.gender, st.photo, st.phone
        FROM student_subject_enrollment sse
        JOIN students st ON sse.student_id = st.student_id
        WHERE sse.subject_id = ?
        ORDER BY st.student_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    /* LAYOUT */
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 0 !important;
        display: block !important;
    }

    .container-fluid {
        padding: 30px !important;
    }

    /* CARDS & TABLES */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .search-box {
        position: relative;
        max-width: 300px;
    }

    .search-box input {
        padding-left: 35px;
        border-radius: 20px;
        border: 1px solid #ddd;
    }

    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
    }

    .table-hover tbody tr:hover {
        background-color: #fffcf5;
    }

    /* AVATAR */
    .avatar-sm {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #eee;
        margin-right: 10px;
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
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1"><?php echo $sub_info['subject_name']; ?></h2>
                    <p class="text-secondary mb-0">
                        Class: <strong><?php echo $sub_info['class_name']; ?></strong> |
                        Code: <span class="font-monospace"><?php echo $sub_info['subject_code']; ?></span>
                    </p>
                </div>
                <a href="my_subjects.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </a>
            </div>

            <div class="card">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <h5 class="fw-bold mb-0">
                            Enrolled Students <span
                                class="badge bg-light text-dark border ms-2"><?php echo $result->num_rows; ?></span>
                        </h5>
                        <div class="search-box w-100">
                            <i class="fas fa-search"></i>
                            <input type="text" id="studentSearch" class="form-control"
                                placeholder="Search name or ID...">
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="studentTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Student Name</th>
                                    <th>Register No</th>
                                    <th>Gender</th>
                                    <th>Contact</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <?php $pic = $row['photo'] ? "../uploads/" . $row['photo'] : "https://ui-avatars.com/api/?name=" . $row['student_name'] . "&background=random"; ?>
                                                    <img src="<?php echo $pic; ?>" class="avatar-sm">
                                                    <span class="fw-bold text-dark"><?php echo $row['student_name']; ?></span>
                                                </div>
                                            </td>
                                            <td class="font-monospace text-muted"><?php echo $row['school_register_no']; ?></td>
                                            <td>
                                                <?php if ($row['gender'] == 'Male'): ?>
                                                    <span class="badge bg-primary-subtle text-primary">Male</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger">Female</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $row['phone'] ? $row['phone'] : '<span class="text-muted">-</span>'; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="view_profile.php?student_id=<?php echo $row['student_id']; ?>"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-id-card me-1"></i> Profile
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No students enrolled in this
                                            subject yet.</td>
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
    // Search Filter Script
    document.getElementById('studentSearch').addEventListener('keyup', function () {
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