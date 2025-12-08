<?php
include '../config/db.php';
include 'includes/header.php';
$tid = $_SESSION['user_id'];

// Get Class
$myClass = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $tid")->fetch_assoc();
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Class Teacher Panel</h1>
        <?php if($myClass): ?>
            <div class="card" style="border-left: 5px solid #FFD700;">
                <h2>Class: <?php echo $myClass['class_name']; ?></h2>
                <p>Academic Year: <?php echo $myClass['year']; ?></p>
            </div>
            <div style="display:flex; gap:20px;">
                 <a href="my_class_students.php" class="card" style="flex:1; text-align:center; text-decoration:none; color:inherit;">
                     <i class="fas fa-user-graduate" style="font-size:30px; color:#b8860b;"></i>
                     <h3>View Students</h3>
                 </a>
                 <a href="class_marksheet.php" class="card" style="flex:1; text-align:center; text-decoration:none; color:inherit;">
                     <i class="fas fa-file-invoice" style="font-size:30px; color:#b8860b;"></i>
                     <h3>Full Marksheet</h3>
                 </a>
            </div>
        <?php else: ?>
            <div class="card"><p>You are not assigned to a class yet.</p></div>
        <?php endif; ?>
    </div>
</div>
</body></html>