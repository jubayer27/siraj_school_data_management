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
// 2. LOGIC: STUDENT ASSIGNMENT
// =========================================================
if (isset($_POST['assign_students'])) {
    if (!empty($_POST['student_ids']) && !empty($_POST['target_class'])) {
        $class_id = intval($_POST['target_class']);
        $ids = implode(",", array_map('intval', $_POST['student_ids']));

        $conn->query("UPDATE students SET class_id = $class_id WHERE student_id IN ($ids)");
        $conn->query("DELETE FROM student_subject_enrollment WHERE student_id IN ($ids)"); // Force refresh

        $success = "Assigned " . count($_POST['student_ids']) . " students to class.";
    } else {
        $error = "Select a class and students.";
    }
}

if (isset($_POST['unassign_students'])) {
    if (!empty($_POST['student_ids'])) {
        $ids = implode(",", array_map('intval', $_POST['student_ids']));
        $conn->query("UPDATE students SET class_id = NULL WHERE student_id IN ($ids)");
        $conn->query("DELETE FROM student_subject_enrollment WHERE student_id IN ($ids)");
        $success = "Unassigned selected students.";
    }
}

// =========================================================
// 3. LOGIC: SUBJECT DISTRIBUTION (CLONING)
// =========================================================
if (isset($_POST['assign_subjects'])) {
    if (!empty($_POST['subject_ids']) && !empty($_POST['target_classes'])) {
        $subject_ids = $_POST['subject_ids'];
        $class_ids = $_POST['target_classes'];
        $count = 0;

        foreach ($subject_ids as $sid) {
            $sid = intval($sid);
            $orig = $conn->query("SELECT * FROM subjects WHERE subject_id = $sid")->fetch_assoc();

            if ($orig) {
                // Determine Base Code (Strip existing suffix)
                $base_code = $orig['subject_code'];
                if ($orig['class_id'] > 0) {
                    $old_c = $conn->query("SELECT class_name FROM classes WHERE class_id = {$orig['class_id']}")->fetch_assoc();
                    $suffix = "-" . str_replace(' ', '', $old_c['class_name']);
                    if (substr($base_code, -strlen($suffix)) === $suffix) {
                        $base_code = substr($base_code, 0, -strlen($suffix));
                    }
                }

                foreach ($class_ids as $cid) {
                    $cid = intval($cid);
                    $c_row = $conn->query("SELECT class_name FROM classes WHERE class_id = $cid")->fetch_assoc();
                    $new_code = $base_code . "-" . str_replace(' ', '', $c_row['class_name']);

                    // Check Duplicate
                    $chk = $conn->query("SELECT subject_id FROM subjects WHERE subject_code = '$new_code'");
                    if ($chk->num_rows == 0) {
                        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id) VALUES (?, ?, ?)");
                        $stmt->bind_param("ssi", $orig['subject_name'], $new_code, $cid);
                        if ($stmt->execute())
                            $count++;
                    }
                }
            }
        }
        $success = "Distributed $count subjects to classes.";
    }
}

if (isset($_POST['unassign_subjects'])) {
    if (!empty($_POST['subject_ids']) && !empty($_POST['target_classes'])) {
        // Logic to remove cloned subjects
        // For simplicity, this removes based on name matching in target classes
        $count = 0;
        foreach ($_POST['target_classes'] as $cid) {
            $cid = intval($cid);
            foreach ($_POST['subject_ids'] as $sid) {
                $sid = intval($sid);
                $orig = $conn->query("SELECT subject_name FROM subjects WHERE subject_id = $sid")->fetch_assoc();
                if ($orig) {
                    $sname = $conn->real_escape_string($orig['subject_name']);
                    $conn->query("DELETE FROM subjects WHERE class_id = $cid AND subject_name = '$sname'");
                    $count += $conn->affected_rows;
                }
            }
        }
        $success = "Removed $count subjects from classes.";
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
                if ($stmt->execute())
                    $count++;
            }
        }
        $success = "Assigned teacher to $count subjects.";
    } else {
        $error = "Please select a Teacher and at least one Subject.";
    }
}

if (isset($_POST['unassign_teacher'])) {
    if (!empty($_POST['sub_teacher_ids'])) {
        $ids = implode(",", array_map('intval', $_POST['sub_teacher_ids']));

        // If teacher is selected, remove ONLY that teacher. Else remove all.
        if (!empty($_POST['teacher_id'])) {
            $tid = intval($_POST['teacher_id']);
            $conn->query("DELETE FROM subject_teachers WHERE subject_id IN ($ids) AND teacher_id = $tid");
            $success = "Removed selected teacher from subjects.";
        } else {
            $conn->query("DELETE FROM subject_teachers WHERE subject_id IN ($ids)");
            $success = "Cleared ALL teachers from selected subjects.";
        }
    } else {
        $error = "Select subjects to unassign.";
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

// Templates (Unique Subjects)
$templates = $conn->query("SELECT * FROM subjects GROUP BY subject_name ORDER BY subject_name");

// Class Subjects (For Teachers) - Fetch ALL and filter with JS
$sub_sql = "SELECT s.subject_id, s.subject_name, s.subject_code, s.class_id, c.class_name,
            GROUP_CONCAT(u.full_name SEPARATOR ', ') as teachers
            FROM subjects s 
            JOIN classes c ON s.class_id = c.class_id 
            LEFT JOIN subject_teachers st ON s.subject_id = st.subject_id
            LEFT JOIN users u ON st.teacher_id = u.user_id
            GROUP BY s.subject_id ORDER BY c.class_name, s.subject_name";
$class_subjects = $conn->query($sub_sql);

// Dropdowns
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$teachers = $conn->query("SELECT * FROM users WHERE role IN ('class_teacher', 'subject_teacher') ORDER BY full_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
    }

    .main-content {
        margin-left: 260px;
        padding: 30px;
    }

    @media(max-width:992px) {
        .main-content {
            margin-left: 0;
        }
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        margin-bottom: 30px;
    }

    .card-header {
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 12px 12px 0 0 !important;
        color: white;
        padding: 15px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-stu {
        background: linear-gradient(135deg, #3498db, #2980b9);
    }

    .header-sub {
        background: linear-gradient(135deg, #7f8c8d, #34495e);
    }

    .header-tea {
        background: linear-gradient(135deg, #27ae60, #219150);
    }

    .scroll-box {
        height: 350px;
        overflow-y: auto;
        border: 1px solid #eee;
        background: #fff;
        border-radius: 6px;
    }

    .table-sticky th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 5;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .badge-class {
        background: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
    }

    /* Interactive Rows */
    .subject-row {
        transition: background 0.2s;
    }

    .subject-row:hover {
        background-color: #f9f9f9;
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark m-0">Assignments Manager</h2>
            <?php if ($success): ?>
                <div class="alert alert-success py-1 px-3 m-0"><i class="fas fa-check me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-1 px-3 m-0"><i class="fas fa-times me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header header-stu">
                <span><i class="fas fa-user-graduate me-2"></i> 1. Student Class Enrollment</span>
                <div class="btn-group btn-group-sm">
                    <a href="?view_stu=unassigned"
                        class="btn btn-outline-light <?php echo $view_stu == 'unassigned' ? 'active fw-bold' : ''; ?>">Unassigned</a>
                    <a href="?view_stu=all"
                        class="btn btn-outline-light <?php echo $view_stu == 'all' ? 'active fw-bold' : ''; ?>">Show All</a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" class="row">
                    <div class="col-md-4 border-end">
                        <label class="form-label fw-bold small text-muted">Select Destination Class</label>
                        <select name="target_class" class="form-select mb-3">
                            <option value="">-- Choose Class --</option>
                            <?php
                            $classes->data_seek(0);
                            while ($c = $classes->fetch_assoc())
                                echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>";
                            ?>
                        </select>
                        <div class="d-grid gap-2">
                            <button name="assign_students" class="btn btn-primary fw-bold"><i
                                    class="fas fa-check-circle me-2"></i> Assign Selected</button>
                            <button name="unassign_students" class="btn btn-outline-danger fw-bold"
                                onclick="return confirm('Remove selected students from their class?');"><i
                                    class="fas fa-times-circle me-2"></i> Unassign</button>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="scroll-box">
                            <table class="table table-hover table-sticky mb-0">
                                <thead>
                                    <tr>
                                        <th width="40"><input type="checkbox" onclick="toggle(this, 's_chk')"></th>
                                        <th>Name</th>
                                        <th>Current Class</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($students->num_rows > 0):
                                        while ($s = $students->fetch_assoc()): ?>
                                            <tr>
                                                <td><input type="checkbox" name="student_ids[]"
                                                        value="<?php echo $s['student_id']; ?>" class="s_chk"></td>
                                                <td>
                                                    <div class="fw-bold"><?php echo $s['student_name']; ?></div><small
                                                        class="text-muted"><?php echo $s['school_register_no']; ?></small>
                                                </td>
                                                <td><?php echo $s['class_name'] ? "<span class='badge badge-class'>{$s['class_name']}</span>" : "<span class='badge bg-warning text-dark'>Unassigned</span>"; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; else:
                                        echo "<tr><td colspan='3' class='text-center py-5 text-muted'>No students found.</td></tr>"; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header header-sub">
                <span><i class="fas fa-copy me-2"></i> 2. Distribute Subjects (Templates)</span>
            </div>
            <div class="card-body">
                <form method="POST" class="row">
                    <div class="col-md-4 border-end">
                        <label class="form-label fw-bold small text-muted">Select Target Classes</label>
                        <div class="border rounded p-3 bg-light mb-3" style="max-height: 250px; overflow-y: auto;">
                            <?php
                            $classes->data_seek(0);
                            while ($c = $classes->fetch_assoc()): ?>
                                <div class="form-check">
                                    <input type="checkbox" name="target_classes[]" value="<?php echo $c['class_id']; ?>"
                                        class="form-check-input">
                                    <label class="form-check-label small"><?php echo $c['class_name']; ?></label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="d-grid gap-2">
                            <button name="assign_subjects" class="btn btn-secondary fw-bold"><i
                                    class="fas fa-clone me-2"></i> Clone to Classes</button>
                            <button name="unassign_subjects" class="btn btn-outline-danger fw-bold"
                                onclick="return confirm('Delete selected subjects from target classes?');"><i
                                    class="fas fa-trash me-2"></i> Delete from Classes</button>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="scroll-box">
                            <table class="table table-hover table-sticky mb-0">
                                <thead>
                                    <tr>
                                        <th width="40"><input type="checkbox" onclick="toggle(this, 'sub_chk')"></th>
                                        <th>Subject Name</th>
                                        <th>Base Code</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($templates->num_rows > 0):
                                        while ($t = $templates->fetch_assoc()): ?>
                                            <tr>
                                                <td><input type="checkbox" name="subject_ids[]"
                                                        value="<?php echo $t['subject_id']; ?>" class="sub_chk"></td>
                                                <td class="fw-bold"><?php echo $t['subject_name']; ?></td>
                                                <td class="text-muted font-monospace"><?php echo $t['subject_code']; ?></td>
                                            </tr>
                                        <?php endwhile; else:
                                        echo "<tr><td colspan='3' class='text-center py-5 text-muted'>No subjects found.</td></tr>"; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header header-tea">
                <span><i class="fas fa-chalkboard-teacher me-2"></i> 3. Assign Teachers to Subjects</span>
            </div>
            <div class="card-body">
                <form method="POST" class="row">
                    <div class="col-md-4 border-end">

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">1. Select Teacher</label>
                            <select name="teacher_id" class="form-select">
                                <option value="">-- Choose Teacher --</option>
                                <?php while ($t = $teachers->fetch_assoc())
                                    echo "<option value='{$t['user_id']}'>{$t['full_name']}</option>"; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">2. Filter by Class</label>
                            <select id="classFilter" class="form-select border-primary" onchange="filterSubjects()">
                                <option value="all">Show All Classes</option>
                                <?php
                                $classes->data_seek(0);
                                while ($c = $classes->fetch_assoc())
                                    echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>";
                                ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button name="assign_teacher" class="btn btn-success fw-bold"><i
                                    class="fas fa-plus-circle me-2"></i> Assign Teacher</button>
                            <button name="unassign_teacher" class="btn btn-outline-danger fw-bold"
                                onclick="return confirm('Unassign teacher from selected subjects?');"><i
                                    class="fas fa-user-minus me-2"></i> Unassign</button>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold small text-muted">3. Select Subjects to Assign</label>
                        <div class="scroll-box">
                            <table class="table table-hover table-sticky mb-0" id="teacherTable">
                                <thead>
                                    <tr>
                                        <th width="40"><input type="checkbox" onclick="toggle(this, 't_chk')"></th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Assigned To</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($class_subjects->num_rows > 0):
                                        while ($sub = $class_subjects->fetch_assoc()): ?>
                                            <tr class="subject-row" data-class-id="<?php echo $sub['class_id']; ?>">
                                                <td><input type="checkbox" name="sub_teacher_ids[]"
                                                        value="<?php echo $sub['subject_id']; ?>" class="t_chk"></td>
                                                <td class="fw-bold"><?php echo $sub['subject_name']; ?></td>
                                                <td><span class="badge badge-class"><?php echo $sub['class_name']; ?></span>
                                                </td>
                                                <td>
                                                    <?php echo $sub['teachers'] ? "<span class='badge bg-success bg-opacity-10 text-success border border-success'>{$sub['teachers']}</span>" : "<span class='text-muted small'>-</span>"; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; else:
                                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No subjects available. Distribute subjects first.</td></tr>"; endif; ?>
                                </tbody>
                            </table>
                            <div id="noSubjectsMsg" class="text-center py-5 text-muted" style="display:none;">No
                                subjects found for this class.</div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    // Checkbox Toggle
    function toggle(source, className) {
        var checkboxes = document.querySelectorAll('.' + className);
        for (var i = 0; i < checkboxes.length; i++) {
            // Only toggle visible checkboxes
            if (checkboxes[i].closest('tr').style.display !== 'none') {
                checkboxes[i].checked = source.checked;
            }
        }
    }

    // Client-Side Subject Filtering
    function filterSubjects() {
        var classId = document.getElementById('classFilter').value;
        var rows = document.querySelectorAll('.subject-row');
        var visibleCount = 0;

        rows.forEach(function (row) {
            if (classId === 'all' || row.getAttribute('data-class-id') === classId) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle Empty Message
        document.getElementById('noSubjectsMsg').style.display = (visibleCount === 0) ? 'block' : 'none';

        // Uncheck "Select All" when filtering
        var masterCheck = document.querySelector('input[onclick="toggle(this, \'t_chk\')"]');
        if (masterCheck) masterCheck.checked = false;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>