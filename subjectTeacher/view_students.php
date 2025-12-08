<?php
include '../config/db.php';
include 'includes/header.php';

$subject_id = $_GET['subject_id'];

// Get students enrolled in this subject via enrollment table
$sql = "SELECT st.student_id, st.student_name, st.school_register_no, st.gender 
        FROM student_subject_enrollment sse
        JOIN students st ON sse.student_id = st.student_id
        WHERE sse.subject_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Enrolled Students</h1>
        <a href="my_subjects.php" class="btn btn-primary">Back</a>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Reg No</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['school_register_no']; ?></td>
                        <td><?php echo $row['student_name']; ?></td>
                        <td><?php echo $row['gender']; ?></td>
                        <td>
                            <a href="view_profile.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-primary">View Profile</a>
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