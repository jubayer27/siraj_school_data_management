<?php
session_start();
// 1. ENABLE ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/db.php';
include 'includes/header.php';

// 2. AUTHENTICATION
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// 3. FETCH CLASS INFO
$class_q = $conn->query("SELECT class_id, class_name FROM classes WHERE class_teacher_id = $teacher_id");
if (!$class_q)
    die("Class Query Failed: " . $conn->error);

$my_class = $class_q->fetch_assoc();
$class_id = $my_class ? $my_class['class_id'] : 0;
$class_name = $my_class ? $my_class['class_name'] : "No Class Assigned";

// 4. FETCH EXAM TYPES
$exams_q = $conn->query("SELECT * FROM exam_types WHERE status='active' ORDER BY created_at DESC");
$exam_types = [];
while ($ex = $exams_q->fetch_assoc()) {
    $exam_types[] = $ex;
}

// 5. FETCH SUBJECTS
$class_subjects = [];
if ($class_id) {
    $subjects_q = $conn->query("SELECT DISTINCT s.subject_id, s.subject_name 
                                FROM subjects s 
                                JOIN student_subject_enrollment sse ON s.subject_id = sse.subject_id
                                JOIN students st ON sse.student_id = st.student_id
                                WHERE st.class_id = $class_id
                                ORDER BY s.subject_name ASC");
    if ($subjects_q) {
        while ($sub = $subjects_q->fetch_assoc()) {
            $class_subjects[] = $sub;
        }
    }
}

// 6. FILTER LOGIC
$exam_filter = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';
$subject_filter = isset($_GET['subject_filter']) ? $_GET['subject_filter'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Resolve Max Mark
$filter_max_mark = 100; // Default
if ($exam_filter) {
    foreach ($exam_types as $et) {
        if ($et['exam_name'] == $exam_filter) {
            $filter_max_mark = floatval($et['max_marks']);
            break;
        }
    }
}

// 7. MAIN DATA FETCH
$results = null;
$stats = ['avg' => 0, 'pass' => 0, 'fail' => 0, 'total' => 0, 'pending' => 0];

if ($class_id) {
    // SQL QUERY CONSTRUCTION
    // FIX APPLIED: Added 'COLLATE utf8mb4_general_ci' to the JOIN ON clause
    $sql = "SELECT st.student_name, st.photo, st.school_register_no, 
                   sub.subject_name, sub.subject_code, 
                   sm.mark_obtained, sm.grade, sm.exam_type,
                   et.max_marks as row_max
            FROM students st
            JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
            JOIN subjects sub ON sse.subject_id = sub.subject_id
            
            LEFT JOIN student_marks sm ON (sse.enrollment_id = sm.enrollment_id";

    if ($exam_filter) {
        $sql .= " AND sm.exam_type = '$exam_filter'";
    }

    // *** FIX IS HERE: Forces collation to match during the JOIN ***
    $sql .= ") 
            LEFT JOIN exam_types et ON sm.exam_type COLLATE utf8mb4_general_ci = et.exam_name COLLATE utf8mb4_general_ci
            WHERE st.class_id = $class_id";

    // Apply Subject Filter
    if ($subject_filter) {
        $sql .= " AND sub.subject_id = '$subject_filter'";
    }

    // Apply Search Filter
    if ($search_query) {
        $sql .= " AND (st.student_name LIKE '%$search_query%' OR sub.subject_name LIKE '%$search_query%')";
    }

    $sql .= " ORDER BY st.student_name, sub.subject_name, sm.exam_type";

    $results = $conn->query($sql);

    // Check for SQL Error
    if (!$results) {
        // If it still fails, try unicode_ci
        $sql = str_replace("utf8mb4_general_ci", "utf8mb4_unicode_ci", $sql);
        $results = $conn->query($sql);

        if (!$results)
            die("Database Error (Collation Mismatch): " . $conn->error);
    }

    // CALCULATE STATS
    if ($exam_filter && $results->num_rows > 0) {
        $total_percentage = 0;
        $graded_count = 0;

        while ($row = $results->fetch_assoc()) {
            $stats['total']++;
            if ($row['mark_obtained'] !== null) {
                $graded_count++;
                $mark = floatval($row['mark_obtained']);
                $max = ($filter_max_mark > 0) ? $filter_max_mark : 100;
                $percentage = ($mark / $max) * 100;
                $total_percentage += $percentage;

                if ($percentage >= 40)
                    $stats['pass']++;
                else
                    $stats['fail']++;
            } else {
                $stats['pending']++;
            }
        }
        if ($graded_count > 0) {
            $stats['avg'] = round($total_percentage / $graded_count, 1) . "%";
        }
        $results->data_seek(0);
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        transition: 0.2s;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
    }

    .stat-card {
        display: flex;
        align-items: center;
        padding: 20px;
    }

    .icon-square {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-right: 15px;
    }

    /* Colors */
    .bg-blue-soft {
        background: #e3f2fd;
        color: #1565c0;
    }

    .bg-green-soft {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .bg-red-soft {
        background: #ffebee;
        color: #c62828;
    }

    .bg-gold-soft {
        background: #fff8e1;
        color: #fbc02d;
    }

    .bg-gray-soft {
        background: #f5f5f5;
        color: #757575;
    }

    .avatar-sm {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
        border: 1px solid #dee2e6;
    }

    .table-hover tbody tr:hover {
        background-color: #fffcf5;
    }

    .grade-badge {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
        margin: 0 auto;
    }

    .grade-A,
    .grade-B {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .grade-C {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .grade-D,
    .grade-E {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .grade-F {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .grade-null {
        background: #f0f0f0;
        color: #999;
        border: 1px dashed #ccc;
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

        .container-fluid {
            padding: 20px !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
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
                    <h2 class="fw-bold text-dark mb-1">Academic Performance</h2>
                    <p class="text-secondary mb-0">Overview for <strong><?php echo $class_name; ?></strong></p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-dark shadow-sm" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                </div>
            </div>

            <div class="d-none d-print-block text-center mb-4 pb-3 border-bottom border-dark">
                <h2><?php echo $class_name; ?> - Academic Report</h2>
                <p>
                    Exam: <strong><?php echo $exam_filter ? $exam_filter : 'All Exams'; ?></strong>
                    <?php if ($subject_filter)
                        echo " | Subject Filter Applied"; ?>
                </p>
            </div>

            <?php if (!$class_id): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center no-print">
                    <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                    <div><strong>No Class Assigned.</strong><br>You are not currently listed as a Class Teacher for any
                        active class.</div>
                </div>
            <?php else: ?>

                <div class="card mb-4 no-print">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <form method="GET" class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label class="small text-muted fw-bold mb-1">1. Filter by Exam</label>
                                <select name="exam_type" class="form-select border-primary" onchange="this.form.submit()">
                                    <option value="">View All Records</option>
                                    <?php foreach ($exam_types as $et): ?>
                                        <option value="<?php echo $et['exam_name']; ?>" <?php echo ($exam_filter == $et['exam_name']) ? 'selected' : ''; ?>>
                                            <?php echo $et['exam_name']; ?> (Max: <?php echo $et['max_marks']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="small text-muted fw-bold mb-1">2. Filter by Subject</label>
                                <select name="subject_filter" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($class_subjects as $s): ?>
                                        <option value="<?php echo $s['subject_id']; ?>" <?php echo ($subject_filter == $s['subject_id']) ? 'selected' : ''; ?>>
                                            <?php echo $s['subject_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted fw-bold mb-1">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i
                                            class="fas fa-search text-muted"></i></span>
                                    <input type="text" name="search" class="form-control border-start-0"
                                        placeholder="Student Name or Subject..." value="<?php echo $search_query; ?>">
                                </div>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-warning w-100 fw-bold shadow-sm"
                                    style="height: 38px; margin-top: 24px;">View</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($exam_filter && $results && $results->num_rows > 0): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="icon-square bg-gold-soft"><i class="fas fa-chart-line"></i></div>
                                <div>
                                    <h3 class="fw-bold mb-0"><?php echo $stats['avg']; ?></h3>
                                    <small class="text-secondary fw-bold">Avg Performance</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="icon-square bg-blue-soft"><i class="fas fa-clipboard-list"></i></div>
                                <div>
                                    <h3 class="fw-bold mb-0"><?php echo $stats['total']; ?></h3>
                                    <small class="text-secondary fw-bold">Total Entries</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="icon-square bg-green-soft"><i class="fas fa-check-circle"></i></div>
                                <div>
                                    <h3 class="fw-bold mb-0 text-success"><?php echo $stats['pass']; ?></h3>
                                    <small class="text-secondary fw-bold">Passed (≥40%)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="icon-square bg-gray-soft"><i class="fas fa-hourglass-half"></i></div>
                                <div>
                                    <h3 class="fw-bold mb-0 text-secondary"><?php echo $stats['pending']; ?></h3>
                                    <small class="text-secondary fw-bold">Pending Grading</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 text-secondary text-uppercase" style="font-size: 0.75rem;">Student
                                        </th>
                                        <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Subject</th>
                                        <th class="text-center text-secondary text-uppercase" style="font-size: 0.75rem;">
                                            Exam Type</th>
                                        <th class="text-center text-secondary text-uppercase" style="font-size: 0.75rem;">
                                            Mark / Max</th>
                                        <th class="text-center text-secondary text-uppercase" style="font-size: 0.75rem;">
                                            Grade</th>
                                        <th class="text-center text-secondary text-uppercase" style="font-size: 0.75rem;">
                                            Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($results && $results->num_rows > 0): ?>
                                        <?php while ($row = $results->fetch_assoc()):
                                            $has_mark = ($row['mark_obtained'] !== null);
                                            // Determine max mark for this specific row (Handles "All" view)
                                            $row_max = $exam_filter ? $filter_max_mark : ($row['row_max'] ? $row['row_max'] : 100);
                                            $exam_label = $row['exam_type'] ? $row['exam_type'] : '<span class="text-muted small">Pending</span>';
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="no-print">
                                                            <?php $pic = $row['photo'] ? "../uploads/" . $row['photo'] : "https://ui-avatars.com/api/?name=" . $row['student_name'] . "&background=random"; ?>
                                                            <img src="<?php echo $pic; ?>" class="avatar-sm">
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="fw-bold text-dark d-block"><?php echo $row['student_name']; ?></span>
                                                            <small
                                                                class="text-muted font-monospace"><?php echo $row['school_register_no']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="d-block fw-bold text-secondary"><?php echo $row['subject_name']; ?></span>
                                                    <small
                                                        class="text-muted font-monospace"><?php echo $row['subject_code']; ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <?php echo $has_mark ? "<span class='badge bg-light text-dark border'>$exam_label</span>" : "-"; ?>
                                                </td>
                                                <td class="text-center fw-bold">
                                                    <?php echo $has_mark ? $row['mark_obtained'] : '-'; ?>
                                                    <?php if ($has_mark): ?>
                                                        <span class="text-muted small fw-normal">/ <?php echo $row_max; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    if ($has_mark) {
                                                        $g = strtoupper($row['grade']);
                                                        $badgeClass = ($g == 'A' || $g == 'B') ? 'grade-A' : (($g == 'C') ? 'grade-C' : 'grade-F');
                                                        echo '<div class="grade-badge ' . $badgeClass . '">' . $g . '</div>';
                                                    } else {
                                                        echo '<div class="grade-badge grade-null">-</div>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php echo $has_mark
                                                        ? '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2">Graded</span>'
                                                        : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2">Pending</span>'; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fas fa-folder-open fa-2x mb-3 d-block opacity-25"></i>
                                                No records found for the selected filters.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($exam_filter): ?>
                        <div class="card-footer bg-white py-3 no-print">
                            <small class="text-muted">Max Mark for <strong><?php echo $exam_filter; ?></strong> is
                                <?php echo $filter_max_mark; ?></small>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>