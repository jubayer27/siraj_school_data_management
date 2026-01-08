<?php
session_start();
include '../config/db.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// 2. FETCH CLASS INFO
$class_q = $conn->query("SELECT class_id, class_name FROM classes WHERE class_teacher_id = $teacher_id");
$my_class = $class_q->fetch_assoc();
$class_id = $my_class ? $my_class['class_id'] : 0;
$class_name = $my_class ? $my_class['class_name'] : "No Class Assigned";

// 3. FETCH ACTIVE EXAMS
$exams_q = $conn->query("SELECT * FROM exam_types WHERE status='active' ORDER BY created_at DESC");
$exam_types = [];
while ($ex = $exams_q->fetch_assoc()) {
    $exam_types[] = $ex;
}

// 4. HANDLE SELECTION
$selected_exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : '';
$export_mode = isset($_GET['export']) ? $_GET['export'] : '';

$current_exam_name = "";
$current_max = 100;

if ($selected_exam_id) {
    foreach ($exam_types as $et) {
        if ($et['exam_id'] == $selected_exam_id) {
            $current_exam_name = $et['exam_name'];
            $current_max = floatval($et['max_marks']);
            break;
        }
    }
}

// 5. HELPER: GRADE CALCULATOR
function getGrade($score, $max)
{
    if ($score === null || $score === "")
        return "-";
    if ($max <= 0)
        return "-";
    $pct = ($score / $max) * 100;
    if ($pct >= 85)
        return 'A';
    if ($pct >= 70)
        return 'B';
    if ($pct >= 60)
        return 'C';
    if ($pct >= 50)
        return 'D';
    if ($pct >= 40)
        return 'E';
    return 'F';
}

// 6. FETCH DATA
$subjects = [];
$students = [];
$marks_map = [];

if ($class_id && $selected_exam_id) {
    // Subjects
    $sub_sql = "SELECT DISTINCT s.subject_id, s.subject_name, s.subject_code 
                FROM subjects s 
                JOIN student_subject_enrollment sse ON s.subject_id = sse.subject_id
                JOIN students st ON sse.student_id = st.student_id
                WHERE st.class_id = $class_id ORDER BY s.subject_name";
    $sub_res = $conn->query($sub_sql);
    while ($row = $sub_res->fetch_assoc()) {
        $subjects[] = $row;
    }

    // Students
    $stu_sql = "SELECT student_id, student_name, school_register_no 
                FROM students WHERE class_id = $class_id ORDER BY student_name";
    $stu_res = $conn->query($stu_sql);
    while ($row = $stu_res->fetch_assoc()) {
        $students[] = $row;
    }

    // Marks
    $mark_sql = "SELECT sse.student_id, sse.subject_id, sm.mark_obtained 
                 FROM student_marks sm
                 JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id
                 JOIN students st ON sse.student_id = st.student_id
                 WHERE st.class_id = $class_id AND sm.exam_type = '$current_exam_name'";
    $mark_res = $conn->query($mark_sql);
    while ($row = $mark_res->fetch_assoc()) {
        $marks_map[$row['student_id']][$row['subject_id']] = $row['mark_obtained'];
    }
}

// ==========================================
// 7. EXPORT LOGIC (FIXED: Uses CSV Format)
// ==========================================
if ($export_mode == 'excel' && $class_id && $selected_exam_id) {
    // Clear buffer to prevent HTML tags in file
    if (ob_get_length())
        ob_clean();

    $filename = "MasterSheet_" . str_replace(' ', '_', $class_name) . "_" . date('Y-m-d') . ".csv";

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 compatibility (Symbols/Arabic etc)
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Title Rows
    fputcsv($output, ["MASTER MARKSHEET: " . $class_name]);
    fputcsv($output, ["Exam: $current_exam_name", "Max Marks: $current_max"]);
    fputcsv($output, []); // Blank row

    // Construct Column Headers
    $headers = ['No', 'Reg No', 'Student Name'];
    foreach ($subjects as $sub) {
        $headers[] = $sub['subject_code']; // Subject Code as Header
    }
    $headers[] = 'TOTAL';
    $headers[] = 'AVG %';

    fputcsv($output, $headers);

    // Write Data Rows
    $i = 1;
    foreach ($students as $stu) {
        $sid = $stu['student_id'];
        $total_score = 0;
        $subject_count = 0;

        $row_data = [
            $i++,
            $stu['school_register_no'],
            $stu['student_name']
        ];

        foreach ($subjects as $sub) {
            $sub_id = $sub['subject_id'];
            $score = isset($marks_map[$sid][$sub_id]) ? $marks_map[$sid][$sub_id] : null;

            if ($score !== null) {
                $total_score += floatval($score);
                $subject_count++;
                $row_data[] = $score;
            } else {
                $row_data[] = "-";
            }
        }

        // Summary Calculations
        $row_data[] = ($subject_count > 0) ? $total_score : '-';

        $avg_display = "-";
        if ($subject_count > 0 && $current_max > 0) {
            $max_possible = $subject_count * $current_max;
            $avg = ($total_score / $max_possible) * 100;
            $avg_display = round($avg, 1) . "%";
        }
        $row_data[] = $avg_display;

        fputcsv($output, $row_data);
    }

    fclose($output);
    exit();
}

include 'includes/header.php';
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

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .master-table th {
        background-color: #343a40;
        color: white;
        vertical-align: middle;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
        z-index: 10;
        text-align: center;
    }

    .master-table td {
        vertical-align: middle;
        border-color: #f0f0f0;
    }

    .stu-col {
        position: sticky;
        left: 0;
        background-color: #fff;
        z-index: 5;
        border-right: 2px solid #dee2e6;
        min-width: 250px;
        text-align: left !important;
    }

    .master-table th:first-child {
        position: sticky;
        left: 0;
        z-index: 15;
        border-right: 2px solid #555;
    }

    .mark-cell {
        font-weight: bold;
        font-size: 0.95rem;
    }

    .grade-small {
        font-size: 0.7rem;
        color: #888;
        display: block;
        line-height: 1;
        margin-top: 2px;
    }

    .text-missing {
        color: #ccc;
        font-weight: normal;
    }

    @media print {

        .no-print,
        .sidebar {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            width: 100% !important;
            padding: 0 !important;
        }

        .container-fluid {
            padding: 10px !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #000 !important;
        }

        .master-table th {
            background-color: #ddd !important;
            color: #000 !important;
        }

        .stu-col {
            border-right: 1px solid #000 !important;
        }

        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    @media (max-width: 992px) {
        .main-content {
            width: 100% !important;
            margin-left: 0 !important;
        }
    }
</style>

<div class="wrapper">
    <div class="no-print"><?php include 'includes/sidebar.php'; ?></div>

    <div class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Master Marksheet</h2>
                    <p class="text-secondary mb-0">Consolidated results for <strong><?php echo $class_name; ?></strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($selected_exam_id): ?>
                        <a href="?exam_id=<?php echo $selected_exam_id; ?>&export=excel"
                            class="btn btn-success shadow-sm fw-bold">
                            <i class="fas fa-file-csv me-2"></i> Export Excel
                        </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-outline-dark shadow-sm fw-bold">
                        <i class="fas fa-print me-2"></i> Print Sheet
                    </button>
                </div>
            </div>

            <?php if (!$class_id): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center no-print">
                    <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                    <div><strong>No Class Assigned.</strong><br>You are not currently listed as a Class Teacher.</div>
                </div>
            <?php else: ?>

                <div class="card mb-4 no-print">
                    <div class="card-body py-3">
                        <form method="GET" class="row align-items-center">
                            <label class="col-auto fw-bold text-muted">Select Examination Term:</label>
                            <div class="col-md-4">
                                <select name="exam_id" class="form-select border-primary" onchange="this.form.submit()">
                                    <option value="">-- Choose Exam --</option>
                                    <?php foreach ($exam_types as $et): ?>
                                        <option value="<?php echo $et['exam_id']; ?>" <?php echo ($selected_exam_id == $et['exam_id']) ? 'selected' : ''; ?>>
                                            <?php echo $et['exam_name']; ?> (Max: <?php echo $et['max_marks']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selected_exam_id): ?>

                    <div class="d-none d-print-block text-center mb-4">
                        <h2 class="text-uppercase m-0">Master Marksheet: <?php echo $class_name; ?></h2>
                        <p class="m-0">Exam: <strong><?php echo $current_exam_name; ?></strong> | Max Marks:
                            <strong><?php echo $current_max; ?></strong>
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 75vh;">
                                <table class="table table-bordered mb-0 master-table text-center">
                                    <thead>
                                        <tr>
                                            <th class="stu-col" style="z-index: 20;">Student Name</th>
                                            <?php foreach ($subjects as $sub): ?>
                                                <th>
                                                    <div style="font-size:0.9rem;"><?php echo $sub['subject_code']; ?></div>
                                                    <div style="font-size:0.65rem; font-weight:normal; opacity:0.8;"
                                                        title="<?php echo $sub['subject_name']; ?>">
                                                        <?php echo substr($sub['subject_name'], 0, 10) . (strlen($sub['subject_name']) > 10 ? '..' : ''); ?>
                                                    </div>
                                                </th>
                                            <?php endforeach; ?>
                                            <th class="bg-dark text-white">Total</th>
                                            <th class="bg-dark text-white">Avg %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($students)): ?>
                                            <tr>
                                                <td colspan="<?php echo count($subjects) + 3; ?>" class="p-5 text-muted">No students
                                                    found in this class.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $stu):
                                                $sid = $stu['student_id'];
                                                $total_score = 0;
                                                $subject_count = 0;
                                                ?>                                        <tr>                                    <td class="stu-col">
                                                                        <div class="fw-bold text-dark"><?php echo $stu['student_name']; ?></div>
                                                        <small
                                                                class="text-muted font-monospace"><?php echo $stu['school_register_no']; ?></small>
                                                                    </td>
                                                        <?php foreach ($subjects as $sub):
                                                            $sub_id = $sub['subject_id'];
                                                            $score = isset($marks_map[$sid][$sub_id]) ? $marks_map[$sid][$sub_id] : null;

                                                            if ($score !== null) {
                                                                $total_score += floatval($score);
                                                                $subject_count++;
                                                                $grade = getGrade(floatval($score), $current_max);
                                                                $color = ($grade == 'F') ? 'text-danger' : 'text-dark';
                                                                echo "<td><div class='mark-cell $color'>$score</div><span class='grade-small'>$grade</span></td>";
                                                            } else {
                                                                echo "<td><span class='text-missing'>-</span></td>";
                                                            }
                                                            ?>
                                                            <?php endforeach; ?>
                                                            <td class="bg-light fw-bold border-start border-2">
                                                                        <?php echo ($subject_count > 0) ? $total_score : '-'; ?>
                                                                </td>
                                                            <td class="bg-light fw-bold text-primary">
                                                                <?php
                                                                if ($subject_count > 0 && $current_max > 0) {
                                                                    $max_possible = $subject_count * $current_max;
                                                                    $avg = ($total_score / $max_possible) * 100;
                                                                    echo round($avg, 1) . "%";
                                                                } else {
                                                                    echo "-";
                                                                }
                                                                ?>
                                                                    </td>
                                                                </tr>
                                                        <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer bg-white small text-muted no-print">
                                    <i class="fas fa-info-circle me-1"></i> <strong>Note:</strong> Marks shown out of
                                    <?php echo $current_max; ?>. "Total" is obtained sum. "Avg %" is based on graded subjects.
                                </div>
                            </div>

                    <?php else: ?>
                            <div class="text-center py-5 mt-4 bg-white rounded shadow-sm border border-dashed">
                                <i class="fas fa-file-invoice fa-3x text-warning mb-3 opacity-50"></i>
                                <h4 class="fw-bold text-secondary">No Exam Selected</h4>
                                <p class="text-muted">Please select an Examination Term from the dropdown above to generate the
                                    marksheet.</p>
                            </div>
                    <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>