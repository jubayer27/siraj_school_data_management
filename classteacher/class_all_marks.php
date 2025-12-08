<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

$teacher_id = $_SESSION['user_id'];
$class_id = $conn->query("SELECT class_id FROM classes WHERE class_teacher_id = $teacher_id")->fetch_assoc()['class_id'];

// Get all marks for students in this class
$sql = "SELECT st.student_name, sub.subject_name, sm.exam_type, sm.mark_obtained, sm.grade
        FROM student_marks sm
        JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id
        JOIN students st ON sse.student_id = st.student_id
        JOIN subjects sub ON sse.subject_id = sub.subject_id
        WHERE st.class_id = $class_id
        ORDER BY st.student_name, sub.subject_name";
        
$result = $conn->query($sql);
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Class Academic Performance</h1>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Exam</th>
                        <th>Mark</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:bold;"><?php echo $row['student_name']; ?></td>
                        <td><?php echo $row['subject_name']; ?></td>
                        <td><?php echo $row['exam_type']; ?></td>
                        <td><?php echo $row['mark_obtained']; ?></td>
                        <td>
                            <span style="padding:4px 8px; background:#eee; border-radius:4px;">
                                <?php echo $row['grade']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>