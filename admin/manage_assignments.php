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
// 2. HANDLE STUDENT ACTIONS
// =========================================================

// A. ASSIGN STUDENTS
if (isset($_POST['assign_students'])) {
    if (!empty($_POST['student_ids']) && !empty($_POST['target_class'])) {
        $class_id = intval($_POST['target_class']);
        $ids = implode(",", array_map('intval', $_POST['student_ids']));

        // Update Class
        $sql = "UPDATE students SET class_id = $class_id WHERE student_id IN ($ids)";
        if ($conn->query($sql)) {
            // Auto-Enroll
            if (function_exists('autoEnrollSubject')) {
                foreach ($_POST['student_ids'] as $sid) {
                    $conn->query("DELETE FROM student_subject_enrollment WHERE student_id = $sid"); // Clean old
                    autoEnrollSubject($conn, $sid, $class_id); // Add new
                }
            }
            $success = "Assigned " . count($_POST['student_ids']) . " students to class.";
        }
    } else {
        $error = "Select a class and students.";
    }
}

// B. UNASSIGN STUDENTS
if (isset($_POST['unassign_students'])) {
    if (!empty($_POST['student_ids'])) {
        $ids = implode(",", array_map('intval', $_POST['student_ids']));
        $conn->query("UPDATE students SET class_id = NULL WHERE student_id IN ($ids)");
        $conn->query("DELETE FROM student_subject_enrollment WHERE student_id IN ($ids)");
        $success = "Unassigned " . count($_POST['student_ids']) . " students.";
    } else {
        $error = "Select students to unassign.";
    }
}

// =========================================================
// 3. HANDLE SUBJECT ACTIONS (SMART CLONING)
// =========================================================

// A. ASSIGN SUBJECTS
if (isset($_POST['assign_subjects'])) {
    if (!empty($_POST['subject_ids']) && !empty($_POST['target_classes'])) {
        $subject_ids = $_POST['subject_ids'];
        $class_ids = $_POST['target_classes'];
        $count = 0;
        
        foreach ($subject_ids as $sid) {
            $sid = intval($sid);
            // Fetch origin details
            $orig = $conn->query("SELECT * FROM subjects WHERE subject_id = $sid")->fetch_assoc();
            
            if ($orig) {
                // Determine if currently assigned
                $is_assigned = ($orig['class_id'] > 0);
                
                // Determine BASE CODE (Strip old suffix if exists)
                $base_code = $orig['subject_code'];
                if($is_assigned){
                    // Fetch old class name to strip it
                    $old_c = $conn->query("SELECT class_name FROM classes WHERE class_id = {$orig['class_id']}")->fetch_assoc();
                    if($old_c){
                        $suffix = "-" . str_replace(' ', '', $old_c['class_name']);
                        // If code ends with old suffix, strip it
                        if(substr($base_code, -strlen($suffix)) === $suffix){
                            $base_code = substr($base_code, 0, -strlen($suffix));
                        }
                    }
                }

                foreach ($class_ids as $index => $cid) {
                    $cid = intval($cid);
                    // Fetch New Class Name
                    $c_row = $conn->query("SELECT class_name FROM classes WHERE class_id = $cid")->fetch_assoc();
                    $new_suffix = str_replace(' ', '', $c_row['class_name']);
                    $new_code = $base_code . "-" . $new_suffix;

                    // LOGIC: 
                    // 1. If Unassigned AND it's the first target -> UPDATE (Fill the empty slot)
                    // 2. All other cases -> INSERT (Clone)
                    
                    if (!$is_assigned && $index === 0) {
                        $conn->query("UPDATE subjects SET class_id = $cid, subject_code = '$new_code' WHERE subject_id = $sid");
                    } else {
                        // Check duplicate
                        $chk = $conn->query("SELECT subject_id FROM subjects WHERE subject_code = '$new_code'");
                        if ($chk->num_rows == 0) {
                            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id, teacher_id) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("ssii", $orig['subject_name'], $new_code, $cid, $orig['teacher_id']);
                            $stmt->execute();
                        }
                    }
                    $count++;
                }
            }
        }
        $success = "Processed assignments successfully.";
    } else {
        $error = "Select classes and subjects.";
    }
}

// B. UNASSIGN SUBJECTS
if (isset($_POST['unassign_subjects'])) {
    if (!empty($_POST['subject_ids'])) {
        $ids = implode(",", array_map('intval', $_POST['subject_ids']));
        $conn->query("UPDATE subjects SET class_id = NULL WHERE subject_id IN ($ids)");
        $success = "Unassigned " . count($_POST['subject_ids']) . " subjects.";
    } else {
        $error = "Select subjects to unassign.";
    }
}

// =========================================================
// 4. FETCH DATA
// =========================================================

// Filters
$view_stu = isset($_GET['view_stu']) ? $_GET['view_stu'] : 'unassigned';
$view_sub = isset($_GET['view_sub']) ? $_GET['view_sub'] : 'unassigned';

// Students
$sql_stu = "SELECT s.student_id, s.student_name, s.school_register_no, c.class_name 
            FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE 1=1";
if($view_stu == 'unassigned') $sql_stu .= " AND (s.class_id IS NULL OR s.class_id = 0)";
$students = $conn->query($sql_stu . " ORDER BY s.student_name LIMIT 500");

// Subjects
$sql_sub = "SELECT s.subject_id, s.subject_name, s.subject_code, c.class_name 
            FROM subjects s LEFT JOIN classes c ON s.class_id = c.class_id WHERE 1=1";
if($view_sub == 'unassigned') $sql_sub .= " AND (s.class_id IS NULL OR s.class_id = 0)";
$subjects = $conn->query($sql_sub . " ORDER BY s.subject_name LIMIT 500");

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

    /* Headers */
    .header-students { background: #2c3e50; color: white; padding: 15px; border-radius: 8px 8px 0 0; }
    .header-subjects { background: #34495e; color: white; padding: 15px; border-radius: 8px 8px 0 0; }
    
    /* Layout */
    .scrollable-table { max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; background: white; }
    .table thead th { position: sticky; top: 0; background: #f8f9fa; z-index: 1; }
    
    /* Badges */
    .badge-current { background: #e3f2fd; color: #0d47a1; font-size: 0.75rem; border: 1px solid #bbdefb; }
    .badge-none { background: #ffebee; color: #c62828; font-size: 0.75rem; border: 1px solid #ffcdd2; }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Assignments Manager</h2>
                    <p class="text-secondary mb-0">Manage allocations. Cloning is automatic for multi-class subjects.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="header-students d-flex justify-content-between align-items-center">
                            <h5 class="m-0"><i class="fas fa-user-graduate me-2"></i> Students</h5>
                            <div class="btn-group btn-group-sm">
                                <a href="?view_stu=unassigned" class="btn <?php echo $view_stu=='unassigned'?'btn-light text-dark fw-bold':'btn-outline-light'; ?>">Unassigned</a>
                                <a href="?view_stu=all" class="btn <?php echo $view_stu=='all'?'btn-light text-dark fw-bold':'btn-outline-light'; ?>">Show All</a>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-8">
                                        <select name="target_class" class="form-select">
                                            <option value="">-- Select Class to Assign --</option>
                                            <?php 
                                            $classes->data_seek(0);
                                            while ($c = $classes->fetch_assoc()) echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>"; 
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <button type="submit" name="unassign_students" class="btn btn-outline-danger w-100" title="Remove from class">
                                            <i class="fas fa-user-slash"></i> Unassign
                                        </button>
                                    </div>
                                </div>

                                <div class="scrollable-table mb-3">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40"><input type="checkbox" class="form-check-input" onclick="toggleAll(this, 'stu_check')"></th>
                                                <th>Name</th>
                                                <th>Current Class</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($students->num_rows > 0): ?>
                                                <?php while ($stu = $students->fetch_assoc()): ?>
                                                <tr>
                                                    <td><input type="checkbox" name="student_ids[]" value="<?php echo $stu['student_id']; ?>" class="form-check-input stu_check"></td>
                                                    <td>
                                                        <div class="fw-bold"><?php echo $stu['student_name']; ?></div>
                                                        <small class="text-muted"><?php echo $stu['school_register_no']; ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if($stu['class_name']): ?>
                                                            <span class="badge badge-current"><?php echo $stu['class_name']; ?></span>
                                                        <?php else: ?>
                                                            <span class="badge badge-none">Unassigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="3" class="text-center py-4 text-muted">No students found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <button type="submit" name="assign_students" class="btn btn-primary w-100 fw-bold">
                                    <i class="fas fa-save me-2"></i> Assign Selected to Class
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="header-subjects d-flex justify-content-between align-items-center">
                            <h5 class="m-0"><i class="fas fa-book me-2"></i> Subjects</h5>
                            <div class="btn-group btn-group-sm">
                                <a href="?view_sub=unassigned" class="btn <?php echo $view_sub=='unassigned'?'btn-light text-dark fw-bold':'btn-outline-light'; ?>">Unassigned</a>
                                <a href="?view_sub=all" class="btn <?php echo $view_sub=='all'?'btn-light text-dark fw-bold':'btn-outline-light'; ?>">Show All</a>
                            </div>
                        </div>

                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Target Classes (Multi-Select)</label>
                                    <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto; background: #fdfdfd;">
                                        <?php 
                                        $classes->data_seek(0);
                                        while ($c = $classes->fetch_assoc()): 
                                        ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="target_classes[]" value="<?php echo $c['class_id']; ?>">
                                                <label class="form-check-label small"><?php echo $c['class_name']; ?></label>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                    <div class="form-text small text-muted"><i class="fas fa-info-circle"></i> Selecting multiple classes will CLONE the subject.</div>
                                </div>

                                <div class="text-end mb-2">
                                    <button type="submit" name="unassign_subjects" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-minus-circle"></i> Unassign Selected
                                    </button>
                                </div>

                                <div class="scrollable-table mb-3">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40"><input type="checkbox" class="form-check-input" onclick="toggleAll(this, 'sub_check')"></th>
                                                <th>Subject</th>
                                                <th>Current Class</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($subjects->num_rows > 0): ?>
                                                <?php while ($sub = $subjects->fetch_assoc()): ?>
                                                <tr>
                                                    <td><input type="checkbox" name="subject_ids[]" value="<?php echo $sub['subject_id']; ?>" class="form-check-input sub_check"></td>
                                                    <td>
                                                        <div class="fw-bold text-dark"><?php echo $sub['subject_name']; ?></div>
                                                        <small class="text-muted"><?php echo $sub['subject_code']; ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if($sub['class_name']): ?>
                                                            <span class="badge badge-current"><?php echo $sub['class_name']; ?></span>
                                                        <?php else: ?>
                                                            <span class="badge badge-none">Unassigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="3" class="text-center py-4 text-muted">No subjects found.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <button type="submit" name="assign_subjects" class="btn btn-success w-100 fw-bold">
                                    <i class="fas fa-copy me-2"></i> Assign / Clone to Classes
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