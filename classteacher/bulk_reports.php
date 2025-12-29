<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// 2. FETCH CLASS
$class_q = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $teacher_id");
$my_class = $class_q->fetch_assoc();
$cid = $my_class ? $my_class['class_id'] : 0;

if (!$cid)
    die("<div class='main-content' style='margin-left:260px; padding:40px;'><h2>No class assigned.</h2></div>");

$report_type = isset($_GET['type']) ? $_GET['type'] : 'menu';
$export_format = isset($_GET['export']) ? $_GET['export'] : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

// ==========================================
// 3. HANDLE EXPORTS (EXCEL / CSV)
// ==========================================
if ($export_format && $cid) {
    ob_end_clean(); // Clean output buffer
    $filename = $report_type . "_" . date('Y-m-d');

    // Headers
    if ($export_format == 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename.xls\"");
    } elseif ($export_format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        $output = fopen('php://output', 'w');
    }

    // --- REPORT 1: CLASS MASTER REPORT ---
    if ($report_type == 'class_master') {
        $res = $conn->query("SELECT * FROM students WHERE class_id = $cid ORDER BY student_name");
        $headers = ['No', 'Register No', 'Student Name', 'Gender', 'IC Number', 'Enrolled Subjects'];

        if ($export_format == 'excel') {
            echo "<table border='1'><tr>";
            foreach ($headers as $h)
                echo "<th>$h</th>";
            echo "</tr>";
        } else {
            fputcsv($output, $headers);
        }

        $i = 1;
        while ($row = $res->fetch_assoc()) {
            $sid = $row['student_id'];
            $sub_q = $conn->query("SELECT s.subject_name FROM subjects s JOIN student_subject_enrollment sse ON s.subject_id=sse.subject_id WHERE sse.student_id=$sid");
            $subjects = [];
            while ($s = $sub_q->fetch_assoc())
                $subjects[] = $s['subject_name'];
            $subject_str = implode(", ", $subjects);

            $data = [$i++, $row['school_register_no'], $row['student_name'], $row['gender'], $row['ic_no'], $subject_str];

            if ($export_format == 'excel') {
                echo "<tr>";
                foreach ($data as $d)
                    echo "<td>$d</td>";
                echo "</tr>";
            } else {
                fputcsv($output, $data);
            }
        }
        if ($export_format == 'excel')
            echo "</table>";
        else
            fclose($output);
    }

    // --- REPORT 2: INDIVIDUAL STUDENT REPORT ---
    elseif ($report_type == 'student_individual' && $student_id) {
        $stu = $conn->query("SELECT * FROM students WHERE student_id = $student_id")->fetch_assoc();
        $headers = ['Section', 'Field', 'Value', 'Extra'];

        if ($export_format == 'excel')
            echo "<table border='1'><tr><th colspan='4'>Student Report: {$stu['student_name']}</th></tr>";
        else {
            fputcsv($output, ['Student Report:', $stu['student_name']]);
            fputcsv($output, $headers);
        }

        // Profile Rows
        $profile_data = [
            ['Profile', 'Register No', $stu['school_register_no'], ''],
            ['Profile', 'IC No', $stu['ic_no'], ''],
            ['Profile', 'Gender', $stu['gender'], ''],
            ['Contact', 'Phone', $stu['phone'], ''],
            ['Contact', 'Address', $stu['address'], ''],
            ['Family', 'Father', $stu['father_name'], $stu['father_phone']],
            ['Family', 'Mother', $stu['mother_name'], $stu['mother_phone']],
        ];

        foreach ($profile_data as $row) {
            if ($export_format == 'excel')
                echo "<tr><td>{$row[0]}</td><td>{$row[1]}</td><td>{$row[2]}</td><td>{$row[3]}</td></tr>";
            else
                fputcsv($output, $row);
        }

        // Marks Rows
        if ($export_format == 'excel')
            echo "<tr><td colspan='4'><b>Academic Results</b></td></tr><tr><td><b>Subject</b></td><td><b>Exam</b></td><td><b>Mark</b></td><td><b>Grade</b></td></tr>";
        else {
            fputcsv($output, []);
            fputcsv($output, ['ACADEMIC RESULTS']);
            fputcsv($output, ['Subject', 'Exam', 'Mark', 'Grade']);
        }

        $marks = $conn->query("SELECT s.subject_name, sm.exam_type, sm.mark_obtained, sm.grade FROM student_marks sm JOIN student_subject_enrollment sse ON sm.enrollment_id=sse.enrollment_id JOIN subjects s ON sse.subject_id=s.subject_id WHERE sse.student_id=$student_id ORDER BY s.subject_name");

        while ($m = $marks->fetch_assoc()) {
            if ($export_format == 'excel')
                echo "<tr><td>{$m['subject_name']}</td><td>{$m['exam_type']}</td><td>{$m['mark_obtained']}</td><td>{$m['grade']}</td></tr>";
            else
                fputcsv($output, [$m['subject_name'], $m['exam_type'], $m['mark_obtained'], $m['grade']]);
        }

        if ($export_format == 'excel')
            echo "</table>";
        else
            fclose($output);
    }

    // --- REPORT 3: TEACHER & SUBJECT REPORT (UPDATED) ---
    elseif ($report_type == 'teacher_report') {
        // Query updated for Many-to-Many Teachers
        $sql = "SELECT s.subject_name, s.subject_code, 
                GROUP_CONCAT(u.full_name SEPARATOR ', ') as teacher_names,
                GROUP_CONCAT(u.phone SEPARATOR ', ') as teacher_phones,
                (SELECT COUNT(*) FROM student_subject_enrollment WHERE subject_id = s.subject_id) as enrollment
                FROM subjects s 
                LEFT JOIN subject_teachers st ON s.subject_id = st.subject_id
                LEFT JOIN users u ON st.teacher_id = u.user_id 
                WHERE s.class_id = $cid 
                GROUP BY s.subject_id
                ORDER BY s.subject_name";
        $res = $conn->query($sql);

        $headers = ['Subject Name', 'Code', 'Teacher Name(s)', 'Contact(s)', 'Total Students'];

        if ($export_format == 'excel') {
            echo "<table border='1'><tr>";
            foreach ($headers as $h)
                echo "<th>$h</th>";
            echo "</tr>";
        } else {
            fputcsv($output, $headers);
        }

        while ($row = $res->fetch_assoc()) {
            $data = [$row['subject_name'], $row['subject_code'], $row['teacher_names'], $row['teacher_phones'], $row['enrollment']];
            if ($export_format == 'excel') {
                echo "<tr>";
                foreach ($data as $d)
                    echo "<td>$d</td>";
                echo "</tr>";
            } else {
                fputcsv($output, $data);
            }
        }

        if ($export_format == 'excel')
            echo "</table>";
        else
            fclose($output);
    }

    exit();
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    /* SCREEN */
    body {
        background: #f4f6f9;
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

    .menu-card {
        transition: 0.2s;
        cursor: pointer;
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        text-decoration: none;
        color: inherit;
        height: 100%;
    }

    .menu-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .icon-box {
        font-size: 2.5rem;
        margin-bottom: 15px;
    }

    /* PRINT */
    @media print {

        .no-print,
        .sidebar {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            width: 100% !important;
            position: static !important;
        }

        .container-fluid {
            padding: 0 !important;
        }

        body {
            background: white;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="wrapper">
    <div class="no-print"><?php include 'includes/sidebar.php'; ?></div>

    <div class="main-content">
        <div class="container-fluid">

            <?php if ($report_type == 'menu'): ?>
                <div class="d-flex justify-content-between align-items-center mb-5 no-print">
                    <div>
                        <h2 class="fw-bold text-dark">Report Hub</h2>
                        <p class="text-secondary">Generate reports for
                            <strong><?php echo $my_class['class_name']; ?></strong>
                        </p>
                    </div>
                </div>

                <div class="row g-4 no-print">
                    <div class="col-md-4">
                        <a href="?type=class_master" class="card menu-card p-4 text-center">
                            <div class="icon-box text-primary"><i class="fas fa-list-alt"></i></div>
                            <h5 class="fw-bold">1. Class Master Report</h5>
                            <p class="text-muted small">Full roster with subjects, reg numbers, and teachers.</p>
                        </a>
                    </div>

                    <div class="col-md-4">
                        <div class="card menu-card p-4 text-center" style="cursor: default;">
                            <div class="icon-box text-success"><i class="fas fa-user-graduate"></i></div>
                            <h5 class="fw-bold">2. Individual Student Report</h5>
                            <p class="text-muted small mb-3">Search by Name or Reg No.</p>
                            <form method="GET" action="bulk_reports.php">
                                <input type="hidden" name="type" value="student_individual">
                                <div class="input-group">
                                    <input type="text" name="search_query" class="form-control form-control-sm"
                                        placeholder="Search..." required>
                                    <button class="btn btn-sm btn-success"><i class="fas fa-search"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <a href="?type=teacher_report" class="card menu-card p-4 text-center">
                            <div class="icon-box text-warning"><i class="fas fa-chalkboard-teacher"></i></div>
                            <h5 class="fw-bold">3. Teacher & Subject Report</h5>
                            <p class="text-muted small">List of subject teachers and enrollment counts.</p>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mb-4 no-print bg-white p-3 rounded shadow-sm">
                    <a href="bulk_reports.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Menu</a>

                    <div class="d-flex gap-2">
                        <?php
                        $export_link = "?type=$report_type";
                        if ($student_id)
                            $export_link .= "&student_id=$student_id";
                        ?>
                        <a href="<?php echo $export_link; ?>&export=excel" class="btn btn-success"><i
                                class="fas fa-file-excel me-2"></i> Excel</a>
                        <a href="<?php echo $export_link; ?>&export=csv" class="btn btn-primary"><i
                                class="fas fa-file-csv me-2"></i> CSV</a>
                        <button onclick="window.print()" class="btn btn-warning fw-bold"><i class="fas fa-print me-2"></i>
                            Print / PDF</button>
                    </div>
                </div>

                <div class="text-center mb-4 pb-3 border-bottom border-dark d-none d-print-block">
                    <h3 class="fw-bold text-uppercase m-0">SIRAJ Al Alusi</h3>
                    <p class="m-0">Class: <?php echo $my_class['class_name']; ?> | Report:
                        <?php echo ucwords(str_replace('_', ' ', $report_type)); ?>
                    </p>
                </div>

                <div class="bg-white p-4 rounded shadow-sm border">

                    <?php
                    // --- VIEW 1: CLASS MASTER REPORT ---
                    if ($report_type == 'class_master'):
                        $res = $conn->query("SELECT * FROM students WHERE class_id = $cid ORDER BY student_name");
                        ?>
                        <h4 class="fw-bold mb-3">Class Master List</h4>
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Reg No</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>IC Number</th>
                                    <th>Subjects Enrolled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1;
                                while ($row = $res->fetch_assoc()):
                                    $sid = $row['student_id'];
                                    $sub_q = $conn->query("SELECT s.subject_name FROM subjects s JOIN student_subject_enrollment sse ON s.subject_id=sse.subject_id WHERE sse.student_id=$sid");
                                    $subjects = [];
                                    while ($s = $sub_q->fetch_assoc())
                                        $subjects[] = $s['subject_name'];
                                    ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td class="font-monospace"><?php echo $row['school_register_no']; ?></td>
                                        <td class="fw-bold"><?php echo $row['student_name']; ?></td>
                                        <td><?php echo $row['gender']; ?></td>
                                        <td><?php echo $row['ic_no']; ?></td>
                                        <td class="small"><?php echo implode(", ", $subjects); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <?php
                        // --- VIEW 2: INDIVIDUAL STUDENT REPORT ---
                    elseif ($report_type == 'student_individual'):

                        if (!isset($_GET['student_id'])):
                            $search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
                            if ($search) {
                                $res = $conn->query("SELECT * FROM students WHERE class_id=$cid AND (student_name LIKE '%$search%' OR school_register_no LIKE '%$search%')");
                                echo "<h5 class='mb-3'>Search Results for: '<strong>$search</strong>'</h5>";
                                if ($res->num_rows > 0) {
                                    echo "<div class='list-group'>";
                                    while ($row = $res->fetch_assoc()) {
                                        echo "<a href='?type=student_individual&student_id={$row['student_id']}' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>
                                            <div><strong>{$row['student_name']}</strong> <small class='text-muted'>({$row['school_register_no']})</small></div>
                                            <span class='badge bg-primary rounded-pill'>View Report</span>
                                          </a>";
                                    }
                                    echo "</div>";
                                } else {
                                    echo "<div class='alert alert-warning'>No students found.</div>";
                                }
                            } else {
                                echo "<div class='alert alert-info'>Please use the search bar on the menu to find a student.</div>";
                            }

                        else:
                            $student_id = $_GET['student_id'];
                            $stu = $conn->query("SELECT * FROM students WHERE student_id = $student_id")->fetch_assoc();
                            ?>
                            <div class="row">
                                <div class="col-md-3 text-center mb-3">
                                    <div class="border p-2 d-inline-block bg-light">
                                        <?php $img = $stu['photo'] ? "../uploads/" . $stu['photo'] : "https://ui-avatars.com/api/?name=" . $stu['student_name']; ?>
                                        <img src="<?php echo $img; ?>" width="120">
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <h3 class="fw-bold"><?php echo $stu['student_name']; ?></h3>
                                    <p><strong>Reg No:</strong> <?php echo $stu['school_register_no']; ?> | <strong>IC:</strong>
                                        <?php echo $stu['ic_no']; ?></p>
                                    <table class="table table-sm table-bordered mt-3">
                                        <tr>
                                            <th>Gender</th>
                                            <td><?php echo $stu['gender']; ?></td>
                                            <th>DOB</th>
                                            <td><?php echo $stu['birthdate']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Phone</th>
                                            <td><?php echo $stu['phone']; ?></td>
                                            <th>Address</th>
                                            <td><?php echo $stu['address']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Father</th>
                                            <td><?php echo $stu['father_name']; ?> (<?php echo $stu['father_phone']; ?>)</td>
                                            <th>Mother</th>
                                            <td><?php echo $stu['mother_name']; ?> (<?php echo $stu['mother_phone']; ?>)</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <h5 class="fw-bold mt-4 border-bottom pb-2">Academic Record</h5>
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Exam</th>
                                        <th>Mark</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $marks = $conn->query("SELECT s.subject_name, sm.exam_type, sm.mark_obtained, sm.grade FROM student_marks sm JOIN student_subject_enrollment sse ON sm.enrollment_id=sse.enrollment_id JOIN subjects s ON sse.subject_id=s.subject_id WHERE sse.student_id=$student_id ORDER BY s.subject_name");
                                    while ($m = $marks->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $m['subject_name']; ?></td>
                                            <td><?php echo $m['exam_type']; ?></td>
                                            <td><?php echo $m['mark_obtained']; ?></td>
                                            <td><?php echo $m['grade']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <?php
                        // --- VIEW 3: TEACHER & SUBJECT REPORT (UPDATED) ---
                    elseif ($report_type == 'teacher_report'):
                        $sql = "SELECT s.subject_name, s.subject_code, 
                            GROUP_CONCAT(u.full_name SEPARATOR ', ') as teacher_names,
                            GROUP_CONCAT(u.phone SEPARATOR ', ') as teacher_phones,
                            (SELECT COUNT(*) FROM student_subject_enrollment WHERE subject_id = s.subject_id) as enrollment
                            FROM subjects s 
                            LEFT JOIN subject_teachers st ON s.subject_id = st.subject_id
                            LEFT JOIN users u ON st.teacher_id = u.user_id 
                            WHERE s.class_id = $cid 
                            GROUP BY s.subject_id
                            ORDER BY s.subject_name";
                        $res = $conn->query($sql);
                        ?>
                        <h4 class="fw-bold mb-3">Teacher & Subject Overview</h4>
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Code</th>
                                    <th>Assigned Teacher(s)</th>
                                    <th>Contact</th>
                                    <th>Students Enrolled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo $row['subject_name']; ?></td>
                                        <td><?php echo $row['subject_code']; ?></td>
                                        <td><?php echo $row['teacher_names'] ? $row['teacher_names'] : '<span class="text-danger">Unassigned</span>'; ?>
                                        </td>
                                        <td><?php echo $row['teacher_phones']; ?></td>
                                        <td class="text-center fw-bold"><?php echo $row['enrollment']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>