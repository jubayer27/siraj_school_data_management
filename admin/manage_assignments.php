<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = isset($_GET['msg']) ? $_GET['msg'] : "";
$error = "";

// =========================================================
// 2. LOGIC: STUDENT ASSIGNMENT
// =========================================================
if (isset($_POST['assign_students'])) {
    if (!empty($_POST['student_ids']) && !empty($_POST['target_class'])) {
        $class_id = intval($_POST['target_class']);
        $ids = implode(",", array_map('intval', $_POST['student_ids']));

        $conn->query("UPDATE students SET class_id = $class_id WHERE student_id IN ($ids)");
        $conn->query("DELETE FROM student_subject_enrollment WHERE student_id IN ($ids)"); // Force refresh

        header("Location: manage_assignments.php?msg=Assigned students successfully");
        exit();
    } else {
        $error = "Select a class and students.";
    }




}

if (isset($_POST['unassign_students'])) {
    if (!empty($_POST['student_ids'])) {
        $ids = implode(",", array_map('intval', $_POST['student_ids']));
        $conn->query("UPDATE students SET class_id = NULL WHERE student_id IN ($ids)");
        $conn->query("DELETE FROM student_subject_enrollment WHERE student_id IN ($ids)");
        
        header("Location: manage_assignments.php?msg=Unassigned students successfully");
        exit();
    }
}

// =========================================================
// 3. LOGIC: SUBJECT DISTRIBUTION (Bulk Create for Classes)
// =========================================================
if (isset($_POST['assign_subjects'])) {
    if (!empty($_POST['subject_ids']) && !empty($_POST['target_classes'])) {
        $subject_ids = $_POST['subject_ids'];
        $class_ids = $_POST['target_classes'];
        $count = 0;

        foreach ($class_ids as $cid) {
            $cid = intval($cid);
            // Fetch Class Name for coding
            $c_row = $conn->query("SELECT class_name FROM classes WHERE class_id = $cid")->fetch_assoc();
            $class_suffix = "-" . str_replace(' ', '', $c_row['class_name']);

            foreach ($subject_ids as $sid) {
                $sid = intval($sid);
                // Fetch Template Subject
                $orig = $conn->query("SELECT * FROM subjects WHERE subject_id = $sid")->fetch_assoc();

                if ($orig) {
                    // Create New Code: MATH-5Amanah
                    // If original code already has a class suffix, strip it first (optional logic)
                    $base_code = explode('-', $orig['subject_code'])[0]; 
                    $new_code = $base_code . $class_suffix;

                    // Check if this subject already exists for this class
                    $chk = $conn->query("SELECT subject_id FROM subjects WHERE class_id = $cid AND subject_name = '{$orig['subject_name']}'");
                    
                    if ($chk->num_rows == 0) {
                        // Create the subject instance for this class
                        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id) VALUES (?, ?, ?)");
                        $stmt->bind_param("ssi", $orig['subject_name'], $new_code, $cid);
                        if ($stmt->execute()) {
                            $count++;
                        }
                    }
                }
            }
        }
        header("Location: manage_assignments.php?msg=Successfully assigned $count subjects to classes");
        exit();
    } else {
        $error = "Please select subjects and target classes.";
    }
}

// =========================================================
// 4. LOGIC: TEACHER ASSIGNMENT
// =========================================================
if (isset($_POST['assign_teacher'])) {
    if (!empty($_POST['teacher_id']) && !empty($_POST['sub_teacher_ids'])) {
        $tid = intval($_POST['teacher_id']);
        $count = 0;
        $stmt = $conn->prepare("INSERT INTO subject_teachers (subject_id, teacher_id) VALUES (?, ?)");

        foreach ($_POST['sub_teacher_ids'] as $sid) {
            $sid = intval($sid);
            // Check duplicate
            $chk = $conn->query("SELECT 1 FROM subject_teachers WHERE subject_id = $sid AND teacher_id = $tid");
            if ($chk->num_rows == 0) {
                $stmt->bind_param("ii", $sid, $tid);
                if ($stmt->execute()) $count++;
            }
        }
        header("Location: manage_assignments.php?msg=Teacher assigned to $count subjects");
        exit();
    } else {
        $error = "Please select a Teacher and at least one Subject.";
    }
}

if (isset($_POST['unassign_teacher'])) {
    if (!empty($_POST['sub_teacher_ids'])) {
        $ids = implode(",", array_map('intval', $_POST['sub_teacher_ids']));
        
        if (!empty($_POST['teacher_id'])) {
            $tid = intval($_POST['teacher_id']);
            $conn->query("DELETE FROM subject_teachers WHERE subject_id IN ($ids) AND teacher_id = $tid");
        } else {
            $conn->query("DELETE FROM subject_teachers WHERE subject_id IN ($ids)");
        }
        header("Location: manage_assignments.php?msg=Teacher assignments removed");
        exit();
    }
}

// =========================================================
// 5. FETCH DATA
// =========================================================
$view_stu = isset($_GET['view_stu']) ? $_GET['view_stu'] : 'unassigned';

// Students
$stu_sql = "SELECT s.student_id, s.student_name, s.school_register_no, c.class_name 
            FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE 1=1";
if ($view_stu == 'unassigned')
    $stu_sql .= " AND (s.class_id IS NULL OR s.class_id = 0)";
$stu_sql .= " ORDER BY s.student_name LIMIT 500";
$students = $conn->query($stu_sql);

// Templates (Get distinct Subject Names to use as "Master List")
$templates = $conn->query("SELECT * FROM subjects GROUP BY subject_name ORDER BY subject_name");

// Active Class Subjects (For Teacher Assignment)
$sub_sql = "SELECT s.subject_id, s.subject_name, s.subject_code, s.class_id, c.class_name,
            GROUP_CONCAT(u.full_name SEPARATOR ', ') as teachers
            FROM subjects s 
            JOIN classes c ON s.class_id = c.class_id 
            LEFT JOIN subject_teachers st ON s.subject_id = st.subject_id
            LEFT JOIN users u ON st.teacher_id = u.user_id
            WHERE s.class_id IS NOT NULL AND s.class_id != 0
            GROUP BY s.subject_id ORDER BY c.class_name, s.subject_name";
$class_subjects = $conn->query($sub_sql);

// Dropdowns
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$teachers = $conn->query("SELECT * FROM users WHERE role IN ('class_teacher', 'subject_teacher') ORDER BY full_name");
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

    /* CARD STYLES */
    .manage-card {
        border: none; border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        margin-bottom: 30px; overflow: hidden;
    }
    
    .card-header-custom {
        padding: 15px 20px; color: white; font-weight: 700;
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .bg-gradient-blue { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
    .bg-gradient-teal { background: linear-gradient(135deg, #20c997, #198754); }
    .bg-gradient-purple { background: linear-gradient(135deg, #6f42c1, #59359a); }

    /* SCROLL BOXES */
    .scroll-table-container {
        height: 350px; overflow-y: auto;
        border: 1px solid #dee2e6; border-radius: 8px;
        background: #fff;
    }
    
    .table-sticky th { position: sticky; top: 0; background: #f8f9fa; z-index: 2; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    
    /* BADGES & BUTTONS */
    .badge-class { background-color: #e3f2fd; color: #0d6efd; border: 1px solid #9ec5fe; }
    .badge-teacher { background-color: #d1e7dd; color: #0f5132; border: 1px solid #a3cfbb; }
    
    .form-label { font-weight: 600; font-size: 0.85rem; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
    
    /* CHECKBOX LIST */
    .checkbox-list { max-height: 250px; overflow-y: auto; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; }
    .checkbox-item { padding: 6px 10px; border-bottom: 1px solid #eee; display: block; }
    .checkbox-item:last-child { border-bottom: none; }
    .checkbox-item:hover { background: #e9ecef; border-radius: 4px; }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Assignments Manager</h2>
                    <p class="text-secondary mb-0">Batch assign students, subjects, and teachers.</p>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success py-2 px-3 m-0 rounded-pill shadow-sm">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 m-0 rounded-pill shadow-sm">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card manage-card">
                <div class="card-header-custom bg-gradient-blue">
                    <span><i class="fas fa-user-graduate me-2"></i> 1. Assign Students to Class</span>
                    <div class="btn-group btn-group-sm">
                        <a href="?view_stu=unassigned" class="btn btn-light <?php echo $view_stu == 'unassigned' ? 'active fw-bold text-primary' : 'text-muted'; ?>">Unassigned</a>
                        <a href="?view_stu=all" class="btn btn-light <?php echo $view_stu == 'all' ? 'active fw-bold text-primary' : 'text-muted'; ?>">All Students</a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-4">
                        <div class="col-lg-4 border-end">
                            <label class="form-label">Target Class</label>
                            <select name="target_class" class="form-select mb-3 shadow-sm">
                                <option value="">-- Select Class --</option>
                                <?php
                                $classes->data_seek(0);
                                while ($c = $classes->fetch_assoc())
                                    echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>";
                                ?>
                            </select>
                            
                            <div class="d-grid gap-2">
                                <button name="assign_students" class="btn btn-primary fw-bold shadow-sm">
                                    <i class="fas fa-arrow-right me-2"></i> Assign Selected
                                </button>
                                <button name="unassign_students" class="btn btn-outline-danger fw-bold" onclick="return confirm('Remove selected students from their class?');">
                                    <i class="fas fa-user-minus me-2"></i> Unassign
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <div class="scroll-table-container">
                                <table class="table table-hover table-sticky mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th width="40"><input class="form-check-input" type="checkbox" onclick="toggle(this, 'stu-chk')"></th>
                                            <th>Student Name</th>
                                            <th>Current Class</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($students->num_rows > 0): while ($s = $students->fetch_assoc()): ?>
                                            <tr>
                                                <td><input type="checkbox" name="student_ids[]" value="<?php echo $s['student_id']; ?>" class="form-check-input stu-chk"></td>
                                                <td>
                                                    <div class="fw-bold"><?php echo $s['student_name']; ?></div>
                                                    <small class="text-muted font-monospace"><?php echo $s['school_register_no']; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo $s['class_name'] ? "<span class='badge badge-class'>{$s['class_name']}</span>" : "<span class='badge bg-warning text-dark'>Unassigned</span>"; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; else: ?>
                                            <tr><td colspan="3" class="text-center py-5 text-muted">No students found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card manage-card">
                <div class="card-header-custom bg-gradient-teal">
                    <span><i class="fas fa-book-reader me-2"></i> 2. Distribute Subjects to Classes</span>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-4">
                        <div class="col-lg-4 border-end">
                            <label class="form-label">Target Classes</label>
                            <div class="checkbox-list mb-3">
                                <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="target_classes[]" value="<?php echo $c['class_id']; ?>" class="form-check-input me-2">
                                        <?php echo $c['class_name']; ?>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                            
                            <button name="assign_subjects" class="btn btn-success fw-bold w-100 shadow-sm">
                                <i class="fas fa-plus-circle me-2"></i> Assign Subjects
                            </button>
                        </div>
                        
                        <div class="col-lg-8">
                            <label class="form-label mb-2">Select Subjects (Templates)</label>
                            <div class="scroll-table-container">
                                <table class="table table-hover table-sticky mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th width="40"><input class="form-check-input" type="checkbox" onclick="toggle(this, 'sub-chk')"></th>
                                            <th>Subject Name</th>
                                            <th>Default Code</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($templates->num_rows > 0): while ($t = $templates->fetch_assoc()): ?>
                                            <tr>
                                                <td><input type="checkbox" name="subject_ids[]" value="<?php echo $t['subject_id']; ?>" class="form-check-input sub-chk"></td>
                                                <td class="fw-bold"><?php echo $t['subject_name']; ?></td>
                                                <td class="text-muted font-monospace"><?php echo $t['subject_code']; ?></td>
                                            </tr>
                                        <?php endwhile; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card manage-card">
                <div class="card-header-custom bg-gradient-purple">
                    <span><i class="fas fa-chalkboard-teacher me-2"></i> 3. Assign Teacher to Class Subjects</span>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-4">
                        <div class="col-lg-4 border-end">
                            <div class="mb-3">
                                <label class="form-label">Select Teacher</label>
                                <select name="teacher_id" class="form-select shadow-sm">
                                    <option value="">-- Choose Teacher --</option>
                                    <?php while ($t = $teachers->fetch_assoc()) echo "<option value='{$t['user_id']}'>{$t['full_name']}</option>"; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Filter by Class</label>
                                <select id="classFilter" class="form-select border-primary bg-light" onchange="filterSubjects()">
                                    <option value="all">Show All Classes</option>
                                    <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()) echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>"; ?>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button name="assign_teacher" class="btn btn-primary fw-bold shadow-sm">
                                    <i class="fas fa-link me-2"></i> Assign Teacher
                                </button>
                                <button name="unassign_teacher" class="btn btn-outline-danger fw-bold" onclick="return confirm('Remove teacher from selected subjects?');">
                                    <i class="fas fa-unlink me-2"></i> Unassign
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="scroll-table-container">
                                <table class="table table-hover table-sticky mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th width="40"><input class="form-check-input" type="checkbox" onclick="toggle(this, 'tea-chk')"></th>
                                            <th>Subject</th>
                                            <th>Class</th>
                                            <th>Current Teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody id="subjectTableBody">
                                        <?php if ($class_subjects->num_rows > 0): while ($sub = $class_subjects->fetch_assoc()): ?>
                                            <tr class="subject-row" data-class-id="<?php echo $sub['class_id']; ?>">
                                                <td><input type="checkbox" name="sub_teacher_ids[]" value="<?php echo $sub['subject_id']; ?>" class="form-check-input tea-chk"></td>
                                                <td class="fw-bold"><?php echo $sub['subject_name']; ?></td>
                                                <td><span class="badge badge-class"><?php echo $sub['class_name']; ?></span></td>
                                                <td>
                                                    <?php echo $sub['teachers'] ? "<span class='badge badge-teacher'><i class='fas fa-user me-1'></i> {$sub['teachers']}</span>" : "<span class='text-muted small'>-</span>"; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; else: ?>
                                            <tr><td colspan="4" class="text-center py-5 text-muted">No active subjects found. Please distribute subjects first.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <div id="noSubjectsMsg" class="text-center py-5 text-muted" style="display:none;">No subjects found for this class.</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Universal Checkbox Toggler
    function toggle(source, className) {
        var checkboxes = document.querySelectorAll('.' + className);
        for (var i = 0; i < checkboxes.length; i++) {
            // Only select visible rows (respecting the filter)
            if (checkboxes[i].closest('tr').style.display !== 'none') {
                checkboxes[i].checked = source.checked;
            }
        }
    }

    // Filter Subjects by Class
    function filterSubjects() {
        var classId = document.getElementById('classFilter').value;
        var rows = document.querySelectorAll('.subject-row');
        var count = 0;

        rows.forEach(function(row) {
            if (classId === 'all' || row.getAttribute('data-class-id') === classId) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('noSubjectsMsg').style.display = (count === 0) ? 'block' : 'none';
        
        // Reset master checkbox
        var master = document.querySelector('input[onclick="toggle(this, \'tea-chk\')"]');
        if(master) master.checked = false;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>