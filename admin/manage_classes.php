<?php
include '../config/db.php';
include 'includes/header.php';

// Add Class
if(isset($_POST['add_class'])){
    $name = $_POST['class_name'];
    $year = $_POST['year'];
    $tid = $_POST['teacher_id'];
    $conn->query("INSERT INTO classes (class_name, year, class_teacher_id) VALUES ('$name', '$year', $tid)");
}
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Manage Classes</h1>
        <div class="card">
            <form method="POST" style="display:flex; gap:10px;">
                <input type="text" name="class_name" placeholder="Class Name (e.g. 5 Science)" required>
                <input type="number" name="year" value="2025" required style="width:100px;">
                <select name="teacher_id" required>
                    <option value="">Assign Teacher</option>
                    <?php 
                    $teachers = $conn->query("SELECT * FROM users WHERE role='class_teacher'");
                    while($t = $teachers->fetch_assoc()) echo "<option value='{$t['user_id']}'>{$t['full_name']}</option>";
                    ?>
                </select>
                <button name="add_class" class="btn btn-primary">Create</button>
            </form>
        </div>
        <div class="card">
            <table>
                <thead><tr><th>ID</th><th>Class Name</th><th>Year</th><th>Class Teacher</th></tr></thead>
                <tbody>
                    <?php
                    $classes = $conn->query("SELECT c.*, u.full_name FROM classes c LEFT JOIN users u ON c.class_teacher_id = u.user_id");
                    while($row = $classes->fetch_assoc()){
                        echo "<tr><td>{$row['class_id']}</td><td>{$row['class_name']}</td><td>{$row['year']}</td><td>{$row['full_name']}</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body></html>