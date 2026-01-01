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
    $etype = $_POST['exam_type']; // This is actually exam_id

    // Fetch Exam Name for filename
    $ename = "Exam";
    $eq = $conn->query("SELECT exam_name FROM exam_types WHERE exam_id = '$etype'");
    if ($eq->num_rows > 0)
        $ename = str_replace(' ', '', $eq->fetch_assoc()['exam_name']);

    // Fetch Data
    $sql = "SELECT st.school_register_no, st.student_name, sm.mark_obtained, sm.grade 
            FROM students st 
            JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
            LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_id = '$etype'
            WHERE sse.subject_id = $sid AND sse.class_id = $cid
            ORDER BY st.student_name ASC";
    $rows = $conn->query($sql);

    // Set Headers
    $filename = "Marks_Class" . $cid . "_Sub" . $sid . "_" . $ename . ".csv";
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
            $reg_no = $conn->real_escape_string(trim($data[0]));

            if (!isset($data[2]) || $data[2] === "")
                continue;
            $mark_val = floatval($data[2]);

            // Simple Grade Logic
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

                // Update/Insert using exam_id
                $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_id = '$etype'");
                if ($chk->num_rows > 0) {
                    $upd = $conn->prepare("UPDATE student_marks SET mark_obtained=?, grade=? WHERE enrollment_id=? AND exam_id=?");
                    $upd->bind_param("dsii", $mark_val, $g, $eid, $etype);
                    $upd->execute();
                } else {
                    $ins = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_id, mark_obtained, max_mark, grade) VALUES (?, ?, ?, 100, ?)");
                    $ins->bind_param("iids", $eid, $etype, $mark_val, $g);
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
    $count = 0;

    if (isset($_POST['marks'])) {
        foreach ($_POST['marks'] as $eid => $val) {
            if ($val === "")
                continue;

            $val = floatval($val);
            $g = ($val >= 80) ? 'A' : (($val >= 60) ? 'B' : (($val >= 40) ? 'C' : 'F'));

            $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_id = '$etype'");
            if ($chk->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE student_marks SET mark_obtained=?, grade=? WHERE enrollment_id=? AND exam_id=?");
                $stmt->bind_param("dsii", $val, $g, $eid, $etype);
            } else {
                $stmt = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_id, mark_obtained, max_mark, grade) VALUES (?, ?, ?, 100, ?)");
                $stmt->bind_param("iids", $eid, $etype, $val, $g);
            }
            $stmt->execute();
            $count++;
        }
        $msg = "Changes saved successfully. Updated $count records.";
        $msg_type = "success";
    }
}

// ==========================================
// 5. FETCH DATA
// ==========================================
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$exam_types = $conn->query("SELECT * FROM exam_types WHERE status='active' ORDER BY created_at DESC");

$sel_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$sel_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$sel_exam = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';

// Auto-select latest exam if not set
if (!$sel_exam && $exam_types->num_rows > 0) {
    $first_exam = $exam_types->fetch_assoc();
    $sel_exam = $first_exam['exam_id'];
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

if ($sel_class && $sel_subject && $sel_exam) {
    $sql = "SELECT st.student_id, st.student_name, st.school_register_no, st.photo, sse.enrollment_id, sm.mark_id, sm.mark_obtained, sm.grade
            FROM students st
            JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
            LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_id = '$sel_exam'
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
    /* 1. Global Reset & Body */
    body {
        background-color: #f0f2f5;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        overflow-x: hidden;
    }

    /* 2. Main Content Wrapper */
    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 40px !important;
        display: block !important;
    }

    /* 3. Header Section */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #2c3e50;
        letter-spacing: -0.5px;
        margin: 0;
    }

    .page-subtitle {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 0;
    }

    /* 4. Filter Card (Glassmorphism Effect) */
    .filter-card {
        background: #ffffff;
        border: 1px solid #eef2f7;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .filter-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(to bottom, #FFD700, #ffb900);
    }

    .form-label-custom {
        font-weight: 700;
        color: #555;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: block;
    }

    .form-select-custom {
        border: 2px solid #f0f2f5;
        border-radius: 8px;
        padding: 10px 15px;
        font-weight: 600;
        color: #444;
        transition: all 0.3s;
    }

    .form-select-custom:focus {
        border-color: #FFD700;
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
    }

    /* 5. Stats Cards */
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
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        text-align: center;
        border: 1px solid #f0f0f0;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 900;
        color: #2c3e50;
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #95a5a6;
    }

    /* 6. Grade Distribution (Viz Card) */
    .viz-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        height: 100%;
        border: 1px solid #f0f0f0;
    }

    .progress-group {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
    }

    .progress-label {
        font-weight: 800;
        width: 30px;
        color: #555;
    }

    .progress-track {
        flex: 1;
        height: 10px;
        background: #ecf0f1;
        border-radius: 5px;
        margin: 0 15px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        border-radius: 5px;
    }

    /* 7. Marks Table */
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .table-header {
        padding: 20px 30px;
        background: white;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .custom-table th {
        background: #f8f9fb;
        padding: 15px 30px;
        text-align: left;
        font-weight: 700;
        color: #7f8c8d;
        font-size: 0.8rem;
        text-transform: uppercase;
        border-bottom: 1px solid #eee;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .custom-table td {
        padding: 15px 30px;
        border-bottom: 1px solid #f9f9f9;
        vertical-align: middle;
        color: #34495e;
        font-weight: 500;
    }

    .custom-table tr:hover td {
        background-color: #fafafa;
    }

    .input-mark {
        width: 80px;
        padding: 8px;
        text-align: center;
        border: 2px solid #eee;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1rem;
        color: #2c3e50;
        transition: 0.2s;
    }

    .input-mark:focus {
        border-color: #3498db;
        outline: none;
    }

    /* 8. Status Badges */
    .badge-status {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .badge-pass {
        background: #e8f8f5;
        color: #27ae60;
    }

    .badge-fail {
        background: #fdedec;
        color: #e74c3c;
    }

    .badge-pending {
        background: #f4f6f7;
        color: #95a5a6;
    }

    /* 9. Buttons */
    .btn-gold {
        background: linear-gradient(135deg, #FFD700, #f39c12);
        border: none;
        color: #fff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        font-weight: 700;
        padding: 10px 25px;
        border-radius: 8px;
        transition: 0.3s;
    }

    .btn-gold:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        color: white;
    }

    /* 10. Import Box */
    #importBox {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 400px;
        z-index: 1000;
        border: 1px solid #eee;
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 999;
        backdrop-filter: blur(2px);
    }

    /* Responsive */
    @media (max-width: 992px) {
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 20px !important;
        }

        .stats-container {
            grid-template-columns: 1fr 1fr;
        }

        .filter-form {
            flex-direction: column;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <div class="page-header">
            <div>
                <h1 class="page-title">Marks Control Center</h1>
                <p class="page-subtitle">Manage student grades, analytics, and reports.</p>
            </div>

            <?php if ($sel_class && $sel_subject): ?>
                <div class="d-flex gap-3">
                    <button onclick="openImport()" class="btn btn-outline-secondary fw-bold px-4">
                        <i class="fas fa-file-import me-2"></i> Import CSV
                    </button>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">
                        <button type="submit" name="export_csv" class="btn btn-outline-success fw-bold px-4">
                            <i class="fas fa-file-export me-2"></i> Export CSV
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($msg): ?>
            <div
                class="alert alert-<?php echo ($msg_type == 'success') ? 'success' : 'danger'; ?> border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
                <i
                    class="fas fa-<?php echo ($msg_type == 'success') ? 'check-circle' : 'exclamation-circle'; ?> fa-lg me-3"></i>
                <div><?php echo $msg; ?></div>
            </div>
        <?php endif; ?>

        <div class="filter-card">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label-custom">1. Select Class</label>
                    <select name="class_id" class="form-select form-select-custom" onchange="this.form.submit()">
                        <option value="">-- Choose Class --</option>
                        <?php
                        $classes->data_seek(0);
                        while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo ($sel_class == $c['class_id']) ? 'selected' : ''; ?>>
                                <?php echo $c['class_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">2. Select Subject</label>
                    <select name="subject_id" class="form-select form-select-custom" onchange="this.form.submit()" <?php echo !$sel_class ? 'disabled' : ''; ?>>
                        <option value="">-- Choose Subject --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['subject_id']; ?>" <?php echo ($sel_subject == $s['subject_id']) ? 'selected' : ''; ?>>
                                <?php echo $s['subject_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">3. Exam Term</label>
                    <select name="exam_type" class="form-select form-select-custom" onchange="this.form.submit()">
                        <?php
                        if ($exam_types->num_rows > 0) {
                            $exam_types->data_seek(0);
                            while ($et = $exam_types->fetch_assoc()): ?>
                                <option value="<?php echo $et['exam_id']; ?>" <?php echo ($sel_exam == $et['exam_id']) ? 'selected' : ''; ?>>
                                    <?php echo $et['exam_name']; ?>
                                </option>
                            <?php endwhile;
                        } else {
                            echo "<option value=''>No Exams Created</option>";
                        }
                        ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($students): ?>

            <div class="row mb-4">
                <div class="col-lg-6">
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
                </div>

                <div class="col-lg-6">
                    <div class="viz-card">
                        <h6 class="fw-bold mb-3 text-secondary text-uppercase ls-1">Performance Distribution</h6>
                        <?php
                        $total_graded = $stats['pass'] + $stats['fail'];
                        if ($total_graded > 0):
                            $colors = ['A' => '#2ecc71', 'B' => '#3498db', 'C' => '#f1c40f', 'F' => '#e74c3c'];
                            foreach (['A', 'B', 'C', 'F'] as $g):
                                $pct = ($stats['grade_counts'][$g] / $total_graded) * 100;
                                ?>
                                <div class="progress-group">
                                    <div class="progress-label"><?php echo $g; ?></div>
                                    <div class="progress-track">
                                        <div class="progress-fill"
                                            style="width:<?php echo $pct; ?>%; background:<?php echo $colors[$g]; ?>;"></div>
                                    </div>
                                    <div class="text-muted small fw-bold" style="width:30px; text-align:right;">
                                        <?php echo $stats['grade_counts'][$g]; ?></div>
                                </div>
                            <?php endforeach; else: ?>
                            <div class="text-center text-muted py-4">No marks entered yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
                <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
                <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">

                <div class="table-container">
                    <div class="table-header">
                        <h5 class="fw-bold m-0 text-dark"><i class="fas fa-list-alt me-2 text-warning"></i> Student Marks
                            Roster</h5>
                        <button type="submit" name="save_changes" class="btn btn-gold shadow-sm">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>

                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Register No</th>
                                    <th class="text-center">Score Input</th>
                                    <th class="text-center">Grade</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students->num_rows > 0): ?>
                                    <?php while ($row = $students->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div
                                                        style="width:35px; height:35px; background:#eee; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#777; margin-right:12px;">
                                                        <?php echo substr($row['student_name'], 0, 1); ?>
                                                    </div>
                                                    <div><?php echo $row['student_name']; ?></div>
                                                </div>
                                            </td>
                                            <td class="font-monospace text-muted"><?php echo $row['school_register_no']; ?></td>
                                            <td class="text-center">
                                                <input type="number" step="0.01" min="0" max="100"
                                                    name="marks[<?php echo $row['enrollment_id']; ?>]"
                                                    value="<?php echo $row['mark_obtained']; ?>" class="input-mark" placeholder="-">
                                            </td>
                                            <td class="text-center">
                                                <?php if ($row['grade']): ?>
                                                    <span
                                                        class="badge-status <?php echo $row['mark_obtained'] >= 40 ? 'badge-pass' : 'badge-fail'; ?>">
                                                        <?php echo $row['grade']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="print_marksheet.php?student_id=<?php echo $row['student_id']; ?>&exam_id=<?php echo $sel_exam; ?>"
                                                    target="_blank" class="btn btn-sm btn-light border" title="Print Slip">
                                                    <i class="fas fa-print text-muted"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

        <?php else: ?>
            <div class="text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="120"
                    style="opacity:0.3; margin-bottom:20px;">
                <h4 class="text-muted fw-bold">No Data Loaded</h4>
                <p class="text-secondary">Please select a Class, Subject, and Exam Term above to manage marks.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<div id="modalOverlay" class="overlay" style="display:none;" onclick="closeImport()"></div>
<div id="importBox" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold m-0">Bulk Import Marks</h5>
        <button onclick="closeImport()" class="btn-close"></button>
    </div>
    <p class="small text-muted mb-3">Upload CSV with columns: <strong>Register No</strong>, <strong>Name</strong>,
        <strong>Mark</strong>.</p>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="class_id" value="<?php echo $sel_class; ?>">
        <input type="hidden" name="subject_id" value="<?php echo $sel_subject; ?>">
        <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">

        <input type="file" name="csv_file" class="form-control mb-3" accept=".csv" required>
        <button type="submit" name="import_marks" class="btn btn-primary w-100 fw-bold">Upload Data</button>
    </form>
</div>

<script>
    function openImport() {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('importBox').style.display = 'block';
    }
    function closeImport() {
        document.getElementById('modalOverlay').style.display = 'none';
        document.getElementById('importBox').style.display = 'none';
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>