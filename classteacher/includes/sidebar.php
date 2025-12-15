<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<div class="sidebar">
    <div class="brand">
        <i class="fas fa-chalkboard-teacher"
            style="font-size: 2rem; color: #FFD700; margin-bottom: 10px; display:block;"></i>
        <h3 style="margin:0; font-size:1.1rem; text-transform:uppercase; color:#2c3e50;">
            Class <span style="color:#FFD700;">Mentor</span>
        </h3>
    </div>

    <ul class="menu">
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">MY CLASS (Home Room)</li>
        <li>
            <a href="my_class.php" class="<?php echo $current_page == 'my_class.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> <span>Class</span>
            </a>
        </li>
        <li>
            <a href="my_class_students.php"
                class="<?php echo $current_page == 'my_class_students.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> <span>Student List</span>
            </a>
        </li>

        <li>
            <a href="assign_subjects.php" class="<?php echo $current_page == 'assign_subjects.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i> <span>Subject Enrollment</span>
            </a>
        </li>

        <li>
            <a href="my_subject_teachers.php"
                class="<?php echo $current_page == 'my_subject_teachers.php' ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard"></i> <span>Subject Teachers</span>
            </a>
        </li>

        <li>
            <a href="class_performance.php"
                class="<?php echo $current_page == 'class_performance.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> <span>Class Performance</span>
            </a>
        </li>

        <li>
            <a href="bulk_reports.php" target="_blank"
                class="<?php echo $current_page == 'bulk_reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-print"></i> <span>Print Reports (Bulk)</span>
            </a>
        </li>

        <?php if (isset($cid) && $cid): ?>
            <li>
                <a href="master_marksheet.php?class_id=<?php echo $cid; ?>" target="_blank">
                    <i class="fas fa-table"></i> <span>Master Marksheet</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="menu-header">MY TEACHING</li>

        <li>
            <a href="my_subjects.php" class="<?php echo $current_page == 'my_subjects.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-open"></i> <span>My Subjects</span>
            </a>
        </li>
        <li>
            <a href="grade_book.php" class="<?php echo $current_page == 'grade_book.php' ? 'active' : ''; ?>">
                <i class="fas fa-pen-alt"></i> <span>Grade Book</span>
            </a>
        </li>

        <li style="margin-top: 20px; border-top: 1px solid #eee;">
            <a href="../logout.php" style="color: #e74c3c;">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<style>
    /* Section Headers for Sidebar */
    .menu-header {
        padding: 15px 25px 5px 25px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>