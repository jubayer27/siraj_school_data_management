<?php
session_start();
include '../config/db.php';
if($_SESSION['role'] != 'class_teacher') header("Location: ../index.php");
include 'includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Get Class ID
$class_q = $conn->query("SELECT class_id, class_name FROM classes WHERE class_teacher_id = $teacher_id");
$class_data = $class_q->fetch_assoc();
$class_id = $class_data['class_id'];

// Get Students
$students = $conn->query("SELECT * FROM students WHERE class_id = $class_id");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>My Class List: <?php echo $class_data['class_name']; ?></h1>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Parent Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php $pic = $row['photo'] ? "../uploads/".$row['photo'] : "https://via.placeholder.com/40"; ?>
                            <img src="<?php echo $pic; ?>" width="40" height="40" style="border-radius:50%;">
                        </td>
                        <td><?php echo $row['student_name']; ?></td>
                        <td><?php echo $row['gender']; ?></td>
                        <td><?php echo $row['father_phone']; ?></td>
                        <td>
                            <a href="view_profile.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-primary">Profile</a>
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