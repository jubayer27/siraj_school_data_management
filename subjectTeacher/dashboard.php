<?php
include '../config/db.php';
include 'includes/header.php';
$tid = $_SESSION['user_id'];
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Welcome, <?php echo $_SESSION['full_name']; ?></h1>
        <div class="card">
            <h3>Your Subject Load</h3>
            <table>
                <thead><tr><th>Subject</th><th>Code</th><th>Class</th><th>Students</th></tr></thead>
                <tbody>
                    <?php
                    $sql = "SELECT s.*, c.class_name, (SELECT count(*) FROM student_subject_enrollment WHERE subject_id = s.subject_id) as stu_count 
                            FROM subjects s JOIN classes c ON s.class_id = c.class_id WHERE s.teacher_id = $tid";
                    $res = $conn->query($sql);
                    while($row = $res->fetch_assoc()){
                        echo "<tr><td>{$row['subject_name']}</td><td>{$row['subject_code']}</td><td>{$row['class_name']}</td><td>{$row['stu_count']}</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body></html>