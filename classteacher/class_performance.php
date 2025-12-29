<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); 
    exit(); 
}

$teacher_id = $_SESSION['user_id'];

// 2. FETCH CLASS INFO
$class_q = $conn->query("SELECT class_id, class_name FROM classes WHERE class_teacher_id = $teacher_id");
$my_class = $class_q->fetch_assoc();
$class_id = $my_class ? $my_class['class_id'] : 0;
$class_name = $my_class ? $my_class['class_name'] : "No Class Assigned";

// 3. FILTER LOGIC
$exam_filter = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// 4. FETCH MARKS
$results = null;
$stats = ['avg' => 0, 'pass' => 0, 'fail' => 0, 'total' => 0];

if($class_id){
    // Query joins students -> enrollment -> marks -> subjects
    // Filters by the student's CURRENT class_id
    $sql = "SELECT st.student_name, st.photo, sub.subject_name, sub.subject_code, sm.exam_type, sm.mark_obtained, sm.grade
            FROM student_marks sm
            JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id
            JOIN students st ON sse.student_id = st.student_id
            JOIN subjects sub ON sse.subject_id = sub.subject_id
            WHERE st.class_id = $class_id";

    if($exam_filter){
        $sql .= " AND sm.exam_type = '$exam_filter'";
    }
    if($search_query){
        $sql .= " AND (st.student_name LIKE '%$search_query%' OR sub.subject_name LIKE '%$search_query%')";
    }

    $sql .= " ORDER BY st.student_name, sub.subject_name";
    
    $results = $conn->query($sql);

    // Calculate Stats on the fly
    $total_score = 0;
    while($row = $results->fetch_assoc()){
        $stats['total']++;
        $total_score += $row['mark_obtained'];
        if($row['mark_obtained'] >= 40) $stats['pass']++; else $stats['fail']++;
    }
    if($stats['total'] > 0) $stats['avg'] = round($total_score / $stats['total'], 1);
    
    // Reset pointer for display
    $results->data_seek(0);
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    /* LAYOUT OVERRIDES */
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important;
        display: block !important;
    }
    
    .container-fluid { padding: 30px !important; }

    /* CUSTOM CARDS */
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: 0.2s; }
    .card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

    .stat-card { display: flex; align-items: center; padding: 20px; }
    .icon-square { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-right: 15px; }

    /* COLORS */
    .bg-blue-soft { background: #e3f2fd; color: #1565c0; }
    .bg-green-soft { background: #e8f5e9; color: #2e7d32; }
    .bg-red-soft { background: #ffebee; color: #c62828; }
    .bg-gold-soft { background: #fff8e1; color: #fbc02d; }

    /* TABLE */
    .avatar-sm { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 1px solid #dee2e6; }
    .table-hover tbody tr:hover { background-color: #fffcf5; }
    
    .grade-badge { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; margin: 0 auto; }
    .grade-A, .grade-B { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .grade-C { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .grade-D, .grade-F { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    @media (max-width: 992px) {
        .main-content { width: 100% !important; margin-left: 0 !important; }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Academic Performance</h2>
                    <p class="text-secondary mb-0">Overview for <strong><?php echo $class_name; ?></strong></p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-dark shadow-sm" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                </div>
            </div>

            <?php if(!$class_id): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                    <div><strong>No Class Assigned.</strong><br>You are not currently listed as a Class Teacher for any active class.</div>
                </div>
            <?php else: ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="icon-square bg-gold-soft"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['avg']; ?></h3>
                            <small class="text-secondary fw-bold">Class Average</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="icon-square bg-blue-soft"><i class="fas fa-clipboard-check"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['total']; ?></h3>
                            <small class="text-secondary fw-bold">Marks Recorded</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="icon-square bg-green-soft"><i class="fas fa-thumbs-up"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-success"><?php echo $stats['pass']; ?></h3>
                            <small class="text-secondary fw-bold">Passed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="icon-square bg-red-soft"><i class="fas fa-exclamation-circle"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0 text-danger"><?php echo $stats['fail']; ?></h3>
                            <small class="text-secondary fw-bold">Needs Improvement</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold mb-1">Filter by Exam</label>
                            <select name="exam_type" class="form-select" onchange="this.form.submit()">
                                <option value="">All Exams</option>
                                <option value="Midterm" <?php echo $exam_filter == 'Midterm' ? 'selected' : ''; ?>>Midterm</option>
                                <option value="Final" <?php echo $exam_filter == 'Final' ? 'selected' : ''; ?>>Final</option>
                                <option value="Quiz 1" <?php echo $exam_filter == 'Quiz 1' ? 'selected' : ''; ?>>Quiz 1</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="small text-muted fw-bold mb-1">Search Student or Subject</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="Type name..." value="<?php echo $search_query; ?>">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-warning w-100 fw-bold shadow-sm">Apply Filters</button>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 text-secondary text-uppercase" style="font-size: 0.75rem;">Student</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Subject</th>
                                    <th class="text-secondary text-uppercase" style="font-size: 0.75rem;">Exam</th>
                                    <th class="text-center text-secondary text-uppercase" style="font-size: 0.75rem;">Mark</th>
                                    <th class="text-center text-secondary text-uppercase" style="font-size: 0.75rem;">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($results && $results->num_rows > 0): ?>
                                    <?php while($row = $results->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <?php $pic = $row['photo'] ? "../uploads/".$row['photo'] : "https://ui-avatars.com/api/?name=".$row['student_name']."&background=random"; ?>
                                                <img src="<?php echo $pic; ?>" class="avatar-sm">
                                                <span class="fw-bold text-dark"><?php echo $row['student_name']; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="d-block fw-bold text-secondary"><?php echo $row['subject_name']; ?></span>
                                            <small class="text-muted font-monospace"><?php echo $row['subject_code']; ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $row['exam_type']; ?></span></td>
                                        <td class="text-center fw-bold"><?php echo $row['mark_obtained']; ?></td>
                                        <td class="text-center">
                                            <?php 
                                            // Determine Grade Color Class
                                            $g = strtoupper($row['grade']);
                                            $badgeClass = ($g == 'A' || $g == 'B') ? 'grade-A' : (($g == 'C') ? 'grade-C' : 'grade-F');
                                            ?>
                                            <div class="grade-badge <?php echo $badgeClass; ?>"><?php echo $g; ?></div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No marks found matching your criteria.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card-footer bg-white py-3">
                    <small class="text-muted">Showing results for <?php echo $class_name; ?></small>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>