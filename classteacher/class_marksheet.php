<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$tid = $_SESSION['user_id'];

// 2. GET CLASS ID
$class_q = $conn->query("SELECT class_id, class_name, year FROM classes WHERE class_teacher_id = $tid");
$my_class = $class_q->fetch_assoc();

if (!$my_class) {
    echo "<div class='main-content' style='margin-left:260px; padding:40px;'><div class='alert alert-warning'>You are not assigned to any class.</div></div>";
    exit();
}

$cid = $my_class['class_id'];
$exam_type = isset($_GET['exam']) ? $_GET['exam'] : 'Midterm';

// 3. GET SUBJECTS (Columns)
$subjects = [];
$subRes = $conn->query("SELECT * FROM subjects WHERE class_id = $cid ORDER BY subject_code ASC");
while ($s = $subRes->fetch_assoc()) {
    $subjects[] = $s;
}

// 4. GET STUDENTS & MARKS (Rows & Data)
$students = [];

// A. Initialize Students
$stu_res = $conn->query("SELECT student_id, student_name, school_register_no FROM students WHERE class_id = $cid ORDER BY student_name ASC");
while ($row = $stu_res->fetch_assoc()) {
    $students[$row['student_id']] = [
        'info' => $row,
        'marks' => []
    ];
}

// B. Fetch Marks for selected Exam Type
$marks_sql = "SELECT sse.student_id, sse.subject_id, sm.mark_obtained 
              FROM student_marks sm 
              JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id 
              WHERE sse.class_id = $cid AND sm.exam_type = '$exam_type'";
$marks_res = $conn->query($marks_sql);

while ($m = $marks_res->fetch_assoc()) {
    if (isset($students[$m['student_id']])) {
        $students[$m['student_id']]['marks'][$m['subject_id']] = $m['mark_obtained'];
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    /* SCREEN STYLES */
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

    .container-fluid {
        padding: 0 !important;
    }

    /* Filter Bar */
    .filter-bar {
        background: white;
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    /* Marksheet Table */
    .sheet-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .marks-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .marks-table th {
        background: #2c3e50;
        color: white;
        padding: 12px 15px;
        text-align: center;
        border: 1px solid #34495e;
        white-space: nowrap;
    }

    .marks-table th.stu-col {
        text-align: left;
        position: sticky;
        left: 0;
        z-index: 10;
        background: #2c3e50;
    }

    .marks-table td {
        padding: 8px 15px;
        border: 1px solid #eee;
        text-align: center;
        color: #333;
    }

    .marks-table td.stu-col {
        text-align: left;
        font-weight: 600;
        background: #fff;
        position: sticky;
        left: 0;
        z-index: 5;
        border-right: 2px solid #ddd;
    }

    .marks-table tr:hover td {
        background-color: #fffcf5;
    }

    .marks-table tr:hover td.stu-col {
        background-color: #fffcf5;
    }

    .fail-mark {
        color: #e74c3c;
        font-weight: bold;
    }

    .pass-mark {
        color: #27ae60;
    }

    .avg-cell {
        font-weight: bold;
        background: #fdfdfd;
        color: #b8860b;
    }

    /* PRINT STYLES */
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

        .filter-bar {
            display: none;
        }

        .sheet-card {
            box-shadow: none;
            border: 1px solid #000;
        }

        .marks-table th {
            background: #eee !important;
            color: #000 !important;
            border: 1px solid #000;
        }

        .marks-table td {
            border: 1px solid #000;
        }

        .page-break {
            page-break-after: always;
        }

        body {
            background: white;
            -webkit-print-color-adjust: exact;
        }
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
                    <h2 class="fw-bold text-dark mb-0">Master Marksheet</h2>
                    <p class="text-secondary mb-0">
                        Class: <strong><?php echo $my_class['class_name']; ?></strong> |
                        Year: <?php echo $my_class['year']; ?>
                    </p>
                </div>
            </div>

            <div class="filter-bar no-print">
                <form method="GET" class="d-flex align-items-center gap-3">
                    <label class="fw-bold text-muted">Exam Type:</label>
                    <select name="exam" class="form-select form-select-sm w-auto border-warning"
                        onchange="this.form.submit()">
                        <option value="Midterm" <?php echo $exam_type == 'Midterm' ? 'selected' : ''; ?>>Midterm</option>
                        <option value="Final" <?php echo $exam_type == 'Final' ? 'selected' : ''; ?>>Final</option>
                    </select>
                </form>

                <button onclick="window.print()" class="btn btn-primary fw-bold shadow-sm">
                    <i class="fas fa-print me-2"></i> Print Sheet
                </button>
            </div>

            <div class="d-none d-print-block text-center mb-4">
                <h3 class="fw-bold text-uppercase">MASTER MARKSHEET REPORT</h3>
                <p>Class: <?php echo $my_class['class_name']; ?> | Exam: <?php echo $exam_type; ?> | Year:
                    <?php echo $my_class['year']; ?>
                </p>
            </div>

            <div class="sheet-card">
                <div class="table-responsive">
                    <table class="marks-table">
                        <thead>
                            <tr>
                                <th class="stu-col"># Student Name</th>
                                <?php foreach ($subjects as $sub): ?>
                                    <th title="<?php echo $sub['subject_name']; ?>">
                                        <?php echo $sub['subject_code']; ?>
                                    </th>
                                <?php endforeach; ?>
                                <th style="background:#444;">AVG</th>
                                <th style="background:#444;">RANK</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($students)):
                                echo "<tr><td colspan='10' class='text-center p-4'>No students found.</td></tr>";
                            else:
                                // To Calculate Rank, we first need averages
                                $ranked_students = [];
                                foreach ($students as $sid => $data) {
                                    $total = 0;
                                    $count = 0;
                                    foreach ($subjects as $sub) {
                                        if (isset($data['marks'][$sub['subject_id']])) {
                                            $total += $data['marks'][$sub['subject_id']];
                                            $count++;
                                        }
                                    }
                                    $avg = $count > 0 ? $total / $count : 0;
                                    $data['calculated_avg'] = $avg;
                                    $ranked_students[$sid] = $data;
                                }

                                // Sort by Avg Descending
                                uasort($ranked_students, function ($a, $b) {
                                    return $b['calculated_avg'] <=> $a['calculated_avg'];
                                });

                                // Assign Ranks
                                $rank = 1;
                                foreach ($ranked_students as $sid => $data):
                                    ?>
                                    <tr>
                                        <td class="stu-col">
                                            <div class="d-flex flex-column">
                                                <span><?php echo $data['info']['student_name']; ?></span>
                                                <small class="text-muted no-print"
                                                    style="font-size:0.7rem; font-weight:normal;">
                                                    <?php echo $data['info']['school_register_no']; ?>
                                                </small>
                                            </div>
                                        </td>

                                        <?php foreach ($subjects as $sub):
                                            $mark = isset($data['marks'][$sub['subject_id']]) ? $data['marks'][$sub['subject_id']] : '-';
                                            $style = is_numeric($mark) ? ($mark < 40 ? 'fail-mark' : 'pass-mark') : 'text-muted';
                                            ?>
                                            <td class="<?php echo $style; ?>"><?php echo $mark; ?></td>
                                        <?php endforeach; ?>

                                        <td class="avg-cell"><?php echo number_format($data['calculated_avg'], 1); ?></td>
                                        <td class="fw-bold"><?php echo ($data['calculated_avg'] > 0) ? $rank++ : '-'; ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3 no-print text-muted small">
                <i class="fas fa-info-circle me-1"></i> Subjects are displayed by their code. Hover over the header to
                see full name.
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>