<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/db.php';

// 1. SECURITY & AUTH
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- HELPER: Grade Calculator ---
function calculateGrade($obtained, $total)
{
    if ($total <= 0)
        return 'F';
    $percentage = ($obtained / $total) * 100;

    if ($percentage >= 80)
        return 'A';
    if ($percentage >= 60)
        return 'B';
    if ($percentage >= 40)
        return 'C';
    return 'F';
}

// ==========================================
// 0. FETCH EXAM DETAILS (GLOBAL)
// ==========================================
$current_max_mark = 100; // Default
$current_exam_name = ''; // Stores 'Midterm', 'Final', etc.
$sel_exam_id = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';

if ($sel_exam_id) {
    $eq = $conn->query("SELECT max_marks, exam_name FROM exam_types WHERE exam_id = '$sel_exam_id'");
    if ($eq && $eq->num_rows > 0) {
        $erow = $eq->fetch_assoc();
        $current_max_mark = floatval($erow['max_marks']);
        $current_exam_name = $erow['exam_name']; // Get the string name for DB querying
    }
}

// ==========================================
// 2. EXPORT LOGIC (CSV Download)
// ==========================================
if (isset($_POST['export_csv'])) {
    $cid = $_POST['class_id'];
    $sid = $_POST['subject_id'];
    $etype = $_POST['exam_type']; // exam_id

    // Fetch Exam Info
    $ename = "Exam";
    $emax = 100;
    $eq = $conn->query("SELECT exam_name, max_marks FROM exam_types WHERE exam_id = '$etype'");
    if ($eq->num_rows > 0) {
        $edata = $eq->fetch_assoc();
        $ename_str = $edata['exam_name']; // Actual name for DB lookup
        $ename = str_replace(' ', '', $edata['exam_name']); // For filename
        $emax = floatval($edata['max_marks']);
    }

    // Fetch Data (Fixed JOIN to use exam_type string)
    $sql = "SELECT st.school_register_no, st.student_name, sm.mark_obtained, sm.grade 
            FROM students st 
            JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
            LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = '$ename_str'
            WHERE sse.subject_id = $sid AND sse.class_id = $cid
            ORDER BY st.student_name ASC";
    $rows = $conn->query($sql);

    // Set Headers
    $filename = "Marks_Class" . $cid . "_Sub" . $sid . "_" . $ename . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Register No', 'Student Name', "Mark (Max: $emax)", 'Grade'));

    while ($row = $rows->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

include 'includes/header.php';

// ==========================================
// 3. IMPORT LOGIC (CSV Upload)
// ==========================================
$msg = "";
$msg_type = "";

if (isset($_POST['import_marks']) && isset($_FILES['csv_file'])) {
    $cid = $_POST['class_id'];
    $sid = $_POST['subject_id'];
    $etype = $_POST['exam_type']; // exam_id
    $filename = $_FILES['csv_file']['tmp_name'];

    // Get Max Mark & Name
    $mq = $conn->query("SELECT max_marks, exam_name FROM exam_types WHERE exam_id = '$etype'");
    if ($mq->num_rows > 0) {
        $edata = $mq->fetch_assoc();
        $import_max = floatval($edata['max_marks']);
        $import_name = $edata['exam_name'];
    } else {
        $import_max = 100;
        $import_name = 'Midterm'; // Fallback
    }

    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($filename, "r");
        $count = 0;
        fgetcsv($file); // Skip header

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $reg_no = $conn->real_escape_string(trim($data[0]));

            if (!isset($data[2]) || $data[2] === "")
                continue;

            $mark_val = floatval($data[2]);
            if ($mark_val > $import_max)
                $mark_val = $import_max; // Cap

            $g = calculateGrade($mark_val, $import_max);

            // Find Enrollment
            $find_stu = $conn->query("SELECT sse.enrollment_id 
                                      FROM students st 
                                      JOIN student_subject_enrollment sse ON st.student_id = sse.student_id 
                                      WHERE st.school_register_no = '$reg_no' 
                                      AND sse.subject_id = $sid 
                                      AND sse.class_id = $cid");

            if ($find_stu->num_rows > 0) {
                $eid = $find_stu->fetch_assoc()['enrollment_id'];

                // Update/Insert using EXAM NAME (exam_type)
                $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_type = '$import_name'");

                if ($chk->num_rows > 0) {
                    $upd = $conn->prepare("UPDATE student_marks SET mark_obtained=?, max_mark=?, grade=? WHERE enrollment_id=? AND exam_type=?");
                    $upd->bind_param("ddss", $mark_val, $import_max, $g, $eid, $import_name);
                    $upd->execute();
                } else {
                    // Note: Removed exam_id from INSERT if column doesn't exist
                    $ins = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_type, mark_obtained, max_mark, grade) VALUES (?, ?, ?, ?, ?)");
                    $ins->bind_param("isdds", $eid, $import_name, $mark_val, $import_max, $g);
                    $ins->execute();
                }
                $count++;
            }
        }
        fclose($file);
        $msg = "Import Successful! Updated $count records.";
        $msg_type = "success";
    } else {
        $msg = "Invalid file.";
        $msg_type = "error";
    }
}

// ==========================================
// 4. MANUAL SAVE LOGIC
// ==========================================
if (isset($_POST['save_changes'])) {
    $etype = $_POST['exam_type']; // exam_id

    // Get Max Mark & Name
    $mq = $conn->query("SELECT max_marks, exam_name FROM exam_types WHERE exam_id = '$etype'");
    if ($mq->num_rows > 0) {
        $edata = $mq->fetch_assoc();
        $save_max = floatval($edata['max_marks']);
        $save_name = $edata['exam_name'];
    } else {
        $save_max = 100;
        $save_name = 'Midterm';
    }

    $count = 0;

    if (isset($_POST['marks'])) {
        foreach ($_POST['marks'] as $eid => $val) {
            if ($val === "")
                continue;

            $val = floatval($val);
            if ($val > $save_max)
                $val = $save_max; // Cap

            $g = calculateGrade($val, $save_max);

            // Use exam_type (NAME) for query
            $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_type = '$save_name'");

            if ($chk->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE student_marks SET mark_obtained=?, max_mark=?, grade=? WHERE enrollment_id=? AND exam_type=?");
                $stmt->bind_param("ddss", $val, $save_max, $g, $eid, $save_name);
            } else {
                $stmt = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_type, mark_obtained, max_mark, grade) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isdds", $eid, $save_name, $val, $save_max, $g);
            }
            $stmt->execute();
            $count++;
        }
        $msg = "Changes saved successfully. Updated $count records.";
        $msg_type = "success";
    }
}

// ==========================================
// 5. FETCH DATA FOR VIEW
// ==========================================
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$exam_types = $conn->query("SELECT * FROM exam_types WHERE status='active' ORDER BY created_at DESC");

$sel_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$sel_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';

// Auto-select latest exam if not set
if (!$sel_exam_id && $exam_types->num_rows > 0) {
    $first_exam = $exam_types->fetch_assoc();
    $sel_exam_id = $first_exam['exam_id'];
    $current_max_mark = floatval($first_exam['max_marks']);
    $current_exam_name = $first_exam['exam_name'];
    $exam_types->data_seek(0);
}

$subjects = [];
if ($sel_class) {
    $sub_q = $conn->query("SELECT * FROM subjects WHERE class_id = $sel_class ORDER BY subject_name");
    while ($s = $sub_q->fetch_assoc())
        $subjects[] = $s;
}

$students = null;
$stats = ['avg' => 0, 'pass' => 0, 'fail' => 0, 'max' => 0, 'grade_counts' => ['A' => 0, 'B' => 0, 'C' => 0, 'F' => 0]];

if ($sel_class && $sel_subject && $sel_exam_id) {
    // FIX: Using exam_type (name) for the JOIN
    $sql = "SELECT st.student_id, st.student_name, st.school_register_no, st.photo, sse.enrollment_id, sm.mark_id, sm.mark_obtained, sm.grade
            FROM students st
            JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
            LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = '$current_exam_name'
            WHERE sse.subject_id = $sel_subject AND sse.class_id = $sel_class
            ORDER BY st.student_name ASC";
    $students = $conn->query($sql);

    // Calculate Stats
    $total_marks = 0;
    $count_marks = 0;
    while ($row = $students->fetch_assoc()) {
        if ($row['mark_obtained'] !== null) {
            $m = $row['mark_obtained'];
            $total_marks += $m;
            $count_marks++;

            // Pass Logic (40%)
            if (($m / $current_max_mark) * 100 >= 40)
                $stats['pass']++;
            else
                $stats['fail']++;

            if ($m > $stats['max'])
                $stats['max'] = $m;

            $grade = $row['grade'];
            if (isset($stats['grade_counts'][$grade]))
                $stats['grade_counts'][$grade]++;
        }
    }
    // Avg as percentage
    if ($count_marks > 0)
        $stats['avg'] = round(($total_marks / ($count_marks * $current_max_mark)) * 100, 1) . "%";

    $students->data_seek(0);
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    /* ... (Your existing CSS here) ... */
    /* Add this for validation visual */
    .input-mark:invalid {
        border-color: #e74c3c;
        background-color: #fff5f5;
    }

    body {
        background-color: #f0f2f5;
        font-family: 'Segoe UI', system-ui, sans-serif;
        overflow-x: hidden;
    }

    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 40px !important;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        border-top: 4px solid #FFD700;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 900;
        color: #2c3e50;
    }

    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
    }

    .custom-table th {
        background: #f8f9fb;
        padding: 15px 30px;
        text-align: left;
        font-weight: 700;
        border-bottom: 1px solid #eee;
    }

    .custom-table td {
        padding: 15px 30px;
        border-bottom: 1px solid #f9f9f9;
        vertical-align: middle;
    }

    .input-mark {
        width: 80px;
        padding: 8px;
        text-align: center;
        border: 2px solid #eee;
        border-radius: 8px;
        font-weight: 700;
    }

    .input-mark:focus {
        border-color: #3498db;
        outline: none;
    }

    .btn-gold {
        background: linear-gradient(135deg, #FFD700, #f39c12);
        border: none;
        color: white;
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 700;
    }

    @media (max-width: 992px) {
        .main-content {
            width: 100% !important;
            margin-left: 0;
        }

        .stats-container {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1 class="fw-bold m-0">Marks Control Center</h1>
                <p class="text-muted m-0">Manage student grades, analytics, and reports.</p>
            </div>
            <?php if ($sel_class && $sel_subject): ?>
                <div class="d-flex gap-3">
                    <button onclick="document.getElementById('importBox').style.display='block'"
                        class="btn btn-outline-secondary fw-bold px-4">
                        <i class="fas fa-file-import me-2"></i> Import
                    </button>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo $sel_exam_id; ?>">
                        <button type="submit" name="export_csv" class="btn btn-outline-success fw-bold px-4">
                            <i class="fas fa-file-export me-2"></i> Export
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo ($msg_type == 'success') ? 'success' : 'danger'; ?> mb-4 shadow-sm">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="filter-card">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="fw-bold text-muted small mb-2">1. SELECT CLASS</label>
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose Class --</option>
                        <?php $classes->data_seek(0);
                        while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo ($sel_class == $c['class_id']) ? 'selected' : ''; ?>>
                                <?php echo $c['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-bold text-muted small mb-2">2. SELECT SUBJECT</label>
                    <select name="subject_id" class="form-select" onchange="this.form.submit()" <?php echo !$sel_class ? 'disabled' : ''; ?>>
                        <option value="">-- Choose Subject --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['subject_id']; ?>" <?php echo ($sel_subject == $s['subject_id']) ? 'selected' : ''; ?>>
                                <?php echo $s['subject_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-bold text-muted small mb-2">3. EXAM TERM</label>
                    <select name="exam_type" class="form-select" onchange="this.form.submit()">
                        <?php if ($exam_types->num_rows > 0):
                            $exam_types->data_seek(0);
                            while ($et = $exam_types->fetch_assoc()): ?>
                                <option value="<?php echo $et['exam_id']; ?>" <?php echo ($sel_exam_id == $et['exam_id']) ? 'selected' : ''; ?>>
                                    <?php echo $et['exam_name']; ?> (Max: <?php echo $et['max_marks']; ?>)
                                </option>
                            <?php endwhile; else:
                            echo "<option value=''>No Exams Created</option>"; endif; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($students): ?>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value text-primary"><?php echo $stats['avg']; ?></div>
                    <div class="stat-title">Avg Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value text-success"><?php echo $stats['pass']; ?></div>
                    <div class="stat-title">Passed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value text-danger"><?php echo $stats['fail']; ?></div>
                    <div class="stat-title">Failed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value text-warning"><?php echo $stats['max']; ?></div>
                    <div class="stat-title">Highest</div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                <input type="hidden" name="exam_type" value="<?php echo $sel_exam_id; ?>">

                <div class="table-container">
                    <div class="table-header">
                        <div>
                            <h5 class="fw-bold m-0 text-dark">Student Marks Roster</h5>
                            <small class="text-muted">Max Mark: <?php echo $current_max_mark; ?></small>
                        </div>
                        <button type="submit" name="save_changes" class="btn btn-gold shadow-sm">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Register No</th>
                                    <th class="text-center">Score (Max: <?php echo $current_max_mark; ?>)</th>
                                    <th class="text-center">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students->num_rows > 0):
                                    while ($row = $students->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['student_name']; ?></td>
                                            <td class="font-monospace text-muted"><?php echo $row['school_register_no']; ?></td>
                                            <td class="text-center">
                                                <input type="number" step="0.01" min="0" max="<?php echo $current_max_mark; ?>"
                                                    name="marks[<?php echo $row['enrollment_id']; ?>]"
                                                    value="<?php echo $row['mark_obtained']; ?>" class="input-mark" placeholder="-">
                                            </td>
                                            <td class="text-center fw-bold"><?php echo $row['grade']; ?></td>
                                        </tr>
                                    <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div id="importBox"
    style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2); z-index:1000; width:400px;">
    <div class="d-flex justify-content-between mb-3">
        <h5 class="fw-bold m-0">Import Marks</h5>
        <button onclick="document.getElementById('importBox').style.display='none'" class="btn-close"></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
        <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
        <input type="hidden" name="exam_type" value="<?php echo $sel_exam_id; ?>">
        <input type="file" name="csv_file" class="form-control mb-3" accept=".csv" required>
        <button type="submit" name="import_marks" class="btn btn-primary w-100">Upload</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>