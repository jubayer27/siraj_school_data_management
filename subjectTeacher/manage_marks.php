<?php
include '../config/db.php';
include 'includes/header.php';

$sid = $_GET['subject_id'];
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Security Check: Is this user the teacher of this subject OR an Admin?
$subCheck = $conn->query("SELECT * FROM subjects WHERE subject_id = $sid")->fetch_assoc();
if($role != 'admin' && $subCheck['teacher_id'] != $uid){
    echo "<script>alert('Unauthorized'); window.location='dashboard.php';</script>"; exit;
}

// Handle Save
if(isset($_POST['save_marks'])){
    foreach($_POST['marks'] as $enrollment_id => $mark){
        $mark = (float)$mark;
        $max = 100;
        // Basic Grading Logic
        if($mark >= 80) $g = 'A'; elseif($mark >= 65) $g = 'B'; elseif($mark >= 50) $g = 'C'; else $g = 'F';

        // Upsert Logic
        $check = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $enrollment_id AND exam_type = 'Midterm'");
        if($check->num_rows > 0){
            $conn->query("UPDATE student_marks SET mark_obtained=$mark, grade='$g' WHERE enrollment_id=$enrollment_id AND exam_type='Midterm'");
        } else {
            $conn->query("INSERT INTO student_marks (enrollment_id, exam_type, mark_obtained, max_mark, grade) VALUES ($enrollment_id, 'Midterm', $mark, $max, '$g')");
        }
    }
    $success = "Marks saved successfully!";
}

// Fetch Students
$sql = "SELECT st.student_name, st.school_register_no, sse.enrollment_id, sm.mark_obtained 
        FROM student_subject_enrollment sse 
        JOIN students st ON sse.student_id = st.student_id 
        LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_type = 'Midterm'
        WHERE sse.subject_id = $sid ORDER BY st.student_name";
$students = $conn->query($sql);
?>
<div class="wrapper">
    <?php 
        if($role == 'admin') include '../admin/includes/sidebar.php';
        else include 'includes/sidebar.php'; 
    ?>
    <div class="main-content">
        <h1>Grading: <?php echo $subCheck['subject_name']; ?></h1>
        <?php if(isset($success)) echo "<div style='padding:15px; background:#d4edda; color:#155724; border-radius:5px; margin-bottom:20px;'>$success</div>"; ?>
        
        <form method="POST">
        <div class="card">
            <table>
                <thead><tr><th>Register No</th><th>Name</th><th>Mark (0-100)</th></tr></thead>
                <tbody>
                    <?php while($s = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $s['school_register_no']; ?></td>
                        <td><?php echo $s['student_name']; ?></td>
                        <td>
                            <input type="number" step="0.01" name="marks[<?php echo $s['enrollment_id']; ?>]" 
                                   value="<?php echo $s['mark_obtained']; ?>" 
                                   style="width:100px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="margin-top:20px; text-align:right;">
                <button type="submit" name="save_marks" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
        </form>
    </div>
</div>
</body></html>