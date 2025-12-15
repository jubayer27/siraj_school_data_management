<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); exit(); 
}

$teacher_id = $_SESSION['user_id'];

// 2. FETCH CLASS
$class_q = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $teacher_id");
$my_class = $class_q->fetch_assoc();
$cid = $my_class ? $my_class['class_id'] : 0;

if(!$cid) die("<div class='main-content p-5'><h2>No class assigned.</h2></div>");

// 3. SETTINGS & FILTERS
$exam_filter = isset($_GET['exam_type']) ? $_GET['exam_type'] : 'Midterm';
$export = isset($_GET['export']) ? $_GET['export'] : '';

// 4. FETCH DATA
// A. Get All Subjects for this class (Columns)
$subjects = [];
$sub_res = $conn->query("SELECT subject_id, subject_name, subject_code FROM subjects WHERE class_id = $cid ORDER BY subject_id ASC");
while($s = $sub_res->fetch_assoc()) $subjects[] = $s;

// B. Get All Students (Rows)
$students = [];
$stu_res = $conn->query("SELECT student_id, student_name, school_register_no FROM students WHERE class_id = $cid ORDER BY student_name ASC");
while($stu = $stu_res->fetch_assoc()) {
    $stu['marks'] = []; // Placeholder for subject marks
    $stu['total'] = 0;
    $stu['max_total'] = 0;
    $stu['failed_subjects'] = 0;
    $students[$stu['student_id']] = $stu;
}

// C. Get All Marks for this Exam & Class
$sql_marks = "SELECT sse.student_id, sse.subject_id, sm.mark_obtained 
              FROM student_marks sm
              JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id
              JOIN students st ON sse.student_id = st.student_id
              WHERE st.class_id = $cid AND sm.exam_type = '$exam_filter'";
$marks_res = $conn->query($sql_marks);

// D. Map Marks to Students
while($m = $marks_res->fetch_assoc()){
    if(isset($students[$m['student_id']])){
        $students[$m['student_id']]['marks'][$m['subject_id']] = $m['mark_obtained'];
    }
}

// 5. CALCULATE RESULTS & RANKING
foreach($students as $sid => $data){
    foreach($subjects as $sub){
        $sub_id = $sub['subject_id'];
        if(isset($data['marks'][$sub_id])){
            $mark = $data['marks'][$sub_id];
            $students[$sid]['total'] += $mark;
            $students[$sid]['max_total'] += 100; // Assume 100 per subject
            if($mark < 40) $students[$sid]['failed_subjects']++;
        }
    }
    // Calculate Average / Percentage
    $count = count($data['marks']);
    $students[$sid]['avg'] = ($count > 0) ? round($students[$sid]['total'] / $count, 1) : 0;
    $students[$sid]['percent'] = ($students[$sid]['max_total'] > 0) ? round(($students[$sid]['total'] / $students[$sid]['max_total']) * 100, 1) : 0;
}

// Sort by Total Score Descending (For Ranking)
usort($students, function($a, $b) {
    return $b['total'] <=> $a['total'];
});

// Assign Rank
$rank = 1;
foreach($students as $key => $val) {
    $students[$key]['rank'] = $rank++;
}

// 6. EXPORT LOGIC (Excel)
if($export == 'excel'){
    ob_end_clean();
    $filename = "MasterSheet_" . $my_class['class_name'] . "_" . $exam_filter . "_" . date('Ymd');
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename.xls\"");
    
    echo "<table border='1'><thead><tr>
            <th>Rank</th><th>Reg No</th><th>Student Name</th>";
    foreach($subjects as $sub) echo "<th>{$sub['subject_code']}</th>";
    echo "<th>Total</th><th>Avg</th><th>Result</th></tr></thead><tbody>";
    
    foreach($students as $stu){
        echo "<tr>
            <td>{$stu['rank']}</td>
            <td>{$stu['school_register_no']}</td>
            <td>{$stu['student_name']}</td>";
        foreach($subjects as $sub){
            $m = isset($stu['marks'][$sub['subject_id']]) ? $stu['marks'][$sub['subject_id']] : '-';
            echo "<td>$m</td>";
        }
        $res = ($stu['failed_subjects'] > 0) ? 'FAIL' : 'PASS';
        echo "<td>{$stu['total']}</td><td>{$stu['avg']}</td><td>$res</td></tr>";
    }
    echo "</tbody></table>";
    exit();
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important; margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important; display: block !important;
    }
    .container-fluid { padding: 30px !important; }
    
    /* Table Styling for Matrix */
    .master-table th { 
        background-color: #2c3e50; color: white; 
        vertical-align: middle; text-align: center; font-size: 0.85rem; 
        white-space: nowrap;
    }
    .master-table td { 
        vertical-align: middle; text-align: center; font-weight: 500; font-size: 0.9rem;
    }
    .col-name { text-align: left !important; min-width: 200px; position: sticky; left: 0; background: #fff; z-index: 10; border-right: 2px solid #ddd; }
    .col-rank { background: #FFD700; font-weight: bold; }
    .mark-fail { color: red; background-color: #ffebee; font-weight: bold; }
    .mark-distinction { color: green; font-weight: bold; }
    .result-pass { color: green; background: #e8f5e9; font-weight: bold; }
    .result-fail { color: red; background: #fbe9e7; font-weight: bold; }

    @media print {
        .no-print, .sidebar { display: none !important; }
        .main-content { width: 100% !important; margin: 0 !important; }
        .master-table { font-size: 10pt; }
        .col-name { position: static; border: 1px solid #000; }
        @page { size: landscape; }
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
                        Total Students: <strong><?php echo count($students); ?></strong>
                    </p>
                </div>
                
                <div class="d-flex gap-2 align-items-center">
                    <form method="GET" class="d-flex gap-2">
                        <select name="exam_type" class="form-select" onchange="this.form.submit()">
                            <option value="Midterm" <?php echo $exam_filter=='Midterm'?'selected':''; ?>>Midterm</option>
                            <option value="Final" <?php echo $exam_filter=='Final'?'selected':''; ?>>Final Exam</option>
                            <option value="Quiz 1" <?php echo $exam_filter=='Quiz 1'?'selected':''; ?>>Quiz 1</option>
                        </select>
                    </form>
                    
                    <a href="?exam_type=<?php echo $exam_filter; ?>&export=excel" class="btn btn-success">
                        <i class="fas fa-file-excel me-2"></i> Export Excel
                    </a>
                    <button onclick="window.print()" class="btn btn-warning fw-bold">
                        <i class="fas fa-print me-2"></i> Print
                    </button>
                </div>
            </div>

            <div class="d-none d-print-block text-center mb-4">
                <h2 class="fw-bold">SEKOLAH INTEGRASI RENDAH AGAMA JAWI (SIRAJ) AL ALUSI</h2>
                <h4>MASTER MARKSHEET: <?php echo strtoupper($my_class['class_name']); ?> - <?php echo strtoupper($exam_filter); ?></h4>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 master-table">
                            <thead>
                                <tr>
                                    <th class="col-rank">Pos</th>
                                    <th>Reg No</th>
                                    <th class="col-name text-center">Student Name</th>
                                    
                                    <?php foreach($subjects as $sub): ?>
                                        <th title="<?php echo $sub['subject_name']; ?>">
                                            <?php echo $sub['subject_code']; ?><br>
                                            <span style="font-size:0.7rem; font-weight:normal;">(100)</span>
                                        </th>
                                    <?php endforeach; ?>
                                    
                                    <th class="bg-dark text-white">Grand<br>Total</th>
                                    <th class="bg-dark text-white">Avg<br>(%)</th>
                                    <th class="bg-dark text-white">Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($students)): ?>
                                    <tr><td colspan="<?php echo count($subjects) + 6; ?>" class="p-5 text-muted">No students found in this class.</td></tr>
                                <?php else: ?>
                                    
                                    <?php foreach($students as $stu): 
                                        $is_pass = ($stu['failed_subjects'] == 0 && count($stu['marks']) > 0);
                                    ?>
                                    <tr>
                                        <td class="col-rank"><?php echo $stu['rank']; ?></td>
                                        
                                        <td class="font-monospace small"><?php echo $stu['school_register_no']; ?></td>
                                        
                                        <td class="col-name text-start fw-bold text-dark"><?php echo $stu['student_name']; ?></td>
                                        
                                        <?php foreach($subjects as $sub): 
                                            $mark = isset($stu['marks'][$sub['subject_id']]) ? $stu['marks'][$sub['subject_id']] : ''; 
                                            // Style logic
                                            $cell_style = "";
                                            if($mark !== '') {
                                                if($mark < 40) $cell_style = "mark-fail";
                                                elseif($mark >= 80) $cell_style = "mark-distinction";
                                            } else {
                                                $mark = "-";
                                            }
                                        ?>
                                            <td class="<?php echo $cell_style; ?>"><?php echo $mark; ?></td>
                                        <?php endforeach; ?>
                                        
                                        <td class="fw-bold bg-light border-start border-dark"><?php echo $stu['total']; ?></td>
                                        
                                        <td class="fw-bold bg-light"><?php echo $stu['avg']; ?></td>
                                        
                                        <td class="<?php echo $is_pass ? 'result-pass' : 'result-fail'; ?>">
                                            <?php echo $is_pass ? 'PASS' : 'FAIL'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-none d-print-block mt-4 small">
                <strong>Legend:</strong> 
                <span class="text-success fw-bold me-3">Green: Distinction (80+)</span>
                <span class="text-danger fw-bold me-3">Red: Fail (<40)</span>
                <span>Pos: Position in Class</span>
                <br>
                <em>Computer generated document. Date: <?php echo date('d/m/Y'); ?></em>
            </div>

        </div>
    </div>
</div>
</body>
</html>