<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

$teacher_id = $_SESSION['user_id'];
$class_id = $conn->query("SELECT class_id FROM classes WHERE class_teacher_id = $teacher_id")->fetch_assoc()['class_id'];

// Query: Join Subjects and Users to find teachers for this class
$sql = "SELECT s.subject_name, s.subject_code, u.full_name, u.phone 
        FROM subjects s 
        LEFT JOIN users u ON s.teacher_id = u.user_id 
        WHERE s.class_id = $class_id";
$result = $conn->query($sql);
?>

<div class="wrapper">
    <?php include 'includes/sidebar_classteacher.php'; ?>
    <div class="main-content">
        <h1>Subject Teachers for My Class</h1>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Code</th>
                        <th>Teacher Name</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['subject_name']; ?></td>
                            <td><?php echo $row['subject_code']; ?></td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['phone'] ? $row['phone'] : 'N/A'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No subjects assigned to this class yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>