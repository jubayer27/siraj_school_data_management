<?php
include '../config/db.php';
include 'includes/header.php';
$tid = $_SESSION['user_id'];

// 1. Get Class ID
$myClass = $conn->query("SELECT class_id, class_name FROM classes WHERE class_teacher_id = $tid")->fetch_assoc();
if(!$myClass) die("<div class='main-content'>Not assigned to a class.</div>");
$cid = $myClass['class_id'];

// 2. Get Subjects
$subjects = [];
$subRes = $conn->query("SELECT * FROM subjects WHERE class_id = $cid");
while($s = $subRes->fetch_assoc()) $subjects[] = $s;

// 3. Get Student Data & Marks
$students = [];
$sql = "SELECT s.student_id, s.student_name, sm.mark_obtained, sse.subject_id 
        FROM students s 
        LEFT JOIN student_subject_enrollment sse ON s.student_id = sse.student_id 
        LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = 'Midterm'
        WHERE s.class_id = $cid ORDER BY s.student_name";
$res = $conn->query($sql);
while($row = $res->fetch_assoc()){
    $students[$row['student_id']]['name'] = $row['student_name'];
    $students[$row['student_id']]['marks'][$row['subject_id']] = $row['mark_obtained'];
}
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Master Marksheet: <?php echo $myClass['class_name']; ?></h1>
        <div class="card" style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <?php foreach($subjects as $sub) echo "<th>{$sub['subject_code']}</th>"; ?>
                        <th>Average</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $sid => $data): ?>
                    <tr>
                        <td style="font-weight:bold;"><?php echo $data['name']; ?></td>
                        <?php 
                        $total = 0; $count = 0;
                        foreach($subjects as $sub){
                            $m = isset($data['marks'][$sub['subject_id']]) ? $data['marks'][$sub['subject_id']] : '-';
                            echo "<td style='text-align:center;'>$m</td>";
                            if(is_numeric($m)){ $total += $m; $count++; }
                        }
                        $avg = $count > 0 ? number_format($total/$count, 1) : '-';
                        ?>
                        <td style="font-weight:bold; color:#b8860b; text-align:center;"><?php echo $avg; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body></html>