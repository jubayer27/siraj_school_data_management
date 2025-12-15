<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = "";
$error = "";

// =========================================================
// 2. HANDLE BULK STUDENT ASSIGNMENT (Single Class)
// =========================================================
if (isset($_POST['assign_students'])) {
    if (!empty($_POST['student_ids']) && !empty($_POST['target_class'])) {
        $class_id = $_POST['target_class'];
        $ids = implode(",", array_map('intval', $_POST['student_ids'])); // Sanitize

        $sql = "UPDATE students SET class_id = $class_id WHERE student_id IN ($ids)";
        if ($conn->query($sql)) {
            $success = "Successfully assigned " . count($_POST['student_ids']) . " students.";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    } else {
        $error = "Please select a class and at least one student.";
    }
}

// =========================================================
// 3. HANDLE BULK SUBJECT ASSIGNMENT (Multi-Class Support)
// =========================================================
if (isset($_POST['assign_subjects'])) {
    if (!empty($_POST['subject_ids']) && !empty($_POST['target_classes'])) {

        $subject_ids = $_POST['subject_ids']; // Array of Subject IDs
        $class_ids = $_POST['target_classes']; // Array of Class IDs
        $count = 0;

        foreach ($subject_ids as $sid) {
            // Fetch original subject details
            $orig = $conn->query("SELECT * FROM subjects WHERE subject_id = $sid")->fetch_assoc();
            $base_code = $orig['subject_code'];
            $base_name = $orig['subject_name'];
            $teacher_id = $orig['teacher_id'] ? $orig['teacher_id'] : "NULL";

            // Loop through selected classes
            foreach ($class_ids as $index => $cid) {
                // Fetch Class Name for unique code generation
                $c_row = $conn->query("SELECT class_name FROM classes WHERE class_id = $cid")->fetch_assoc();
                $suffix = str_replace(' ', '', $c_row['class_name']);
                $new_code = $base_code . "-" . $suffix;

                if ($index === 0) {
                    // FIRST CLASS: Update the existing orphan row
                    $sql = "UPDATE subjects SET class_id = $cid, subject_code = '$new_code' WHERE subject_id = $sid";
                    $conn->query($sql);
                } else {
                    // SUBSEQUENT CLASSES: Create new rows (Clone)
                    // Check duplicate first
                    $chk = $conn->query("SELECT subject_id FROM subjects WHERE subject_code = '$new_code'");
                    if ($chk->num_rows == 0) {
                        $sql = "INSERT INTO subjects (subject_name, subject_code, class_id, teacher_id) 
                                VALUES ('$base_name', '$new_code', $cid, $teacher_id)";
                        $conn->query($sql);
                    }
                }
                $count++;
            }
        }
        $success = "Successfully processed assignments for " . count($subject_ids) . " subjects.";
    } else {
        $error = "Please select at least one class and one subject.";
    }
}

// 4. FETCH DATA
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$unassigned_students = $conn->query("SELECT * FROM students WHERE class_id IS NULL OR class_id = 0 ORDER BY student_name");
$unassigned_subjects = $conn->query("SELECT * FROM subjects WHERE class_id IS NULL OR class_id = 0 ORDER BY subject_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
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

    /* Cards */
    .assign-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        height: 100%;
    }

    .card-header-custom {
        padding: 15px 20px;
        font-weight: 700;
        color: white;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-students {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .header-subjects {
        background: linear-gradient(135deg, #FF8008 0%, #FFC837 100%);
    }

    /* Scroll Boxes */
    .scrollable-table {
        max-height: 350px;
        overflow-y: auto;
        border: 1px solid #eee;
        border-radius: 6px;
        background: white;
    }

    .class-checkbox-list {
        max-height: 150px;
        overflow-y: auto;
        border: 1px solid #eee;
        border-radius: 6px;
        background: #fdfdfd;
        padding: 10px;
    }

    .table thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 1;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 10px;
        opacity: 0.5;
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
                    <h2 class="fw-bold text-dark mb-0">Bulk Assignments</h2>
                    <p class="text-secondary mb-0">Assign students and subjects to classes in bulk.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4"><i
                        class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-6">
                    <div class="card assign-card">
                        <div class="card-header-custom header-students">
                            <span><i class="fas fa-user-graduate me-2"></i> Unassigned Students</span>
                            <span class="badge bg-white text-dark"><?php echo $unassigned_students->num_rows; ?>
                                Pending</span>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="fw-bold mb-2 small text-uppercase text-muted">1. Select Target
                                        Class</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-chalkboard"></i></span>
                                        <select name="target_class" class="form-select" required>
                                            <option value="">-- Choose Class --</option>
                                            <?php
                                            $classes->data_seek(0);
                                            while ($c = $classes->fetch_assoc()):
                                                ?>
                                                <option value="<?php echo $c['class_id']; ?>">
                                                    <?php echo $c['class_name']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <label class="fw-bold mb-2 small text-uppercase text-muted">2. Select Students</label>
                                <div class="scrollable-table mb-3">
                                    <?php if ($unassigned_students->num_rows > 0): ?>
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th width="40"><input type="checkbox" class="form-check-input"
                                                            onclick="toggleAll(this, 'stu_check')"></th>
                                                    <th>Name</th>
                                                    <th>Reg No</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($stu = $unassigned_students->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><input type="checkbox" name="student_ids[]"
                                                                value="<?php echo $stu['student_id']; ?>"
                                                                class="form-check-input stu_check"></td>
                                                        <td class="fw-bold text-dark"><?php echo $stu['student_name']; ?></td>
                                                        <td class="text-muted small font-monospace">
                                                            <?php echo $stu['school_register_no']; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="empty-state"><i class="fas fa-check-circle text-success"></i>
                                            <p>All students assigned!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" name="assign_students" class="btn btn-success w-100 fw-bold">
                                    <i class="fas fa-link me-2"></i> Assign Selected
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card assign-card">
                        <div class="card-header-custom header-subjects">
                            <span><i class="fas fa-book me-2"></i> Unassigned Subjects</span>
                            <span class="badge bg-white text-dark"><?php echo $unassigned_subjects->num_rows; ?>
                                Pending</span>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="fw-bold mb-2 small text-uppercase text-muted">1. Select Target Classes
                                        (Multi)</label>
                                    <div class="class-checkbox-list">
                                        <?php
                                        $classes->data_seek(0);
                                        while ($c = $classes->fetch_assoc()):
                                            ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="target_classes[]"
                                                    value="<?php echo $c['class_id']; ?>"
                                                    id="cls_<?php echo $c['class_id']; ?>">
                                                <label class="form-check-label" for="cls_<?php echo $c['class_id']; ?>">
                                                    <?php echo $c['class_name']; ?>
                                                </label>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <div class="form-text small mt-1 text-primary">
                                        <i class="fas fa-info-circle"></i> Subject will be cloned if multiple classes
                                        are selected.
                                    </div>
                                </div>

                                <label class="fw-bold mb-2 small text-uppercase text-muted">2. Select Subjects</label>
                                <div class="scrollable-table mb-3">
                                    <?php if ($unassigned_subjects->num_rows > 0): ?>
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th width="40"><input type="checkbox" class="form-check-input"
                                                            onclick="toggleAll(this, 'sub_check')"></th>
                                                    <th>Subject Name</th>
                                                    <th>Base Code</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($sub = $unassigned_subjects->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><input type="checkbox" name="subject_ids[]"
                                                                value="<?php echo $sub['subject_id']; ?>"
                                                                class="form-check-input sub_check"></td>
                                                        <td class="fw-bold text-dark"><?php echo $sub['subject_name']; ?></td>
                                                        <td class="text-muted small font-monospace">
                                                            <?php echo $sub['subject_code']; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="empty-state"><i class="fas fa-check-circle text-warning"></i>
                                            <p>All subjects assigned!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" name="assign_subjects"
                                    class="btn btn-warning w-100 fw-bold text-dark">
                                    <i class="fas fa-copy me-2"></i> Assign & Clone Selected
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // Select All Logic
    function toggleAll(source, className) {
        checkboxes = document.getElementsByClassName(className);
        for (var i = 0, n = checkboxes.length; i < n; i++) {
            checkboxes[i].checked = source.checked;
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>