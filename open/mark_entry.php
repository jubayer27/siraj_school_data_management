<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit(); }

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : 0;

// --- 1. SECURITY & PERMISSION CHECK ---
$stmt = $conn->prepare("SELECT * FROM subjects WHERE subject_id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if(!$subject) die("Subject not found.");

// Permission Logic:
// 1. Admin can edit ANY subject.
// 2. Teachers can ONLY edit subjects where teacher_id matches their ID.
if($role != 'admin' && $subject['teacher_id'] != $user_id){
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>⛔ Access Denied: You are not the teacher of this subject.</h2>");
}

// --- 2. HANDLE SAVE MARKS ---
if(isset($_POST['save_marks'])){
    foreach($_POST['marks'] as $enrollment_id => $mark){
        $mark = floatval($mark);
        
        // Auto-Grade Logic (Simple Example)
        $grade = 'F';
        if($mark >= 80) $grade = 'A';
        elseif($mark >= 70) $grade = 'B';
        elseif($mark >= 60) $grade = 'C';
        elseif($mark >= 50) $grade = 'D';
        elseif($mark >= 40) $grade = 'E';

        // Check if mark entry exists
        $check = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $enrollment_id AND exam_type = 'Midterm'");
        
        if($check->num_rows > 0){
            // Update
            $upd = $conn->prepare("UPDATE student_marks SET mark_obtained=?, grade=? WHERE enrollment_id=? AND exam_type='Midterm'");
            $upd->bind_param("dsi", $mark, $grade, $enrollment_id);
            $upd->execute();
        } else {
            // Insert
            $ins = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_type, mark_obtained, max_mark, grade) VALUES (?, 'Midterm', ?, 100, ?)");
            $ins->bind_param("ids", $enrollment_id, $mark, $grade);
            $ins->execute();
        }
    }
    $msg = "Marks updated successfully!";
}

// --- 3. FETCH STUDENTS ENROLLED IN THIS SUBJECT ---
$sql = "SELECT sse.enrollment_id, st.student_name, st.school_register_no, sm.mark_obtained, sm.grade 
        FROM student_subject_enrollment sse
        JOIN students st ON sse.student_id = st.student_id
        LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = 'Midterm'
        WHERE sse.subject_id = ?
        ORDER BY st.student_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();

// Load Header based on role
if($role == 'admin') include '../admin/includes/header.php';
else include '../includes/header.php';
?>

<div class="wrapper">
    <?php 
        if($role == 'admin') include '../admin/includes/sidebar.php';
        elseif($role == 'class_teacher') include '../includes/sidebar_classteacher.php';
        else include '../includes/sidebar.php';
    ?>

    <div class="main-content">
        <h1>Mark Entry: <?php echo $subject['subject_name']; ?></h1>
        <p>Exam Type: <strong>Midterm</strong> (Max: 100)</p>
        
        <div class="card">
            <?php if(isset($msg)) echo "<div style='padding:10px; background:#d4edda; color:#155724; margin-bottom:15px;'>$msg</div>"; ?>
            
            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Student Name</th>
                            <th>Mark (0-100)</th>
                            <th>Current Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['school_register_no']; ?></td>
                                <td><?php echo $row['student_name']; ?></td>
                                <td>
                                    <input type="number" step="0.1" min="0" max="100" 
                                           name="marks[<?php echo $row['enrollment_id']; ?>]" 
                                           value="<?php echo $row['mark_obtained']; ?>" 
                                           style="width:100px;">
                                </td>
                                <td><?php echo $row['grade'] ? $row['grade'] : '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No students enrolled in this subject.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="margin-top:20px; text-align:right;">
                    <button type="submit" name="save_marks" class="btn btn-success">Save All Marks</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>