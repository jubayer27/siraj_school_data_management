<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar" id="adminSidebar">
    <div class="brand">
        <div class="logo-circle">
            <img src="../assets/siraj-logo.png" alt="School Logo" onerror="this.style.display='none'">
        </div>

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
            <a href="exam_setup.php" class="<?php echo $current_page == 'exam_setup.php' ? 'active' : ''; ?>">
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

        <li class="menu-header">THEME PREFERENCE</li>
        <li style="padding: 10px 25px;">
            <div class="theme-selector">
                <div class="theme-dot default" onclick="setTheme('default')" title="Default Blue"></div>
                <div class="theme-dot dark" onclick="setTheme('dark')" title="Midnight Black"></div>
                <div class="theme-dot purple" onclick="setTheme('purple')" title="Royal Purple"></div>
                <div class="theme-dot teal" onclick="setTheme('teal')" title="Ocean Teal"></div>
            </div>
        </li>

        <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="../logout.php" style="color: #ff6b6b;">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<style>
    /* 1. CSS Variables for Themes */
    :root {
        --sidebar-bg: #2c3e50;
        --sidebar-hover: #34495e;
        --sidebar-active-border: #FFD700;
        --brand-bg: #233242;
    }

    /* Theme Classes (applied by JS) */
    .sidebar.theme-dark {
        --sidebar-bg: #1a1a2e;
        --sidebar-hover: #16213e;
        --brand-bg: #0f3460;
    }

    .sidebar.theme-purple {
        --sidebar-bg: #4a148c;
        --sidebar-hover: #6a1b9a;
        --brand-bg: #38006b;
    }

    .sidebar.theme-teal {
        --sidebar-bg: #00695c;
        --sidebar-hover: #004d40;
        --brand-bg: #003d33;
    }

    /* 2. Admin Sidebar Base Styles */
    .sidebar {
        width: 260px;
        height: 100vh;
        background: var(--sidebar-bg);
        /* Use Variable */
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        overflow-y: auto;
        z-index: 1000;
        transition: background 0.3s ease;
    }

    .brand {
        text-align: center;
        padding: 25px 20px;
        background: var(--brand-bg);
        /* Use Variable */
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* 3. White Circle Logo Styling */
    .logo-circle {
        width: 90px;
        height: 90px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px auto;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .logo-circle img {
        width: 65px;
        /* Adjust based on logo shape */
        height: auto;
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
        background: var(--sidebar-hover);
        /* Use Variable */
        color: white;
    }

    .menu li a.active {
        background: var(--sidebar-hover);
        /* Use Variable */
        color: white;
        border-left-color: var(--sidebar-active-border);
    }

    .menu li a i {
        width: 30px;
        font-size: 1.1rem;
    }

    .menu-header {
        padding: 20px 25px 5px 25px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #92aabf;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    /* 4. Theme Dots Styling */
    .theme-selector {
        display: flex;
        gap: 10px;
    }

    .theme-dot {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid rgba(255, 255, 255, 0.2);
        transition: transform 0.2s;
    }

    .theme-dot:hover {
        transform: scale(1.2);
        border-color: #fff;
    }

    /* Dot Colors */
    .theme-dot.default {
        background: #2c3e50;
    }

    .theme-dot.dark {
        background: #1a1a2e;
    }

    .theme-dot.purple {
        background: #4a148c;
    }

    .theme-dot.teal {
        background: #00695c;
    }

    /* Scrollbar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
</style>

<script>
    // 5. JavaScript to Handle Theme Switching with Memory
    function setTheme(themeName) {
        const sidebar = document.getElementById('adminSidebar');

        // Remove all theme classes first
        sidebar.classList.remove('theme-dark', 'theme-purple', 'theme-teal');

        // Add the specific theme class if not default
        if (themeName !== 'default') {
            sidebar.classList.add('theme-' + themeName);
        }

        // Save preference to LocalStorage (Persistent across refreshes)
        localStorage.setItem('adminTheme', themeName);
    }

    // Immediately load the saved theme when the page loads
    (function () {
        const savedTheme = localStorage.getItem('adminTheme');
        if (savedTheme) {
            setTheme(savedTheme);
        }
    })();
</script>