<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// 2. FETCH CLASS
// A class teacher can only manage enrollment for their own assigned class
$class_q = $conn->query("SELECT class_id, class_name FROM classes WHERE class_teacher_id = $teacher_id");
$my_class = $class_q->fetch_assoc();
$cid = $my_class ? $my_class['class_id'] : 0;

// 3. HANDLE BULK ENROLLMENT
$msg = "";
if (isset($_POST['enroll_bulk']) && $cid) {
    if (!empty($_POST['students']) && !empty($_POST['subjects'])) {
        $count = 0;
        $stu_list = $_POST['students']; // Array of Student IDs
        $sub_list = $_POST['subjects']; // Array of Subject IDs

        foreach ($stu_list as $sid) {
            foreach ($sub_list as $sub_id) {
                // Check if already enrolled to prevent duplicates
                $check = $conn->query("SELECT enrollment_id FROM student_subject_enrollment WHERE student_id=$sid AND subject_id=$sub_id");

                if ($check->num_rows == 0) {
                    // Create Enrollment
                    $conn->query("INSERT INTO student_subject_enrollment (student_id, subject_id, class_id) VALUES ($sid, $sub_id, $cid)");
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $msg = "<div class='alert alert-success d-flex align-items-center'><i class='fas fa-check-circle me-2'></i> Successfully created $count new subject enrollments.</div>";
        } else {
            $msg = "<div class='alert alert-warning d-flex align-items-center'><i class='fas fa-exclamation-circle me-2'></i> No new enrollments were added (students may already be enrolled).</div>";
        }

    } else {
        $msg = "<div class='alert alert-danger d-flex align-items-center'><i class='fas fa-times-circle me-2'></i> Please select at least one student and one subject.</div>";
    }
}

// 4. FETCH DATA FOR FORMS
// Only fetch students and subjects belonging to THIS class
$students = $conn->query("SELECT student_id, student_name, school_register_no FROM students WHERE class_id = $cid ORDER BY student_name");
$subjects = $conn->query("SELECT subject_id, subject_name, subject_code FROM subjects WHERE class_id = $cid ORDER BY subject_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    /* Layout */
    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 30px !important;
        display: block !important;
    }

    .container-fluid {
        padding: 0 !important;
    }

    /* Cards */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .card-header {
        border-bottom: 1px solid #f0f0f0;
        padding: 15px 20px;
    }

    /* Form Elements */
    .form-check-input:checked {
        background-color: #DAA520;
        border-color: #DAA520;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .list-group-item:hover {
        background-color: #fcfcfc;
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
                    <h2 class="fw-bold text-dark mb-1">Subject Enrollment</h2>
                    <p class="text-secondary mb-0">Assign subjects to students in
                        <strong><?php echo isset($my_class['class_name']) ? $my_class['class_name'] : 'Unknown Class'; ?></strong>
                    </p>
                </div>
            </div>

            <?php echo $msg; ?>

            <?php if (!$cid): ?>
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                    <div>
                        <strong>No Class Assigned</strong><br>
                        You are not currently assigned as a Class Teacher for any active class. Please contact the
                        Administrator.
                    </div>
                </div>
            <?php else: ?>

                <form method="POST">
                    <div class="row g-4">

                        <div class="col-md-5">
                            <div class="card h-100">
                                <div class="card-header bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="fw-bold mb-0 text-warning"><i class="fas fa-book me-2"></i> 1. Select
                                            Subjects</h5>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAllSubjects">
                                            <label class="form-check-label small text-muted user-select-none"
                                                for="checkAllSubjects">All</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                                    <div class="list-group list-group-flush">
                                        <?php if ($subjects->num_rows > 0):
                                            while ($sub = $subjects->fetch_assoc()): ?>
                                                <label class="list-group-item px-4 py-3 d-flex gap-3 cursor-pointer">
                                                    <input class="form-check-input flex-shrink-0 subject-checkbox" type="checkbox"
                                                        name="subjects[]" value="<?php echo $sub['subject_id']; ?>">
                                                    <div>
                                                        <span
                                                            class="fw-bold d-block text-dark"><?php echo $sub['subject_name']; ?></span>
                                                        <small
                                                            class="text-muted font-monospace"><?php echo $sub['subject_code']; ?></small>
                                                    </div>
                                                </label>
                                            <?php endwhile; else: ?>
                                            <div class="p-5 text-center text-muted">
                                                <i class="fas fa-book-open fa-2x mb-2 opacity-50"></i><br>
                                                No subjects assigned to this class yet.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div class="text-center text-muted">
                                <i class="fas fa-arrow-right fa-3x d-none d-md-block text-warning opacity-50"></i>
                                <i class="fas fa-arrow-down fa-3x d-block d-md-none my-3 text-warning opacity-50"></i>
                                <div class="mt-2 small fw-bold text-uppercase letter-spacing-1">Assign To</div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card h-100">
                                <div class="card-header bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-user-graduate me-2"></i> 2.
                                            Select Students</h5>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAllStudents">
                                            <label class="form-check-label small text-muted user-select-none"
                                                for="checkAllStudents">All</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                                    <div class="list-group list-group-flush">
                                        <?php if ($students->num_rows > 0):
                                            while ($stu = $students->fetch_assoc()): ?>
                                                <label
                                                    class="list-group-item px-4 py-2 d-flex gap-3 cursor-pointer align-items-center">
                                                    <input class="form-check-input flex-shrink-0 student-checkbox" type="checkbox"
                                                        name="students[]" value="<?php echo $stu['student_id']; ?>">
                                                    <div>
                                                        <span
                                                            class="fw-bold text-dark d-block"><?php echo $stu['student_name']; ?></span>
                                                        <small class="text-muted"><?php echo $stu['school_register_no']; ?></small>
                                                    </div>
                                                </label>
                                            <?php endwhile; else: ?>
                                            <div class="p-5 text-center text-muted">
                                                <i class="fas fa-users fa-2x mb-2 opacity-50"></i><br>
                                                No students found in this class.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4 p-4 text-center bg-white border-0">
                        <button type="submit" name="enroll_bulk"
                            class="btn btn-warning btn-lg fw-bold px-5 text-dark shadow-sm">
                            <i class="fas fa-save me-2"></i> Confirm Enrollment
                        </button>
                        <p class="text-muted small mt-2 mb-0">
                            <i class="fas fa-info-circle me-1"></i> This action links students to subjects so Subject
                            Teachers can enter marks.
                        </p>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    // Select All Logic
    document.getElementById('checkAllSubjects').addEventListener('change', function () {
        document.querySelectorAll('.subject-checkbox').forEach(cb => cb.checked = this.checked);
    });
    document.getElementById('checkAllStudents').addEventListener('change', function () {
        document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = this.checked);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>