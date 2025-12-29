<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'subject_teacher' && $_SESSION['role'] != 'admin' && $_SESSION['role'] != 'class_teacher') {
    header("Location: ../index.php");
    exit();
}

$tid = $_SESSION['user_id'];

// 2. FETCH KEY STATS
// A. Total Subjects (Updated for junction table)
$sub_count = $conn->query("SELECT count(*) as c FROM subject_teachers WHERE teacher_id = $tid")->fetch_assoc()['c'];

// B. Total Unique Students (Updated logic)
$stu_count = $conn->query("SELECT count(DISTINCT sse.student_id) as c 
                           FROM student_subject_enrollment sse 
                           JOIN subject_teachers st ON sse.subject_id = st.subject_id 
                           WHERE st.teacher_id = $tid")->fetch_assoc()['c'];

// C. Fetch Notices
$notices = $conn->query("SELECT * FROM notices 
                         WHERE audience IN ('all', 'subject_teacher') 
                         ORDER BY created_at DESC LIMIT 3");

// D. Subject Performance & Grading Progress
// Updated query to join subject_teachers
$subjects_sql = "SELECT s.subject_id, s.subject_name, s.subject_code, c.class_name,
                 (SELECT COUNT(*) FROM student_subject_enrollment WHERE subject_id = s.subject_id) as total_students,
                 (SELECT COUNT(*) FROM student_marks sm 
                  JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id 
                  WHERE sse.subject_id = s.subject_id AND sm.exam_type = 'Midterm') as graded_count
                 FROM subjects s 
                 JOIN classes c ON s.class_id = c.class_id 
                 JOIN subject_teachers st ON s.subject_id = st.subject_id
                 WHERE st.teacher_id = $tid
                 ORDER BY c.class_name, s.subject_name";
$my_subjects = $conn->query($subjects_sql);
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <div>
                <h1>Welcome, <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Teacher'; ?></h1>
                <p>Here is your academic overview for today.</p>
            </div>
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, d M Y'); ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card stat-card">
                <div class="icon-box" style="background:#fff8e1; color:#DAA520;">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="text-box">
                    <h3><?php echo $sub_count; ?></h3>
                    <span>Active Subjects</span>
                </div>
            </div>
            <div class="card stat-card">
                <div class="icon-box" style="background:#e3f2fd; color:#1565c0;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="text-box">
                    <h3><?php echo $stu_count; ?></h3>
                    <span>Total Students</span>
                </div>
            </div>
            <div class="card stat-card" onclick="window.location='manage_marks.php'" style="cursor:pointer;">
                <div class="icon-box" style="background:#e8f5e9; color:#2e7d32;">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="text-box">
                    <h3>Grade Now</h3>
                    <span>Manage Marks</span>
                </div>
            </div>
        </div>

        <div class="grid-2-col">

            <div class="card table-card">
                <div class="card-header-row">
                    <h3>My Teaching Load</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Class & Subject</th>
                                <th>Enrollment</th>
                                <th>Midterm Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($my_subjects->num_rows > 0): ?>
                                <?php while ($row = $my_subjects->fetch_assoc()):
                                    $pct = $row['total_students'] > 0 ? round(($row['graded_count'] / $row['total_students']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:#333;"><?php echo $row['subject_name']; ?></div>
                                            <div style="font-size:0.85rem; color:#888;">
                                                <span class="badge-class"><?php echo $row['class_name']; ?></span>
                                                <span style="font-family:monospace;"><?php echo $row['subject_code']; ?></span>
                                            </div>
                                        </td>
                                        <td style="font-weight:bold; text-align:center;"><?php echo $row['total_students']; ?>
                                        </td>
                                        <td>
                                            <div
                                                style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:3px;">
                                                <span>Graded:
                                                    <?php echo $row['graded_count']; ?>/<?php echo $row['total_students']; ?></span>
                                                <strong><?php echo $pct; ?>%</strong>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width:<?php echo $pct; ?>%;"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="manage_marks.php?subject_id=<?php echo $row['subject_id']; ?>"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-pen"></i> Grade
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:20px;">No subjects assigned yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header-row">
                    <h3><i class="fas fa-bell" style="color:#DAA520;"></i> Admin Notices</h3>
                </div>
                <div class="notice-list">
                    <?php if ($notices->num_rows > 0): ?>
                        <?php while ($n = $notices->fetch_assoc()): ?>
                            <div class="notice-item <?php echo $n['type']; ?>">
                                <div class="notice-date"><?php echo date('d M', strtotime($n['created_at'])); ?></div>
                                <div class="notice-content">
                                    <strong><?php echo $n['title']; ?></strong>
                                    <p><?php echo $n['message']; ?></p>
                                    <?php if ($n['event_date']): ?>
                                        <div class="event-tag"><i class="far fa-clock"></i> Event:
                                            <?php echo date('d M Y', strtotime($n['event_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:30px; color:#999;">No new notices.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* CSS Styles included directly for portability */
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
        font-family: 'Segoe UI', sans-serif;
    }

    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 30px !important;
        display: block !important;
    }

    .container-fluid {
        padding: 0 !important;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .date-badge {
        background: white;
        padding: 8px 15px;
        border-radius: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        color: #555;
        font-weight: 500;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .stat-card {
        padding: 20px;
        display: flex;
        align-items: center;
        transition: 0.3s;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        border: none;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 15px;
    }

    .text-box h3 {
        margin: 0;
        font-size: 1.8rem;
        color: #333;
    }

    .text-box span {
        font-size: 0.85rem;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Layout */
    .grid-2-col {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 25px;
    }

    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        border: none;
        overflow: hidden;
    }

    /* Table */
    .table-card {
        padding: 0;
    }

    .card-header-row {
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
        background: #fff;
    }

    .card-header-row h3 {
        margin: 0;
        font-size: 1.1rem;
        color: #2c3e50;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: #f9f9f9;
        padding: 12px 20px;
        text-align: left;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #666;
        font-weight: 600;
    }

    .data-table td {
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .badge-class {
        background: #FFD700;
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
        margin-right: 5px;
    }

    /* Progress Bar */
    .progress-bar {
        width: 100%;
        height: 6px;
        background: #eee;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 5px;
    }

    .progress-fill {
        height: 100%;
        background: #27ae60;
        border-radius: 3px;
        transition: width 0.5s ease;
    }

    /* Notices */
    .notice-list {
        padding: 10px;
        max-height: 400px;
        overflow-y: auto;
    }

    .notice-item {
        display: flex;
        gap: 15px;
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 10px;
        border-radius: 6px;
    }

    .notice-item:last-child {
        border-bottom: none;
    }

    .notice-item.alert {
        background: #fff5f5;
        border-left: 3px solid #e74c3c;
    }

    .notice-item.info {
        background: #f0f8ff;
        border-left: 3px solid #3498db;
    }

    .notice-item.event {
        background: #fffcf0;
        border-left: 3px solid #f1c40f;
    }

    .notice-date {
        font-size: 0.8rem;
        font-weight: bold;
        color: #999;
        width: 40px;
        text-align: center;
        line-height: 1.2;
    }

    .notice-content strong {
        display: block;
        margin-bottom: 5px;
        color: #333;
        font-size: 0.95rem;
    }

    .notice-content p {
        margin: 0;
        font-size: 0.85rem;
        color: #666;
        line-height: 1.4;
    }

    .event-tag {
        margin-top: 5px;
        font-size: 0.75rem;
        color: #d35400;
        background: rgba(230, 126, 34, 0.1);
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
    }

    @media(max-width: 992px) {
        .main-content {
            width: 100% !important;
            margin-left: 0 !important;
        }

        .grid-2-col {
            grid-template-columns: 1fr;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>