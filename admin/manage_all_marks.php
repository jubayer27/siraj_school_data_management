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

// ==========================================
// 2. EXPORT LOGIC (CSV Download)
// ==========================================
if (isset($_POST['export_csv'])) {
    $cid = $_POST['class_id'];
    $sid = $_POST['subject_id'];
    $etype = $_POST['exam_type'];

    // Fetch Data
    $sql = "SELECT st.school_register_no, st.student_name, sm.mark_obtained, sm.grade 
            FROM students st 
            JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
            LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = '$etype'
            WHERE sse.subject_id = $sid AND sse.class_id = $cid
            ORDER BY st.student_name ASC";
    $rows = $conn->query($sql);

    // Set Headers
    $filename = "Marks_Class" . $cid . "_Sub" . $sid . "_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Register No', 'Student Name', 'Mark', 'Grade'));

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
    $etype = $_POST['exam_type'];
    $filename = $_FILES['csv_file']['tmp_name'];

    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($filename, "r");
        $count = 0;
        fgetcsv($file); // Skip header

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            // CSV: [0]RegNo, [1]Name, [2]Mark
            $reg_no = $conn->real_escape_string(trim($data[0]));

            // Validate Mark
            if (!isset($data[2]) || $data[2] === "")
                continue;
            $mark_val = floatval($data[2]);

            // Calculate Grade (Standard Scale)
            $g = 'F';
            if ($mark_val >= 80)
                $g = 'A';
            elseif ($mark_val >= 60)
                $g = 'B';
            elseif ($mark_val >= 40)
                $g = 'C';

            // Find Enrollment
            $find_stu = $conn->query("SELECT sse.enrollment_id 
                                      FROM students st 
                                      JOIN student_subject_enrollment sse ON st.student_id = sse.student_id 
                                      WHERE st.school_register_no = '$reg_no' 
                                      AND sse.subject_id = $sid 
                                      AND sse.class_id = $cid");

            if ($find_stu->num_rows > 0) {
                $eid = $find_stu->fetch_assoc()['enrollment_id'];

                // Update or Insert Mark
                $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_type = '$etype'");
                if ($chk->num_rows > 0) {
                    $upd = $conn->prepare("UPDATE student_marks SET mark_obtained=?, grade=? WHERE enrollment_id=? AND exam_type=?");
                    $upd->bind_param("dsis", $mark_val, $g, $eid, $etype);
                    $upd->execute();
                } else {
                    $ins = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_type, mark_obtained, max_mark, grade) VALUES (?, ?, ?, 100, ?)");
                    $ins->bind_param("isds", $eid, $etype, $mark_val, $g);
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
    $etype = $_POST['exam_type'];
    $count = 0;

    if (isset($_POST['marks'])) {
        foreach ($_POST['marks'] as $eid => $val) {
            if ($val === "")
                continue;

            $val = floatval($val);
            // Grade Logic
            $g = ($val >= 80) ? 'A' : (($val >= 60) ? 'B' : (($val >= 40) ? 'C' : 'F'));

            $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_type = '$etype'");
            if ($chk->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE student_marks SET mark_obtained=?, grade=? WHERE enrollment_id=? AND exam_type=?");
                $stmt->bind_param("dsis", $val, $g, $eid, $etype);
            } else {
                $stmt = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_type, mark_obtained, max_mark, grade) VALUES (?, ?, ?, 100, ?)");
                $stmt->bind_param("isds", $eid, $etype, $val, $g);
            }
            $stmt->execute();
            $count++;
        }
        $msg = "Manual update successful. Saved $count marks.";
        $msg_type = "success";
    }
}

// ==========================================
// 5. FETCH DATA FOR VIEW
// ==========================================
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");

$sel_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$sel_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$sel_exam = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'Midterm';

$subjects = [];
if ($sel_class) {
    // Fetch subjects linked to this class
    $sub_q = $conn->query("SELECT * FROM subjects WHERE class_id = $sel_class ORDER BY subject_name");
    while ($s = $sub_q->fetch_assoc())
        $subjects[] = $s;
}

$students = null;
$stats = ['avg' => 0, 'pass' => 0, 'fail' => 0, 'max' => 0, 'grade_counts' => ['A' => 0, 'B' => 0, 'C' => 0, 'F' => 0]];

if ($sel_class && $sel_subject) {
    $sql = "SELECT st.student_id, st.student_name, st.school_register_no, st.photo, sse.enrollment_id, sm.mark_id, sm.mark_obtained, sm.grade
            FROM students st
            JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
            LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = '$sel_exam'
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
            if ($m >= 40)
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
    if ($count_marks > 0)
        $stats['avg'] = round($total_marks / $count_marks, 2);
    $students->data_seek(0);
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    /* Global Layout Fixes */
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
        padding: 30px !important;
        display: block !important;
    }

    /* Header */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .dashboard-header h2 {
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }

    .dashboard-header p {
        color: #7f8c8d;
        margin: 0;
    }

    /* Filter Card */
    .filter-card {
        padding: 20px;
        border-top: 4px solid #FFD700;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        margin-bottom: 25px;
        background: white;
    }

    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }

    .input-group {
        flex: 1;
    }

    .input-group label {
        font-weight: 700;
        color: #DAA520;
        display: block;
        margin-bottom: 5px;
        font-size: 0.8rem;
        text-transform: uppercase;
    }

    /* Stats Grid */
    .grid-2-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
    }

    .stats-grid-mini {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        text-align: center;
        border-radius: 12px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .stat-val {
        font-size: 2rem;
        font-weight: 800;
        color: #333;
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #999;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 1px;
    }

    /* Chart Bars */
    .viz-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        height: 100%;
    }

    .bar-group {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
    }

    .bar-label {
        width: 30px;
        font-weight: 700;
        color: #555;
    }

    .bar-track {
        flex: 1;
        background: #f0f0f0;
        height: 12px;
        border-radius: 6px;
        margin: 0 15px;
        overflow: hidden;
    }

    .bar-fill {
        height: 100%;
        border-radius: 6px;
        transition: width 0.5s ease;
    }

    .bar-count {
        font-size: 0.85rem;
        color: #777;
        width: 20px;
        text-align: right;
    }

    /* Marks Table */
    .table-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .card-header-row {
        padding: 20px 25px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .marks-table {
        width: 100%;
        border-collapse: collapse;
    }

    .marks-table th {
        background: #fafafa;
        padding: 15px 25px;
        text-align: left;
        color: #555;
        text-transform: uppercase;
        font-size: 0.8rem;
        font-weight: 700;
        border-bottom: 2px solid #eee;
    }

    .marks-table td {
        padding: 12px 25px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .marks-table tr:hover {
        background-color: #fffcf5;
    }

    .mark-input {
        width: 80px;
        padding: 8px 10px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-weight: 700;
        color: #333;
        transition: all 0.2s;
    }

    .mark-input:focus {
        border-color: #FFD700;
        background: #fff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    }

    .avatar-circle {
        width: 35px;
        height: 35px;
        background: #DAA520;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        margin-right: 15px;
    }

    .stu-name {
        font-weight: 600;
        color: #2c3e50;
    }

    /* Status Pills */
    .status-pill {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .status-pill.pass {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-pill.fail {
        background: #ffebee;
        color: #c62828;
    }

    .status-pill.pending {
        background: #f5f5f5;
        color: #999;
    }

    /* Import Box */
    #importBox {
        background: #f9f9f9;
        border: 2px dashed #ccc;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 25px;
    }

    /* Responsive */
    @media(max-width: 992px) {
        .main-content {
            width: 100% !important;
            margin-left: 0 !important;
        }

        .grid-2-col {
            grid-template-columns: 1fr;
        }

        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h2>Marks Control Center</h2>
                <p>Advanced grading, analytics, and data management.</p>
            </div>

            <?php if ($sel_class && $sel_subject): ?>
                <div style="display:flex; gap:10px;">
                    <button onclick="document.getElementById('importBox').style.display='block'"
                        class="btn btn-outline-primary shadow-sm">
                        <i class="fas fa-file-upload me-2"></i> Import
                    </button>

                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">
                        <button type="submit" name="export_csv" class="btn btn-success shadow-sm fw-bold">
                            <i class="fas fa-file-excel me-2"></i> Export
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($msg): ?>
            <div
                class="alert alert-<?php echo ($msg_type == 'success') ? 'success' : 'danger'; ?> d-flex align-items-center mb-4 shadow-sm">
                <i
                    class="fas fa-<?php echo ($msg_type == 'success') ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="card filter-card">
            <form method="GET" class="filter-form">
                <div class="input-group">
                    <label>1. Class Context</label>
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Select Class --</option>
                        <?php
                        $classes->data_seek(0);
                        while ($c = $classes->fetch_assoc()):
                            ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo ($sel_class == $c['class_id']) ? 'selected' : ''; ?>>
                                <?php echo $c['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label>2. Subject Context</label>
                    <select name="subject_id" class="form-select" onchange="this.form.submit()" <?php if (!$sel_class)
                        echo 'disabled'; ?>>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['subject_id']; ?>" <?php echo ($sel_subject == $s['subject_id']) ? 'selected' : ''; ?>>
                                <?php echo $s['subject_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label>3. Exam Term</label>
                    <select name="exam_type" class="form-select" onchange="this.form.submit()">
                        <option value="Midterm" <?php echo ($sel_exam == 'Midterm') ? 'selected' : ''; ?>>Midterm</option>
                        <option value="Final" <?php echo ($sel_exam == 'Final') ? 'selected' : ''; ?>>Final</option>
                    </select>
                </div>
            </form>
        </div>

        <div id="importBox" style="display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; mb-3">
                <h5 class="fw-bold m-0"><i class="fas fa-cloud-upload-alt me-2 text-primary"></i> Bulk Import Marks</h5>
                <button onclick="document.getElementById('importBox').style.display='none'" class="btn-close"></button>
            </div>
            <p class="text-muted small mb-3">Upload CSV with columns: <strong>Register No</strong>, <strong>Name
                    (Optional)</strong>, <strong>Mark</strong>.</p>

            <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">

                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                <button type="submit" name="import_marks" class="btn btn-primary fw-bold px-4">Upload</button>
            </form>
        </div>

        <?php if ($students): ?>

            <div class="grid-2-col">
                <div class="stats-grid-mini">
                    <div class="stat-card">
                        <div class="stat-val"><?php echo $stats['avg']; ?></div>
                        <div class="stat-label">Average Score</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val text-success"><?php echo $stats['pass']; ?></div>
                        <div class="stat-label">Passed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val text-danger"><?php echo $stats['fail']; ?></div>
                        <div class="stat-label">Failed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val text-warning"><?php echo $stats['max']; ?></div>
                        <div class="stat-label">Highest Mark</div>
                    </div>
                </div>

                <div class="viz-card">
                    <h5 class="fw-bold mb-3">Grade Distribution</h5>
                    <div class="chart-container">
                        <?php
                        $total_graded = $stats['pass'] + $stats['fail'];
                        if ($total_graded > 0):
                            foreach (['A' => '#27ae60', 'B' => '#2ecc71', 'C' => '#f1c40f', 'F' => '#e74c3c'] as $g => $col):
                                $pct = ($stats['grade_counts'][$g] / $total_graded) * 100;
                                if ($pct > 0):
                                    ?>
                                    <div class="bar-group">
                                        <div class="bar-label"><?php echo $g; ?></div>
                                        <div class="bar-track">
                                            <div class="bar-fill" style="width:<?php echo $pct; ?>%; background:<?php echo $col; ?>;">
                                            </div>
                                        </div>
                                        <div class="bar-count"><?php echo $stats['grade_counts'][$g]; ?></div>
                                    </div>
                                <?php endif; endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">

                <div class="card table-card">
                    <div class="card-header-row">
                        <h5 class="m-0 fw-bold text-dark"><i class="fas fa-list me-2 text-warning"></i> Student Marks Roster
                        </h5>
                        <button type="submit" name="save_changes" class="btn btn-primary fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Register No</th>
                                    <th>Score Input</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students->num_rows > 0): ?>
                                    <?php while ($row = $students->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle">
                                                        <?php echo strtoupper(substr($row['student_name'], 0, 1)); ?>
                                                    </div>
                                                    <span class="stu-name"><?php echo $row['student_name']; ?></span>
                                                </div>
                                            </td>
                                            <td class="font-monospace text-muted"><?php echo $row['school_register_no']; ?></td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="100"
                                                    name="marks[<?php echo $row['enrollment_id']; ?>]"
                                                    value="<?php echo $row['mark_obtained']; ?>" class="mark-input" placeholder="-">
                                            </td>
                                            <td>
                                                <?php if ($row['grade']): ?>
                                                    <span
                                                        class="status-pill <?php echo $row['mark_obtained'] < 40 ? 'fail' : 'pass'; ?>">
                                                        <?php echo $row['grade']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-pill pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">No students found in this class.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

        <?php else: ?>
            <div class="text-center py-5 bg-white rounded shadow-sm border border-dashed">
                <i class="fas fa-filter fa-3x text-warning opacity-50 mb-3"></i>
                <h4 class="text-muted">Ready to Manage Marks</h4>
                <p class="text-secondary">Please select a Class, Subject, and Exam Term above.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>