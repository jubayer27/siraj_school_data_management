<?php
session_start();
include '../config/db.php';

// 1. SECURITY & AUTH
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// ==========================================
// 2. EXPORT LOGIC (Must be before HTML)
// ==========================================
if (isset($_POST['export_csv'])) {
    $cid = $_POST['class_id'];
    $sid = $_POST['subject_id'];
    $etype = $_POST['exam_type'];

    // Fetch Data matching the current view
    $sql = "SELECT st.school_register_no, st.student_name, sm.mark_obtained 
            FROM students st 
            JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
            LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = '$etype'
            WHERE sse.subject_id = $sid AND sse.class_id = $cid
            ORDER BY st.school_register_no ASC";
    $rows = $conn->query($sql);

    // Set Headers for Download
    $filename = "Marks_Class" . $cid . "_Sub" . $sid . "_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Header Row matches Import expectation
    fputcsv($output, array('Register No', 'Student Name', 'Mark (0-100)'));

    while ($row = $rows->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

include 'includes/header.php';

// ==========================================
// 3. IMPORT LOGIC
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

        // Skip header row
        fgetcsv($file);

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            // CSV Structure: [0] => Reg No, [1] => Name (Ignored), [2] => Mark
            $reg_no = $data[0];
            $mark_val = floatval($data[2]);

            if ($data[2] === "")
                continue; // Skip empty marks

            // Calculate Grade
            $g = 'F';
            if ($mark_val >= 80)
                $g = 'A';
            elseif ($mark_val >= 60)
                $g = 'B';
            elseif ($mark_val >= 40)
                $g = 'C';

            // Find Enrollment ID based on Register No + Subject
            // This ensures we only update the correct student for THIS subject
            $find_stu = $conn->query("SELECT sse.enrollment_id 
                                      FROM students st 
                                      JOIN student_subject_enrollment sse ON st.student_id = sse.student_id 
                                      WHERE st.school_register_no = '$reg_no' AND sse.subject_id = $sid AND sse.class_id = $cid");

            if ($find_stu->num_rows > 0) {
                $eid = $find_stu->fetch_assoc()['enrollment_id'];

                // Update or Insert
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

    foreach ($_POST['marks'] as $eid => $val) {
        if ($val === "")
            continue;
        $val = floatval($val);
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

// ==========================================
// 5. FETCH DATA FOR VIEW
// ==========================================
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");

$sel_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$sel_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$sel_exam = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'Midterm';

$subjects = [];
if ($sel_class) {
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

            // Grade Count
            $grade = $row['grade'];
            if (isset($stats['grade_counts'][$grade]))
                $stats['grade_counts'][$grade]++;
        }
    }
    if ($count_marks > 0)
        $stats['avg'] = round($total_marks / $count_marks, 2);
    $students->data_seek(0); // Reset pointer
}
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content full-width">
        <div class="dashboard-header">
            <div>
                <h1>Marks Control Center</h1>
                <p>Advanced grading, analytics, and data import/export.</p>
            </div>

            <?php if ($sel_class && $sel_subject): ?>
                <div style="display:flex; gap:10px;">
                    <button onclick="document.getElementById('importBox').style.display='block'" class="btn btn-primary"
                        style="background:#8e44ad;">
                        <i class="fas fa-file-upload"></i> Import Excel
                    </button>

                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">
                        <button type="submit" name="export_csv" class="btn btn-primary" style="background:#27ae60;">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($msg): ?>
            <div class="alert-box <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="card filter-card">
            <form method="GET" class="filter-form">
                <div class="input-group">
                    <label>1. Class Context</label>
                    <select name="class_id" onchange="this.form.submit()">
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
                    <select name="subject_id" onchange="this.form.submit()" <?php if (!$sel_class)
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
                    <select name="exam_type" onchange="this.form.submit()">
                        <option value="Midterm" <?php echo ($sel_exam == 'Midterm') ? 'selected' : ''; ?>>Midterm</option>
                        <option value="Final" <?php echo ($sel_exam == 'Final') ? 'selected' : ''; ?>>Final</option>
                    </select>
                </div>
            </form>
        </div>

        <div id="importBox" class="card" style="display:none; border:2px dashed #8e44ad; background:#fbf6ff;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; color:#8e44ad;">Bulk Import Marks</h3>
                <button onclick="document.getElementById('importBox').style.display='none'"
                    style="border:none; background:none; font-size:1.2rem; cursor:pointer;">&times;</button>
            </div>
            <p style="font-size:0.9rem; color:#666; margin:10px 0;">Upload a CSV file with columns: <strong>Register
                    No</strong>, <strong>Name (Optional)</strong>, <strong>Mark</strong>.</p>

            <form method="POST" enctype="multipart/form-data" style="display:flex; gap:15px; align-items:center;">
                <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">

                <input type="file" name="csv_file" accept=".csv" required
                    style="border:1px solid #ccc; padding:8px; border-radius:4px; background:#fff;">
                <button type="submit" name="import_marks" class="btn btn-primary" style="background:#8e44ad;">Upload &
                    Process</button>
            </form>
        </div>

        <?php if ($students): ?>

            <div class="grid-2-col">
                <div class="stats-grid-mini">
                    <div class="card stat-card">
                        <div class="stat-val"><?php echo $stats['avg']; ?></div>
                        <div class="stat-label">Class Avg</div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-val" style="color:#27ae60;"><?php echo $stats['pass']; ?></div>
                        <div class="stat-label">Passed</div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-val" style="color:#c0392b;"><?php echo $stats['fail']; ?></div>
                        <div class="stat-label">Failed</div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-val" style="color:#f39c12;"><?php echo $stats['max']; ?></div>
                        <div class="stat-label">Top Score</div>
                    </div>
                </div>

                <div class="card viz-card">
                    <h4 style="margin:0 0 15px 0;">Grade Distribution</h4>
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
                        <h3>Marks Entry Roster</h3>
                        <button type="submit" name="save_changes" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save All Changes
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Register No</th>
                                    <th>Input Mark</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students->num_rows > 0): ?>
                                    <?php while ($row = $students->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="stu-info">
                                                    <div class="avatar-circle">
                                                        <?php echo strtoupper(substr($row['student_name'], 0, 1)); ?>
                                                    </div>
                                                    <span class="stu-name"><?php echo $row['student_name']; ?></span>
                                                </div>
                                            </td>
                                            <td style="font-family:monospace;"><?php echo $row['school_register_no']; ?></td>
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
                                        <td colspan="4" style="text-align:center; padding:30px;">No students enrolled.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

        <?php else: ?>
            <div class="empty-state-box">
                <i class="fas fa-filter"></i>
                <h3>Ready to Manage Marks</h3>
                <p>Select a Class, Subject, and Exam Term from the filter bar above to begin.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
    /* Layout */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .filter-card {
        padding: 20px;
        border-top: 4px solid #DAA520;
    }

    .filter-form {
        display: flex;
        gap: 15px;
    }

    .input-group {
        flex: 1;
    }

    .input-group label {
        font-weight: 600;
        color: #DAA520;
        display: block;
        margin-bottom: 5px;
        font-size: 0.85rem;
    }

    /* Stats & Viz */
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
        padding: 20px;
        text-align: center;
    }

    .stat-val {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #888;
        text-transform: uppercase;
        margin-top: 5px;
        letter-spacing: 0.5px;
    }

    /* Chart Bars */
    .viz-card {
        padding: 20px;
    }

    .bar-group {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .bar-label {
        width: 30px;
        font-weight: bold;
        color: #555;
    }

    .bar-track {
        flex: 1;
        background: #f0f0f0;
        height: 10px;
        border-radius: 5px;
        margin: 0 10px;
        overflow: hidden;
    }

    .bar-fill {
        height: 100%;
        border-radius: 5px;
    }

    .bar-count {
        font-size: 0.85rem;
        color: #888;
        width: 20px;
        text-align: right;
    }

    /* Table */
    .table-card {
        padding: 0;
    }

    .card-header-row {
        padding: 15px 25px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .marks-table th {
        background: #f9f9f9;
        padding: 12px 20px;
        text-align: left;
        color: #555;
        text-transform: uppercase;
        font-size: 0.8rem;
    }

    .marks-table td {
        padding: 10px 20px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .mark-input {
        width: 80px;
        padding: 8px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-weight: bold;
    }

    .mark-input:focus {
        border-color: #DAA520;
        background: #fffcf0;
        outline: none;
    }

    .stu-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .avatar-circle {
        width: 32px;
        height: 32px;
        background: #DAA520;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .status-pill {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
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

    .empty-state-box {
        text-align: center;
        padding: 60px;
        color: #aaa;
        background: #fff;
        border-radius: 10px;
        border: 2px dashed #eee;
    }

    .empty-state-box i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #DAA520;
        opacity: 0.5;
    }

    .alert-box {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .alert-box.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-box.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    @media(max-width: 900px) {
        .filter-form {
            flex-direction: column;
        }

        .grid-2-col {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php include 'includes/footer.php'; ?>