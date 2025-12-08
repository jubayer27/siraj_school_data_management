<?php
session_start();
include '../config/db.php';
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

if(isset($_POST['add_subject'])){
    $name = $_POST['subject_name'];
    $code = $_POST['subject_code'];
    $tid = $_POST['teacher_id'];
    $cid = $_POST['class_id'];

    $conn->query("INSERT INTO subjects (subject_name, subject_code, teacher_id, class_id) VALUES ('$name', '$code', $tid, $cid)");
}

// Fetch lists for dropdowns
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('subject_teacher', 'class_teacher')");
$classes_list = $conn->query("SELECT class_id, class_name FROM classes");

include 'includes/header.php';
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Manage Subjects</h1>
        <div class="card">
            <h3>Add Subject to Class</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="subject_name" placeholder="Subject Name" required>
                </div>
                <div class="form-group">
                    <input type="text" name="subject_code" placeholder="Subject Code (Unique)" required>
                </div>
                <div class="form-group">
                    <select name="class_id" required>
                        <option value="">Select Class</option>
                        <?php while($c = $classes_list->fetch_assoc()): ?>
                            <option value="<?php echo $c['class_id']; ?>"><?php echo $c['class_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="teacher_id" required>
                        <option value="">Select Teacher</option>
                        <?php 
                        // Reset pointer for second loop if needed, or re-fetch. 
                        // For simplicity, fetching directly in loop or array is better, but here we re-loop.
                        $teachers->data_seek(0);
                        while($t = $teachers->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $t['user_id']; ?>"><?php echo $t['full_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="add_subject" class="btn btn-primary">Create Subject</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>