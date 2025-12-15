<?php
// Get current page name to highlight the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="brand">
        <i class="fas fa-chalkboard-teacher"
            style="font-size: 2.5rem; color: #FFD700; margin-bottom: 10px; display:block;"></i>
        <h3 style="margin:0; font-size:1.1rem; text-transform:uppercase; letter-spacing:1px; color:#2c3e50;">
            Teacher <span style="color:#FFD700;">Portal</span>
        </h3>
    </div>

    <ul class="menu">
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="my_subjects.php" class="<?php echo $current_page == 'my_subjects.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-open"></i>
                <span>My Subjects</span>
            </a>
        </li>
        <li>
            <a href="student_list.php" class="<?php echo $current_page == 'student_list.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-open"></i>
                <span>Students List</span>
            </a>
        </li>

        <li>
            <a href="manage_marks.php" class="<?php echo $current_page == 'manage_marks.php' ? 'active' : ''; ?>">
                <i class="fas fa-star-half-alt"></i>
                <span>Manage Marks</span>
            </a>
        </li>

        <li style="margin-top: 20px; border-top: 1px solid #f0f0f0;">
            <a href="../logout.php" style="color: #e74c3c;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>