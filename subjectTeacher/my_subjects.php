<?php
include '../config/db.php';
include 'includes/header.php';
$tid = $_SESSION['user_id'];
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Manage Academic Performance</h1>
        <div class="card">
            <table>
                <thead><tr><th>Subject</th><th>Class</th><th>Action</th></tr></thead>
                <tbody>
                    <?php
                    $sql = "SELECT s.subject_id, s.subject_name, c.class_name FROM subjects s JOIN classes c ON s.class_id = c.class_id WHERE s.teacher_id = $tid";
                    $res = $conn->query($sql);
                    while($row = $res->fetch_assoc()){
                        echo "<tr>
                                <td><strong>{$row['subject_name']}</strong></td>
                                <td>{$row['class_name']}</td>
                                <td>
                                    <a href='manage_marks.php?subject_id={$row['subject_id']}' class='btn btn-primary'>Enter Marks</a>
                                    <a href='view_students.php?subject_id={$row['subject_id']}' class='btn btn-secondary'>Class List</a>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body></html>