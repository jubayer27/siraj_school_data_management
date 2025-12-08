<?php
include '../config/db.php';
include 'includes/header.php';
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Global Marks Management</h1>
        <p>Select a class subject to audit or modify marks.</p>
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px;">
            <?php
            $classes = $conn->query("SELECT * FROM classes");
            while($c = $classes->fetch_assoc()){
                echo "<div class='card'>";
                echo "<h3>{$c['class_name']}</h3><ul style='list-style:none; padding:0;'>";
                $subs = $conn->query("SELECT * FROM subjects WHERE class_id = {$c['class_id']}");
                while($s = $subs->fetch_assoc()){
                    echo "<li style='padding:8px 0; border-bottom:1px dashed #eee; display:flex; justify-content:space-between;'>";
                    echo "<span>{$s['subject_name']}</span>";
                    // Using a shared public marks manager
                    echo "<a href='../subjectTeacher/manage_marks.php?subject_id={$s['subject_id']}&admin_override=true' class='btn btn-primary' style='padding:5px 10px; font-size:0.8rem;'>Edit</a>";
                    echo "</li>";
                }
                echo "</ul></div>";
            }
            ?>
        </div>
    </div>
</div>
</body></html>