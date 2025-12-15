<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="brand">
        <i class="fas fa-user-shield" style="font-size: 2rem; color: #FFD700; margin-bottom: 10px; display:block;"></i>
        <h3 style="margin:0; font-size:1.1rem; text-transform:uppercase; color:#fff;">
            Admin <span style="color:#FFD700;">Portal</span>
        </h3>
    </div>

    <ul class="menu">
        <li>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">ACADEMIC & CURRICULUM</li>

        <li>
            <a href="manage_classes.php"
                class="<?php echo in_array($current_page, ['manage_classes.php', 'edit_class.php', 'view_class.php']) ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard"></i> <span>Classes</span>
            </a>
        </li>

        <li>
            <a href="manage_subjects.php"
                class="<?php echo in_array($current_page, ['manage_subjects.php', 'edit_subject.php', 'view_subject.php']) ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> <span>Subjects</span>
            </a>
        </li>

        <li>
            <a href="manage_assignments.php"
                class="<?php echo $current_page == 'manage_assignments.php' ? 'active' : ''; ?>">
                <i class="fas fa-link"></i> <span>Bulk Assign</span>
            </a>
        </li>

        <li class="menu-header">PEOPLE DIRECTORY</li>

        <li>
            <a href="manage_students.php"
                class="<?php echo in_array($current_page, ['manage_students.php', 'edit_student.php', 'view_student.php']) ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> <span>Students</span>
            </a>
        </li>

        <li>
            <a href="manage_users.php"
                class="<?php echo in_array($current_page, ['manage_users.php', 'edit_user.php', 'view_user.php']) ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard-teacher"></i> <span>Staff & Users</span>
            </a>
        </li>

        <li class="menu-header">EXAMS & RESULTS</li>

        <li>
            <a href="manage_exams.php" class="<?php echo $current_page == 'manage_exams.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-signature"></i> <span>Exam Setup</span>
            </a>
        </li>

        <li>
            <a href="manage_all_marks.php"
                class="<?php echo $current_page == 'manage_all_marks.php' ? 'active' : ''; ?>">
                <i class="fas fa-star-half-alt"></i> <span>Marks Master</span>
            </a>
        </li>

        <li class="menu-header">SYSTEM & DATA</li>

        <li>
            <a href="manage_notices.php" class="<?php echo $current_page == 'manage_notices.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> <span>Notice Board</span>
            </a>
        </li>

        <li>
            <a href="import_data.php" class="<?php echo $current_page == 'import_data.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-import"></i> <span>Import Data</span>
            </a>
        </li>

        <li>
            <a href="export_data.php" class="<?php echo $current_page == 'export_data.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-export"></i> <span>Export Data</span>
            </a>
        </li>

        <li>
            <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cogs"></i> <span>Settings</span>
            </a>
        </li>

        <li style="margin-top: 20px; border-top: 1px solid #3d4b60;">
            <a href="../logout.php" style="color: #ff6b6b;">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<style>
    /* Admin Sidebar Specific Styles */
    .sidebar {
        width: 260px;
        height: 100vh;
        background: #2c3e50;
        /* Dark Blue-Grey Theme */
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        overflow-y: auto;
        z-index: 1000;
        transition: all 0.3s;
    }

    .brand {
        text-align: center;
        padding: 30px 20px;
        background: #233242;
        /* Slightly darker header */
        border-bottom: 1px solid #3d4b60;
    }

    .menu {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }

    .menu li a {
        display: flex;
        align-items: center;
        padding: 15px 25px;
        color: #b0c4de;
        text-decoration: none;
        transition: 0.3s;
        border-left: 4px solid transparent;
        font-size: 0.95rem;
    }

    .menu li a:hover {
        background: #34495e;
        color: white;
    }

    .menu li a.active {
        background: #34495e;
        color: white;
        border-left-color: #FFD700;
        /* Gold Highlight */
    }

    .menu li a i {
        width: 30px;
        font-size: 1.1rem;
    }

    .menu-header {
        padding: 20px 25px 5px 25px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #5d7694;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    /* Scrollbar for Sidebar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: #3d4b60;
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: #2c3e50;
    }
</style>