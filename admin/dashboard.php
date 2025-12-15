<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. FETCH LIVE STATISTICS
$stats = [
    'students' => $conn->query("SELECT count(*) as c FROM students")->fetch_assoc()['c'],
    'teachers' => $conn->query("SELECT count(*) as c FROM users WHERE role != 'admin'")->fetch_assoc()['c'],
    'classes' => $conn->query("SELECT count(*) as c FROM classes")->fetch_assoc()['c'],
    'subjects' => $conn->query("SELECT count(*) as c FROM subjects")->fetch_assoc()['c']
];

// 3. FETCH RECENT ADMISSIONS
$recent_students = $conn->query("SELECT s.*, c.class_name FROM students s 
                                 LEFT JOIN classes c ON s.class_id = c.class_id 
                                 ORDER BY s.student_id DESC LIMIT 5");

// 4. FETCH RECENT NOTICES
$recent_notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 4");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    /* Layout Overrides */
    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 0 !important;
        display: block !important;
    }

    .container-fluid {
        padding: 30px !important;
    }

    /* DASHBOARD CARDS */
    .stat-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
        overflow: hidden;
        position: relative;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon-bg {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 5rem;
        opacity: 0.1;
        transform: rotate(-15deg);
    }

    .gradient-1 {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .gradient-2 {
        background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
        color: #333;
    }

    .gradient-3 {
        background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%);
        color: #555;
    }

    .gradient-4 {
        background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        color: #333;
    }

    /* QUICK ACTIONS */
    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 20px 10px;
        text-decoration: none;
        color: #555;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        height: 100%;
    }

    .action-btn i {
        font-size: 1.8rem;
        margin-bottom: 10px;
        color: #DAA520;
    }

    .action-btn:hover {
        background: #fffcf0;
        border-color: #DAA520;
        transform: translateY(-3px);
    }

    .action-btn span {
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* DATA HUB (Export/Import) */
    .data-hub-card {
        background: #2c3e50;
        color: white;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .btn-glass {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 30px;
        padding: 8px 20px;
        text-decoration: none;
        transition: 0.2s;
        font-size: 0.9rem;
    }

    .btn-glass:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #FFD700;
    }

    /* TABLE STYLES */
    .custom-table thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #eee;
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #777;
    }

    .custom-table td {
        vertical-align: middle;
        font-size: 0.95rem;
    }

    @media (max-width: 992px) {
        .main-content {
            width: 100% !important;
            margin-left: 0 !important;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Admin Dashboard</h2>
                    <p class="text-secondary mb-0">System Overview & Controls</p>
                </div>
                <div class="d-none d-md-block">
                    <span class="badge bg-white text-dark border p-2 shadow-sm rounded-pill">
                        <i class="far fa-calendar-alt text-warning me-2"></i> <?php echo date('l, d F Y'); ?>
                    </span>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card gradient-1 p-4 h-100">
                        <div class="position-relative z-1">
                            <h2 class="fw-bold mb-0"><?php echo $stats['students']; ?></h2>
                            <p class="mb-0 opacity-75">Total Students</p>
                        </div>
                        <i class="fas fa-user-graduate stat-icon-bg"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card gradient-2 p-4 h-100">
                        <div class="position-relative z-1">
                            <h2 class="fw-bold mb-0"><?php echo $stats['teachers']; ?></h2>
                            <p class="mb-0 opacity-75">Active Teachers</p>
                        </div>
                        <i class="fas fa-chalkboard-teacher stat-icon-bg"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card gradient-3 p-4 h-100">
                        <div class="position-relative z-1">
                            <h2 class="fw-bold mb-0"><?php echo $stats['classes']; ?></h2>
                            <p class="mb-0 opacity-75">Active Classes</p>
                        </div>
                        <i class="fas fa-school stat-icon-bg"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card gradient-4 p-4 h-100">
                        <div class="position-relative z-1">
                            <h2 class="fw-bold mb-0"><?php echo $stats['subjects']; ?></h2>
                            <p class="mb-0 opacity-75">Subjects</p>
                        </div>
                        <i class="fas fa-book-open stat-icon-bg"></i>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="data-hub-card shadow-sm">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3 p-3 rounded bg-white bg-opacity-10 text-warning">
                                <i class="fas fa-database fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Data Command Center</h5>
                                <p class="mb-0 small opacity-75">Manage bulk data operations securely.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="export_data.php?type=students" class="btn-glass"><i
                                    class="fas fa-file-download me-2"></i> Export Students</a>
                            <a href="export_data.php?type=marks" class="btn-glass"><i
                                    class="fas fa-file-excel me-2"></i> Export Marks</a>
                            <a href="import_data.php" class="btn-glass bg-warning text-dark border-0 fw-bold"><i
                                    class="fas fa-file-upload me-2"></i> Bulk Import</a>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold text-dark mb-3 ps-1">Quick Actions</h5>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="manage_students.php" class="action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Student</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="manage_users.php" class="action-btn">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Add Teacher</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="manage_classes.php" class="action-btn">
                        <i class="fas fa-layer-group"></i>
                        <span>Classes</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="manage_subjects.php" class="action-btn">
                        <i class="fas fa-book"></i>
                        <span>Subjects</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="manage_notices.php" class="action-btn">
                        <i class="fas fa-bullhorn"></i>
                        <span>Notices</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="settings.php" class="action-btn">
                        <i class="fas fa-cogs"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-uppercase"><i class="fas fa-clock text-warning me-2"></i> New
                                Admissions</h6>
                            <a href="manage_students.php" class="btn btn-sm btn-light text-primary fw-bold">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table custom-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Student Name</th>
                                        <th>Class</th>
                                        <th>Reg No</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($stu = $recent_students->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark">
                                                <?php $img = $stu['photo'] ? "../uploads/" . $stu['photo'] : "https://ui-avatars.com/api/?name=" . $stu['student_name']; ?>
                                                <img src="<?php echo $img; ?>" width="30" height="30"
                                                    class="rounded-circle me-2">
                                                <?php echo $stu['student_name']; ?>
                                            </td>
                                            <td><span
                                                    class="badge bg-light text-dark border"><?php echo $stu['class_name'] ?? 'Unassigned'; ?></span>
                                            </td>
                                            <td class="font-monospace text-muted"><?php echo $stu['school_register_no']; ?>
                                            </td>
                                            <td><span class="badge bg-success-subtle text-success">Active</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-uppercase"><i class="fas fa-bell text-danger me-2"></i> Notice
                                Board</h6>
                            <a href="manage_notices.php" class="btn btn-sm btn-light text-primary fw-bold">Post New</a>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php if ($recent_notices->num_rows > 0): ?>
                                <?php while ($n = $recent_notices->fetch_assoc()): ?>
                                    <div class="list-group-item px-4 py-3 border-bottom-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 fw-bold text-dark"><?php echo $n['title']; ?></h6>
                                            <small class="text-muted"
                                                style="font-size:0.7rem;"><?php echo date('M d', strtotime($n['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1 small text-secondary text-truncate" style="max-width: 250px;">
                                            <?php echo $n['message']; ?>
                                        </p>
                                        <small class="badge bg-light text-secondary border">
                                            <?php echo ucfirst($n['type']); ?>
                                        </small>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted">No active notices.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>