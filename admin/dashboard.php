<?php
include '../config/db.php';
include 'includes/header.php';

// --- 1. FETCH LIVE STATISTICS ---
$stats = [
    'students' => $conn->query("SELECT count(*) as c FROM students")->fetch_assoc()['c'],
    'teachers' => $conn->query("SELECT count(*) as c FROM users WHERE role != 'admin'")->fetch_assoc()['c'],
    'classes'  => $conn->query("SELECT count(*) as c FROM classes")->fetch_assoc()['c'],
    'subjects' => $conn->query("SELECT count(*) as c FROM subjects")->fetch_assoc()['c']
];

// --- 2. FETCH RECENT DATA ---
// Recent Students
$recent_students = $conn->query("SELECT s.*, c.class_name FROM students s 
                                 LEFT JOIN classes c ON s.class_id = c.class_id 
                                 ORDER BY s.student_id DESC LIMIT 5");

// Recent Notices
$recent_notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 4");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Overview of school performance and daily activities.</p>
            </div>
            <div style="font-size:0.9rem; color:#888; background:white; padding:10px 20px; border-radius:30px; border:1px solid #ddd;">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, d F Y'); ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card stat-card">
                <div class="stat-icon" style="background: rgba(255, 215, 0, 0.15); color: #DAA520;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['students']; ?></h3>
                    <span>Total Students</span>
                </div>
            </div>
            
            <div class="card stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.15); color: #27ae60;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['teachers']; ?></h3>
                    <span>Active Teachers</span>
                </div>
            </div>

            <div class="card stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.15); color: #2980b9;">
                    <i class="fas fa-school"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['classes']; ?></h3>
                    <span>Total Classes</span>
                </div>
            </div>

            <div class="card stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.15); color: #8e44ad;">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['subjects']; ?></h3>
                    <span>Subjects</span>
                </div>
            </div>
        </div>

        <h3 class="section-title">Quick Actions</h3>
        <div class="action-grid">
            <a href="manage_students.php" class="action-btn">
                <i class="fas fa-plus-circle"></i> Add Student
            </a>
            <a href="manage_users.php" class="action-btn">
                <i class="fas fa-user-plus"></i> Add Teacher
            </a>
            <a href="manage_notices.php" class="action-btn">
                <i class="fas fa-bullhorn"></i> Post Notice
            </a>
            <a href="manage_all_marks.php" class="action-btn">
                <i class="fas fa-star-half-alt"></i> Manage Marks
            </a>
            <a href="manage_classes.php" class="action-btn">
                <i class="fas fa-layer-group"></i> Create Class
            </a>
        </div>

        <div class="content-split">
            
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3>New Admissions</h3>
                    <a href="manage_students.php" style="font-size:0.85rem; color:#DAA520; text-decoration:none;">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Reg No</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($stu = $recent_students->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:600; color:#444;">
                                    <i class="fas fa-user-circle" style="color:#ddd; margin-right:5px;"></i>
                                    <?php echo $stu['student_name']; ?>
                                </td>
                                <td>
                                    <?php if($stu['class_name']): ?>
                                        <span class="badge badge-gold"><?php echo $stu['class_name']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $stu['school_register_no']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3>Notice Board</h3>
                    <a href="manage_notices.php" style="font-size:0.85rem; color:#DAA520; text-decoration:none;">View All</a>
                </div>
                <ul class="notice-list">
                    <?php if($recent_notices->num_rows > 0): ?>
                        <?php while($note = $recent_notices->fetch_assoc()): ?>
                        <li class="notice-item">
                            <div class="notice-icon">
                                <?php 
                                    if($note['type']=='alert') echo '<i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i>';
                                    elseif($note['type']=='event') echo '<i class="fas fa-calendar-check" style="color:#27ae60;"></i>';
                                    else echo '<i class="fas fa-info-circle" style="color:#3498db;"></i>';
                                ?>
                            </div>
                            <div class="notice-content">
                                <h4 class="notice-title"><?php echo $note['title']; ?></h4>
                                <span class="notice-date"><?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li style="color:#999; text-align:center; padding:20px;">No recent notices.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

    </div>
</div>

<style>
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        display: flex;
        align-items: center;
        padding: 25px;
        border-left: 5px solid transparent;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-5px); border-left-color: #FFD700; }
    .stat-icon {
        width: 60px; height: 60px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        margin-right: 20px;
    }
    .stat-info h3 { font-size: 1.8rem; margin: 0; color: #333; }
    .stat-info span { color: #888; font-size: 0.9rem; }

    /* Quick Actions */
    .section-title { margin-bottom: 15px; font-weight: 600; color: #555; }
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .action-btn {
        background: white;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        color: #555;
        border: 1px solid #eee;
        transition: 0.3s;
        font-weight: 500;
        display: flex; flex-direction: column; align-items: center; gap: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .action-btn i { font-size: 1.5rem; color: #DAA520; }
    .action-btn:hover {
        background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
        color: white;
        border-color: transparent;
        transform: translateY(-2px);
    }
    .action-btn:hover i { color: white; }

    /* Content Split */
    .content-split {
        display: grid;
        grid-template-columns: 2fr 1fr; /* Table takes more space */
        gap: 25px;
    }
    @media (max-width: 900px) { .content-split { grid-template-columns: 1fr; } }

    /* Badges */
    .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
    .badge-gold { background: #fff8e1; color: #b8860b; border: 1px solid #ffe082; }
    .badge-gray { background: #f0f0f0; color: #888; }

    /* Notices */
    .notice-list { list-style: none; padding: 0; margin: 0; }
    .notice-item {
        display: flex; align-items: flex-start;
        padding: 15px 0;
        border-bottom: 1px dashed #eee;
    }
    .notice-item:last-child { border-bottom: none; }
    .notice-icon { margin-right: 15px; font-size: 1.1rem; margin-top: 2px; }
    .notice-title { margin: 0 0 5px 0; font-size: 0.95rem; font-weight: 600; color: #444; }
    .notice-date { font-size: 0.8rem; color: #999; }
</style>
</body>
</html>