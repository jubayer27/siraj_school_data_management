<?php $page = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <div class="brand">
        <h3>Admin Portal</h3>
    </div>
    <ul class="menu">
        <li><a href="dashboard.php" class="<?php echo $page=='dashboard.php'?'active':''; ?>">
            <i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="manage_users.php" class="<?php echo $page=='manage_users.php'?'active':''; ?>">
            <i class="fas fa-users-cog"></i> Users & Staff</a></li>
        <li><a href="manage_classes.php" class="<?php echo $page=='manage_classes.php'?'active':''; ?>">
            <i class="fas fa-chalkboard"></i> Classes</a></li>
        <li><a href="manage_subjects.php" class="<?php echo $page=='manage_subjects.php'?'active':''; ?>">
            <i class="fas fa-book"></i> Subjects</a></li>
        <li><a href="manage_students.php" class="<?php echo $page=='manage_students.php'?'active':''; ?>">
            <i class="fas fa-user-graduate"></i> Students</a></li>
        <li><a href="manage_all_marks.php" class="<?php echo $page=='manage_all_marks.php'?'active':''; ?>">
            <i class="fas fa-star"></i> Marks Mgmt</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>