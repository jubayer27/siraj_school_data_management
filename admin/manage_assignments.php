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

        // Optional: Clean old subject enrollment to force re-enrollment based on new class
        $conn->query("DELETE FROM student_subject_enrollment WHERE student_id IN ($ids)");

        $success = "Successfully assigned " . count($_POST['student_ids']) . " students to class.";
    } else {
        $error = "Please select a target class and at least one student.";
    }
}

// =========================================================
// 3. LOGIC: SUBJECT CLONING (Master -> Class)
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
                // Determine Clean Base Code
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

                    // New Code: CODE-CLASS (e.g. BM-1USM)
                    $new_code = $base_code . "-" . str_replace(' ', '', $c_row['class_name']);

                    // Check duplicate
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
        $success = "Successfully cloned/distributed $count subjects.";
    }
}

// =========================================================
// 4. LOGIC: TEACHER ASSIGNMENT (User -> Subject)
// =========================================================
if (isset($_POST['assign_teacher'])) {
    if (!empty($_POST['teacher_id']) && !empty($_POST['sub_teacher_ids'])) {
        $tid = intval($_POST['teacher_id']);
        $count = 0;

        $stmt = $conn->prepare("INSERT INTO subject_teachers (subject_id, teacher_id) VALUES (?, ?)");

        foreach ($_POST['sub_teacher_ids'] as $sid) {
            $sid = intval($sid);
            // Check duplicate assignment
            $chk = $conn->query("SELECT 1 FROM subject_teachers WHERE subject_id = $sid AND teacher_id = $tid");
            if ($chk->num_rows == 0) {
                $stmt->bind_param("ii", $sid, $tid);
                if ($stmt->execute())
                    $count++;
            }
        }
        $success = "Teacher assigned to $count subjects.";
    } else {
        $error = "Select a teacher and at least one subject.";
    }
}

// =========================================================
// 5. DATA FETCHING
// =========================================================
$view_stu = isset($_GET['view_stu']) ? $_GET['view_stu'] : 'unassigned';
$class_filter = isset($_GET['class_filter']) ? intval($_GET['class_filter']) : 0;

// 1. Students Query
$stu_sql = "SELECT s.student_id, s.student_name, s.school_register_no, c.class_name 
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.class_id 
            WHERE 1=1";
if ($view_stu == 'unassigned')
    $stu_sql .= " AND (s.class_id IS NULL OR s.class_id = 0)";
$stu_sql .= " ORDER BY s.student_name LIMIT 500";
$students = $conn->query($stu_sql);

// 2. Master Subjects (Templates)
$templates = $conn->query("SELECT * FROM subjects WHERE class_id IS NULL OR class_id = 0 ORDER BY subject_name");

// 3. Class Subjects (For Teacher Assign)
$sub_sql = "SELECT s.subject_id, s.subject_name, s.subject_code, c.class_name,
            GROUP_CONCAT(u.full_name SEPARATOR ', ') as teachers
            FROM subjects s 
            JOIN classes c ON s.class_id = c.class_id 
            LEFT JOIN subject_teachers st ON s.subject_id = st.subject_id
            LEFT JOIN users u ON st.teacher_id = u.user_id
            WHERE 1=1";
if ($class_filter > 0)
    $sub_sql .= " AND s.class_id = $class_filter";
$sub_sql .= " GROUP BY s.subject_id ORDER BY c.class_name, s.subject_name";
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

    /* Card Styling */
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

    /* Section Colors */
    .header-stu {
        background: linear-gradient(135deg, #3498db, #2980b9);
    }

    .header-sub {
        background: linear-gradient(135deg, #7f8c8d, #34495e);
    }

    .header-tea {
        background: linear-gradient(135deg, #27ae60, #219150);
    }

    /* Tables & Scroll */
    .scroll-box {
        height: 350px;
        overflow-y: auto;
        border: 1px solid #eee;
        background: #fff;
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
                <span><i class="fas fa-user-graduate me-2"></i> 1. Assign Students to Class</span>
                <div class="btn-group btn-group-sm">
                    <a href="?view_stu=unassigned"
                        class="btn btn-outline-light <?php echo $view_stu == 'unassigned' ? 'active fw-bold' : ''; ?>">Unassigned
                        Only</a>
                    <a href="?view_stu=all"
                        class="btn btn-outline-light <?php echo $view_stu == 'all' ? 'active fw-bold' : ''; ?>">Show All
                        (Reassign)</a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" class="row">
                    <div class="col-md-4 border-end">
                        <label class="form-label fw-bold small text-muted">Target Class</label>
                        <select name="target_class" class="form-select mb-3" required>
                            <option value="">-- Select Destination Class --</option>
                            <?php
                            $classes->data_seek(0);
                            while ($c = $classes->fetch_assoc())
                                echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>";
                            ?>
                        </select>
                        <div class="alert alert-info small border-0 bg-light">
                            <i class="fas fa-info-circle me-1"></i> Select students from the list on the right, pick a
                            class here, and click Assign.
                        </div>
                        <button name="assign_students" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-arrow-right me-2"></i> Move to Class
                        </button>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold small text-muted">Student List</label>
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
                                                    <div class="fw-bold"><?php echo $s['student_name']; ?></div>
                                                    <small class="text-muted"><?php echo $s['school_register_no']; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo $s['class_name'] ? "<span class='badge badge-class'>{$s['class_name']}</span>" : "<span class='badge bg-warning text-dark'>Unassigned</span>"; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; else:
                                        echo "<tr><td colspan='3' class='text-center py-5 text-muted'>No students found matching filter.</td></tr>";
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header header-sub">
                <span><i class="fas fa-copy me-2"></i> 2. Distribute Subjects (Batch Clone)</span>
            </div>
            <div class="card-body">
                <form method="POST" class="row">
                    <div class="col-md-4 border-end">
                        <label class="form-label fw-bold small text-muted">Select Target Classes</label>
                        <div class="border rounded p-3 bg-light mb-3" style="max-height: 300px; overflow-y: auto;">
                            <?php
                            $classes->data_seek(0);
                            while ($c = $classes->fetch_assoc()): ?>
                                <div class="form-check">
                                    <input type="checkbox" name="target_classes[]" value="<?php echo $c['class_id']; ?>"
                                        class="form-check-input" id="c_<?php echo $c['class_id']; ?>">
                                    <label class="form-check-label"
                                        for="c_<?php echo $c['class_id']; ?>"><?php echo $c['class_name']; ?></label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <button name="assign_subjects" class="btn btn-secondary w-100 fw-bold">
                            <i class="fas fa-layer-group me-2"></i> Clone to Selected Classes
                        </button>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold small text-muted">Select Master Subjects (Templates)</label>
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
                                        echo "<tr><td colspan='3' class='text-center py-5 text-muted'>No master subject templates found. Import subjects without class first.</td></tr>";
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header header-tea">
                <span><i class="fas fa-chalkboard-teacher me-2"></i> 3. Assign Subject Teachers</span>
                <form method="GET" class="d-inline-block">
                    <select name="class_filter" class="form-select form-select-sm text-dark fw-bold"
                        onchange="this.form.submit()" style="min-width: 150px;">
                        <option value="0">All Classes</option>
                        <?php
                        $classes->data_seek(0);
                        while ($c = $classes->fetch_assoc())
                            echo "<option value='{$c['class_id']}' " . ($class_filter == $c['class_id'] ? 'selected' : '') . ">{$c['class_name']}</option>";
                        ?>
                    </select>
                </form>
            </div>
            <div class="card-body">
                <form method="POST" class="row">
                    <div class="col-md-4 border-end">
                        <label class="form-label fw-bold small text-muted">Select Teacher</label>
                        <select name="teacher_id" class="form-select mb-3" required>
                            <option value="">-- Choose Teacher --</option>
                            <?php while ($t = $teachers->fetch_assoc())
                                echo "<option value='{$t['user_id']}'>{$t['full_name']}</option>"; ?>
                        </select>
                        <button name="assign_teacher" class="btn btn-success w-100 fw-bold">
                            <i class="fas fa-plus-circle me-2"></i> Assign Teacher
                        </button>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold small text-muted">Select Subjects (Assigned to Classes)</label>
                        <div class="scroll-box">
                            <table class="table table-hover table-sticky mb-0">
                                <thead>
                                    <tr>
                                        <th width="40"><input type="checkbox" onclick="toggle(this, 't_chk')"></th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Assigned Teacher(s)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($class_subjects->num_rows > 0):
                                        while ($sub = $class_subjects->fetch_assoc()): ?>
                                            <tr>
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
                                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No subjects found. Clone subjects first.</td></tr>";
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    function toggle(source, className) {
        checkboxes = document.getElementsByClassName(className);
        for (var i = 0; i < checkboxes.length; i++) checkboxes[i].checked = source.checked;
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>