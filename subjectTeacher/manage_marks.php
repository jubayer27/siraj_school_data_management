<?php
session_start();
include '../config/db.php';

// 1. AUTHENTICATION
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['subject_teacher', 'admin', 'class_teacher'])) {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$alert_msg = "";
$alert_type = "";

// --- HELPER: Grade Calculator (Based on Ratio) ---
function calculateGrade($obtained, $total)
{
    if ($total <= 0)
        return 'F';

    // Calculate Percentage
    $percentage = ($obtained / $total) * 100;

    // Standard Malaysian/Common Grading Scale (Adjust as needed)
    if ($percentage >= 80)
        return 'A'; // 80-100%
    if ($percentage >= 60)
        return 'B'; // 60-79%
    if ($percentage >= 40)
        return 'C'; // 40-59%
    if ($percentage >= 35)
        return 'D'; // 35-39%
    if ($percentage >= 1)
        return 'E'; // 1-34%
    return 'F'; // 0 or Absent
}

// Helper to check subject ownership
function is_authorized($conn, $sid, $tid)
{
    if ($_SESSION['role'] == 'admin')
        return true;
    $q = $conn->query("SELECT 1 FROM subject_teachers WHERE subject_id = $sid AND teacher_id = $tid");
    return ($q->num_rows > 0);
}

// ---------------------------------------------------------
// 0. FETCH ACTIVE EXAMS
// ---------------------------------------------------------
$active_exams = [];
$exam_query = $conn->query("SELECT * FROM exam_types WHERE status = 'active' ORDER BY created_at DESC");
while ($row = $exam_query->fetch_assoc()) {
    $active_exams[] = $row;
}

// ---------------------------------------------------------
// 1. GET MAX MARKS FOR SELECTED EXAM
// ---------------------------------------------------------
$current_max_mark = 100; // Default
$sel_exam_name = isset($_REQUEST['exam_type']) ? $_REQUEST['exam_type'] : '';

if ($sel_exam_name) {
    // Safe fetch of max mark based on name
    $ename_safe = $conn->real_escape_string($sel_exam_name);
    $mq = $conn->query("SELECT max_marks FROM exam_types WHERE exam_name = '$ename_safe' LIMIT 1");
    if ($mq && $mq->num_rows > 0) {
        $current_max_mark = floatval($mq->fetch_assoc()['max_marks']);
    }
}

// ---------------------------------------------------------
// 2. EXPORT LOGIC
// ---------------------------------------------------------
if (isset($_POST['export_csv'])) {
    $sid = intval($_POST['subject_id']);

    if (is_authorized($conn, $sid, $teacher_id)) {
        $sub_info = $conn->query("SELECT subject_name, subject_code FROM subjects WHERE subject_id = $sid")->fetch_assoc();

        $sql = "SELECT st.school_register_no, st.student_name, sm.mark_obtained 
                FROM students st 
                JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
                LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = '$sel_exam_name'
                WHERE sse.subject_id = $sid
                ORDER BY st.student_name ASC";
        $rows = $conn->query($sql);

        $filename = $sub_info['subject_code'] . "_" . str_replace(' ', '', $sel_exam_name) . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Register No', 'Student Name', "Mark (Max: $current_max_mark)"));

        while ($row = $rows->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
}

include 'includes/header.php';

// ---------------------------------------------------------
// 3. IMPORT LOGIC
// ---------------------------------------------------------
if (isset($_POST['import_marks']) && isset($_FILES['csv_file'])) {
    $sid = intval($_POST['subject_id']);
    $filename = $_FILES['csv_file']['tmp_name'];

    if (is_authorized($conn, $sid, $teacher_id) && $_FILES['csv_file']['size'] > 0) {
        $file = fopen($filename, "r");
        $count = 0;
        fgetcsv($file); // Skip Header

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $reg_no = trim($data[0]);

            if (!isset($data[2]) || $data[2] === "")
                continue;

            $mark_val = floatval($data[2]);

            // Validate Max Mark
            if ($mark_val > $current_max_mark)
                $mark_val = $current_max_mark; // Cap it

            // Calculate Grade based on Ratio
            $g = calculateGrade($mark_val, $current_max_mark);

            $find_stu = $conn->query("SELECT sse.enrollment_id 
                                      FROM students st 
                                      JOIN student_subject_enrollment sse ON st.student_id = sse.student_id 
                                      WHERE st.school_register_no = '$reg_no' AND sse.subject_id = $sid");

            if ($find_stu->num_rows > 0) {
                $eid = $find_stu->fetch_assoc()['enrollment_id'];

                $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_type = '$sel_exam_name'");
                if ($chk->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE student_marks SET mark_obtained=?, max_mark=?, grade=? WHERE enrollment_id=? AND exam_type=?");
                    $stmt->bind_param("ddsis", $mark_val, $current_max_mark, $g, $eid, $sel_exam_name);
                } else {
                    $stmt = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_type, mark_obtained, max_mark, grade) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("isdds", $eid, $sel_exam_name, $mark_val, $current_max_mark, $g);
                }
                $stmt->execute();
                $count++;
            }
        }
        fclose($file);
        $alert_msg = "Import Successful! Updated $count marks.";
        $alert_type = "success";
    } else {
        $alert_msg = "Error: Invalid file or unauthorized.";
        $alert_type = "error";
    }
}

// ---------------------------------------------------------
// 4. MANUAL SAVE LOGIC
// ---------------------------------------------------------
if (isset($_POST['save_marks'])) {
    $sid = intval($_POST['subject_id']);
    $count = 0;

    if (isset($_POST['marks'])) {
        foreach ($_POST['marks'] as $eid => $val) {
            if ($val === "")
                continue;

            $val = floatval($val);

            // Validate Max (Backend Check)
            if ($val > $current_max_mark)
                $val = $current_max_mark;

            // Dynamic Grade Calculation
            $g = calculateGrade($val, $current_max_mark);

            $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_type = '$sel_exam_name'");
            if ($chk->num_rows > 0) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE student_marks SET mark_obtained=?, max_mark=?, grade=? WHERE enrollment_id=? AND exam_type=?");
                $stmt->bind_param("ddsis", $val, $current_max_mark, $g, $eid, $sel_exam_name);
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_type, mark_obtained, max_mark, grade) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isdds", $eid, $sel_exam_name, $val, $current_max_mark, $g);
            }
            $stmt->execute();
            $count++;
        }
        $alert_msg = "Saved $count marks successfully. (Based on Max: $current_max_mark)";
        $alert_type = "success";
    }
}

// 5. FETCH DATA FOR VIEW
if ($_SESSION['role'] == 'admin') {
    $sub_query = "SELECT s.subject_id, s.subject_name, c.class_name 
                  FROM subjects s 
                  JOIN classes c ON s.class_id = c.class_id 
                  ORDER BY c.class_name, s.subject_name";
} else {
    $sub_query = "SELECT s.subject_id, s.subject_name, c.class_name 
                  FROM subjects s 
                  JOIN subject_teachers st ON s.subject_id = st.subject_id
                  JOIN classes c ON s.class_id = c.class_id 
                  WHERE st.teacher_id = $teacher_id 
                  ORDER BY c.class_name, s.subject_name";
}
$my_subjects = $conn->query($sub_query);

$sel_sub = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : '';

// Default to first active exam
if (empty($sel_exam_name) && !empty($active_exams)) {
    $sel_exam_name = $active_exams[0]['exam_name'];
    // Update Max Mark for the default exam
    $current_max_mark = floatval($active_exams[0]['max_marks']);
}

$students = null;
$stats = ['total' => 0, 'graded' => 0, 'avg' => 0];

if ($sel_sub && $sel_exam_name) {
    if (is_authorized($conn, $sel_sub, $teacher_id)) {
        $sql = "SELECT st.student_id, st.student_name, st.school_register_no, st.photo, sse.enrollment_id, sm.mark_obtained, sm.grade
                FROM students st
                JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
                LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = '$sel_exam_name'
                WHERE sse.subject_id = $sel_sub
                ORDER BY st.student_name ASC";
        $students = $conn->query($sql);

        if ($students && $students->num_rows > 0) {
            $stats['total'] = $students->num_rows;
            $total_score = 0;
            while ($row = $students->fetch_assoc()) {
                if ($row['mark_obtained'] !== null) {
                    $stats['graded']++;
                    $total_score += $row['mark_obtained'];
                }
            }
            // Average calculation (converted to percentage for consistency)
            if ($stats['graded'] > 0) {
                $avg_raw = $total_score / $stats['graded'];
                $stats['avg'] = round(($avg_raw / $current_max_mark) * 100, 1) . "%";
            }
            $students->data_seek(0);
        }
    } else {
        $alert_msg = "Access Denied.";
        $alert_type = "error";
    }
}
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content full-width">
        <div class="dashboard-header">
            <div>
                <h1>Marks Management</h1>
                <p>Select subject and exam type to manage grading.</p>
            </div>

            <?php if ($sel_sub): ?>
                <div class="action-buttons">
                    <button onclick="document.getElementById('importBox').style.display='block'" class="btn btn-secondary">
                        <i class="fas fa-file-upload"></i> Import CSV
                    </button>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="subject_id" value="<?php echo $sel_sub; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo htmlspecialchars($sel_exam_name); ?>">
                        <button type="submit" name="export_csv" class="btn btn-primary" style="background:#27ae60;">
                            <i class="fas fa-file-download"></i> Export CSV
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($alert_msg): ?>
            <div class="alert-box <?php echo $alert_type; ?>"><?php echo $alert_msg; ?></div>
        <?php endif; ?>

        <div class="card filter-card">
            <div class="card-header-small">
                <i class="fas fa-filter"></i> Grading Context
            </div>
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Select Subject</label>
                    <div class="select-wrapper">
                        <i class="fas fa-book"></i>
                        <select name="subject_id" onchange="this.form.submit()">
                            <option value="">-- Choose Subject --</option>
                            <?php while ($s = $my_subjects->fetch_assoc()): ?>
                                <option value="<?php echo $s['subject_id']; ?>" <?php echo ($sel_sub == $s['subject_id']) ? 'selected' : ''; ?>>
                                    <?php echo $s['subject_name']; ?> &bull; <?php echo $s['class_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <i class="fas fa-chevron-down arrow"></i>
                    </div>
                </div>

                <div class="filter-group">
                    <label>Exam Term</label>
                    <div class="select-wrapper">
                        <i class="fas fa-calendar-alt"></i>
                        <select name="exam_type" onchange="this.form.submit()">
                            <?php if (empty($active_exams)): ?>
                                <option value="">No Active Exams</option>
                            <?php else: ?>
                                <?php foreach ($active_exams as $ex): ?>
                                    <option value="<?php echo htmlspecialchars($ex['exam_name']); ?>" <?php echo ($sel_exam_name == $ex['exam_name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ex['exam_name']); ?> (Max: <?php echo $ex['max_marks']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <i class="fas fa-chevron-down arrow"></i>
                    </div>
                </div>
            </form>
        </div>

        <div id="importBox" class="card import-box" style="display:none;">
            <div class="modal-header">
                <h3><i class="fas fa-cloud-upload-alt"></i> Upload Marks</h3>
                <button onclick="document.getElementById('importBox').style.display='none'">&times;</button>
            </div>
            <p>Upload a CSV file with columns: <strong>Register No</strong>, <strong>Name</strong>,
                <strong>Mark</strong>.
            </p>
            <p class="small text-danger">Note: Marks greater than <strong><?php echo $current_max_mark; ?></strong> will
                be capped.</p>
            <form method="POST" enctype="multipart/form-data" class="import-form">
                <input type="hidden" name="subject_id" value="<?php echo $sel_sub; ?>">
                <input type="hidden" name="exam_type" value="<?php echo htmlspecialchars($sel_exam_name); ?>">
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit" name="import_marks" class="btn btn-primary">Process Upload</button>
            </form>
        </div>

        <?php if ($sel_sub && $students): ?>

            <div class="stats-grid">
                <div class="card stat-card">
                    <div class="stat-val"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="card stat-card">
                    <div class="stat-val text-green"><?php echo $stats['graded']; ?></div>
                    <div class="stat-label">Graded</div>
                </div>
                <div class="card stat-card">
                    <div class="stat-val text-gold"><?php echo $stats['avg']; ?></div>
                    <div class="stat-label">Avg %</div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="subject_id" value="<?php echo $sel_sub; ?>">
                <input type="hidden" name="exam_type" value="<?php echo htmlspecialchars($sel_exam_name); ?>">

                <div class="card table-card">
                    <div class="card-header-row">
                        <div style="display:flex; flex-direction:column;">
                            <h3>Student Roster</h3>
                            <span style="font-size:0.85rem; color:#666;">
                                Grading Scale: <strong>0 - <?php echo $current_max_mark; ?></strong>
                            </span>
                        </div>
                        <button type="submit" name="save_marks" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Marks
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Register No</th>
                                    <th>Mark (Max: <?php echo $current_max_mark; ?>)</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students->num_rows > 0):
                                    while ($row = $students->fetch_assoc()):
                                        $has_mark = ($row['mark_obtained'] !== null);
                                        ?>
                                        <tr class="<?php echo $has_mark ? 'row-saved' : ''; ?>">
                                            <td>
                                                <div class="stu-info">
                                                    <?php $img = $row['photo'] ? "../uploads/" . $row['photo'] : "https://ui-avatars.com/api/?name=" . $row['student_name'] . "&background=f0f0f0&color=333"; ?>
                                                    <img src="<?php echo $img; ?>" class="stu-avatar">
                                                    <span class="stu-name"><?php echo $row['student_name']; ?></span>
                                                </div>
                                            </td>
                                            <td class="mono"><?php echo $row['school_register_no']; ?></td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="<?php echo $current_max_mark; ?>"
                                                    name="marks[<?php echo $row['enrollment_id']; ?>]"
                                                    value="<?php echo $row['mark_obtained']; ?>" class="mark-input" placeholder="-">
                                            </td>
                                            <td style="font-weight:bold; color:#555;"><?php echo $row['grade']; ?></td>
                                            <td>
                                                <?php if ($has_mark): ?>
                                                    <span class="status-badge success"><i class="fas fa-check"></i> Saved</span>
                                                <?php else: ?>
                                                    <span class="status-badge pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">No students enrolled in this subject.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

        <?php else: ?>
            <div class="empty-dashboard">
                <i class="fas fa-tasks"></i>
                <h2>No Subject Selected</h2>
                <p>Please select a subject from the options above.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
    /* Add this specific style for the input warning on overflow */
    .mark-input:invalid {
        border-color: red;
        background-color: #ffe6e6;
    }

    /* ... keep your existing CSS styles for filters, cards, tables ... */
    .filter-card {
        padding: 0;
        border: 1px solid #e0e0e0;
        overflow: hidden;
        border-top: 4px solid #DAA520;
    }

    .card-header-small {
        background: #fdfdfd;
        padding: 10px 20px;
        border-bottom: 1px solid #eee;
        font-size: 0.85rem;
        font-weight: 700;
        color: #DAA520;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        padding: 25px;
        background: #fff;
    }

    .filter-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
        font-size: 0.9rem;
    }

    .select-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .select-wrapper i {
        position: absolute;
        left: 15px;
        color: #aaa;
        pointer-events: none;
    }

    .select-wrapper i.arrow {
        left: auto;
        right: 15px;
        font-size: 0.8rem;
    }

    .select-wrapper select {
        width: 100%;
        padding: 12px 40px;
        font-size: 1rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        appearance: none;
        background: #fff;
        color: #333;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s;
    }

    .select-wrapper select:hover {
        border-color: #DAA520;
    }

    .select-wrapper select:focus {
        border-color: #DAA520;
        box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.1);
        outline: none;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }

    .stat-card {
        text-align: center;
        padding: 25px;
    }

    .stat-val {
        font-size: 2.2rem;
        font-weight: 700;
        color: #333;
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .text-green {
        color: #27ae60;
    }

    .text-gold {
        color: #f39c12;
    }

    .table-card {
        padding: 0;
        overflow: hidden;
    }

    .card-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 25px;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
    }

    .marks-table th {
        background: #f9f9f9;
        padding: 15px 20px;
        text-align: left;
        font-weight: 600;
        color: #555;
        text-transform: uppercase;
        font-size: 0.8rem;
    }

    .marks-table td {
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .row-saved {
        background: #fafffb;
    }

    .stu-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .stu-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #eee;
    }

    .mark-input {
        width: 80px;
        padding: 10px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-weight: bold;
        font-size: 1rem;
        color: #333;
        transition: 0.2s;
    }

    .mark-input:focus {
        border-color: #DAA520;
        background: #fffcf5;
        outline: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
    }

    .status-badge.success {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-badge.pending {
        background: #f5f5f5;
        color: #999;
    }

    .import-box {
        border: 2px dashed #DAA520;
        background: #fffcf5;
        padding: 20px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .modal-header button {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #888;
    }

    .import-form {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-top: 15px;
    }

    .import-form input[type="file"] {
        border: 1px solid #ddd;
        padding: 8px;
        border-radius: 4px;
        background: #fff;
        flex: 1;
    }

    .alert-box {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 6px;
        font-weight: 500;
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

    .empty-dashboard {
        text-align: center;
        padding: 80px;
        color: #aaa;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #eee;
    }

    .empty-dashboard i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.3;
    }

    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }
</style>
<?php include 'includes/footer.php'; ?>