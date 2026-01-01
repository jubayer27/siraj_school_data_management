<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if($_SESSION['role'] != 'subject_teacher' && $_SESSION['role'] != 'admin' && $_SESSION['role'] != 'class_teacher'){
    header("Location: ../index.php"); 
    exit(); 
}

$teacher_id = $_SESSION['user_id'];
$alert_msg = "";
$alert_type = "";

// Helper to check subject ownership
function is_authorized($conn, $sid, $tid) {
    // UPDATED: Check subject_teachers table
    $q = $conn->query("SELECT 1 FROM subject_teachers WHERE subject_id = $sid AND teacher_id = $tid");
    return ($q->num_rows > 0);
}

// ==========================================
// 2. EXPORT LOGIC (Download Excel/CSV)
// ==========================================
if(isset($_POST['export_csv'])){
    $sid = intval($_POST['subject_id']);
    $etype = $_POST['exam_type']; // This is exam_id now
    
    // Verify ownership
    if(is_authorized($conn, $sid, $teacher_id)){
        $sub_info = $conn->query("SELECT subject_name, subject_code FROM subjects WHERE subject_id = $sid")->fetch_assoc();
        
        // Fetch Exam Name for filename
        $exam_name_q = $conn->query("SELECT exam_name FROM exam_types WHERE exam_id = '$etype'");
        $exam_name = ($exam_name_q->num_rows > 0) ? $exam_name_q->fetch_assoc()['exam_name'] : 'Exam';
        $safe_exam_name = preg_replace('/[^a-zA-Z0-9]/', '', $exam_name);

        $sql = "SELECT st.school_register_no, st.student_name, sm.mark_obtained 
                FROM students st 
                JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
                LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_id = '$etype'
                WHERE sse.subject_id = $sid
                ORDER BY st.student_name ASC";
        $rows = $conn->query($sql);
        
        $filename = $sub_info['subject_code'] . "_" . $safe_exam_name . "_Marks.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Register No', 'Student Name', 'Mark (0-100)')); // Header Row
        
        while($row = $rows->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
}

// ==========================================
// 3. IMPORT LOGIC (Upload Excel/CSV)
// ==========================================
if(isset($_POST['import_marks']) && isset($_FILES['csv_file'])){
    $sid = intval($_POST['subject_id']);
    $etype = $_POST['exam_type']; // exam_id
    $filename = $_FILES['csv_file']['tmp_name'];
    
    if(is_authorized($conn, $sid, $teacher_id) && $_FILES['csv_file']['size'] > 0){
        $file = fopen($filename, "r");
        $count = 0;
        fgetcsv($file); // Skip Header
        
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $reg_no = $data[0];
            $mark_val = floatval($data[2]);
            if(!is_numeric($data[2])) continue;

            $g = ($mark_val >= 80) ? 'A' : (($mark_val >= 60) ? 'B' : (($mark_val >= 40) ? 'C' : 'F'));

            // Find correct enrollment based on Reg No & Subject
            $find_stu = $conn->query("SELECT sse.enrollment_id 
                                      FROM students st 
                                      JOIN student_subject_enrollment sse ON st.student_id = sse.student_id 
                                      WHERE st.school_register_no = '$reg_no' AND sse.subject_id = $sid");
            
            if($find_stu->num_rows > 0){
                $eid = $find_stu->fetch_assoc()['enrollment_id'];
                
                // Update or Insert using exam_id
                $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_id = '$etype'");
                if($chk->num_rows > 0){
                    $stmt = $conn->prepare("UPDATE student_marks SET mark_obtained=?, grade=? WHERE enrollment_id=? AND exam_id=?");
                    $stmt->bind_param("dsis", $mark_val, $g, $eid, $etype);
                } else {
                    $stmt = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_id, mark_obtained, max_mark, grade) VALUES (?, ?, ?, 100, ?)");
                    $stmt->bind_param("isds", $eid, $etype, $mark_val, $g);
                }
                $stmt->execute();
                $count++;
            }
        }
        fclose($file);
        $alert_msg = "Import Successful! Updated $count marks.";
        $alert_type = "success";
    } else {
        $alert_msg = "Error: Unauthorized subject or empty file.";
        $alert_type = "error";
    }
}

// ==========================================
// 4. MANUAL SAVE LOGIC
// ==========================================
if (isset($_POST['save_marks'])) {
    $sid = intval($_POST['subject_id']);
    $etype = $_POST['exam_type']; // exam_id
    $count = 0;
    
    if(isset($_POST['marks'])){
        foreach ($_POST['marks'] as $eid => $val) {
            if ($val === "") continue;
            $val = floatval($val);
            $g = ($val >= 80) ? 'A' : (($val >= 60) ? 'B' : (($val >= 40) ? 'C' : 'F'));

            $chk = $conn->query("SELECT mark_id FROM student_marks WHERE enrollment_id = $eid AND exam_id = '$etype'");
            if ($chk->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE student_marks SET mark_obtained=?, grade=? WHERE enrollment_id=? AND exam_id=?");
                $stmt->bind_param("dsis", $val, $g, $eid, $etype);
            } else {
                $stmt = $conn->prepare("INSERT INTO student_marks (enrollment_id, exam_id, mark_obtained, max_mark, grade) VALUES (?, ?, ?, 100, ?)");
                $stmt->bind_param("isds", $eid, $etype, $val, $g);
            }
            $stmt->execute();
            $count++;
        }
        $alert_msg = "Saved $count marks successfully.";
        $alert_type = "success";
    }
}

// 5. FETCH SUBJECTS TAUGHT BY THIS TEACHER
// UPDATED: Use subject_teachers join
$sub_query = "SELECT s.subject_id, s.subject_name, c.class_name, c.class_id 
              FROM subjects s 
              JOIN subject_teachers st ON s.subject_id = st.subject_id
              JOIN classes c ON s.class_id = c.class_id 
              WHERE st.teacher_id = $teacher_id 
              ORDER BY c.class_name, s.subject_name";
$my_subjects = $conn->query($sub_query);

// 6. FETCH EXAM TYPES (New Logic)
$exam_types_query = "SELECT * FROM exam_types WHERE status = 'active' ORDER BY created_at DESC";
$exam_types = $conn->query($exam_types_query);

// 7. LOAD SELECTED DATA
$sel_sub = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : '';
$sel_exam = isset($_GET['exam_type']) ? $_GET['exam_type'] : '';

// Auto-select first exam if none selected and exams exist
if (!$sel_exam && $exam_types->num_rows > 0) {
    $first_exam = $exam_types->fetch_assoc();
    $sel_exam = $first_exam['exam_id'];
    $exam_types->data_seek(0); // Reset pointer
}

$students = null;
$stats = ['total'=>0, 'graded'=>0, 'avg'=>0];

if ($sel_sub && $sel_exam) {
    // Security Check
    if(is_authorized($conn, $sel_sub, $teacher_id)){
        $sql = "SELECT st.student_id, st.student_name, st.school_register_no, st.photo, sse.enrollment_id, sm.mark_obtained, sm.grade
                FROM students st
                JOIN student_subject_enrollment sse ON st.student_id = sse.student_id
                LEFT JOIN student_marks sm ON sse.enrollment_id = sm.enrollment_id AND sm.exam_id = '$sel_exam'
                WHERE sse.subject_id = $sel_sub
                ORDER BY st.student_name ASC";
        $students = $conn->query($sql);
        
        if ($students->num_rows > 0) {
            $stats['total'] = $students->num_rows;
            $total_score = 0;
            while($row = $students->fetch_assoc()){
                if($row['mark_obtained'] !== null) {
                    $stats['graded']++;
                    $total_score += $row['mark_obtained'];
                }
            }
            if($stats['graded'] > 0) $stats['avg'] = round($total_score / $stats['graded'], 1);
            $students->data_seek(0);
        }
    }
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important; margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important; display: block !important;
    }
    .container-fluid { padding: 30px !important; }
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    
    /* Stats Cards */
    .stat-card { text-align: center; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .stat-val { font-size: 2rem; font-weight: bold; color: #333; margin-bottom: 5px; }
    .stat-label { font-size: 0.8rem; text-transform: uppercase; color: #888; letter-spacing: 1px; }

    /* Filter Bar */
    .filter-bar { background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-top: 4px solid #FFD700; }

    /* Inputs */
    .mark-input { width: 80px; text-align: center; border: 1px solid #ddd; border-radius: 6px; padding: 8px; font-weight: bold; }
    .mark-input:focus { border-color: #FFD700; outline: none; background: #fffcf5; }

    /* Import Box */
    .import-area { border: 2px dashed #DAA520; background: #fffcf0; padding: 20px; border-radius: 12px; display: none; margin-bottom: 20px; }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">My Grade Book</h2>
                    <p class="text-secondary mb-0">Manage marks for subjects you teach.</p>
                </div>
                
                <?php if($sel_sub): ?>
                <div class="d-flex gap-2">
                    <button onclick="document.getElementById('importArea').style.display='block'" class="btn btn-outline-dark">
                        <i class="fas fa-file-upload me-2"></i> Import
                    </button>
                    
                    <form method="POST" class="m-0">
                        <input type="hidden" name="subject_id" value="<?php echo $sel_sub; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">
                        <button type="submit" name="export_csv" class="btn btn-success text-white">
                            <i class="fas fa-file-csv me-2"></i> Download Excel
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <?php if($alert_msg): ?>
                <div class="alert alert-<?php echo ($alert_type == 'success') ? 'success' : 'danger'; ?> border-0 shadow-sm mb-4">
                    <?php echo $alert_msg; ?>
                </div>
            <?php endif; ?>

            <div class="filter-bar shadow-sm">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-bold text-muted small mb-1">SELECT CLASS & SUBJECT</label>
                        <select name="subject_id" class="form-select form-select-lg" onchange="this.form.submit()">
                            <option value="">-- Choose Subject --</option>
                            <?php 
                            if ($my_subjects->num_rows > 0) {
                                $my_subjects->data_seek(0);
                                while($s = $my_subjects->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $s['subject_id']; ?>" <?php echo ($sel_sub == $s['subject_id']) ? 'selected' : ''; ?>>
                                    <?php echo $s['class_name']; ?> &raquo; <?php echo $s['subject_name']; ?>
                                </option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-muted small mb-1">EXAM TYPE</label>
                        <select name="exam_type" class="form-select form-select-lg" onchange="this.form.submit()">
                            <?php 
                            if ($exam_types->num_rows > 0) {
                                $exam_types->data_seek(0);
                                while ($et = $exam_types->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $et['exam_id']; ?>" <?php echo ($sel_exam == $et['exam_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($et['exam_name']); ?>
                                </option>
                            <?php endwhile; 
                            } else {
                                echo "<option value=''>No Exams Found</option>";
                            } ?>
                        </select>
                    </div>
                </form>
            </div>

            <div id="importArea" class="import-area">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold text-warning mb-0"><i class="fas fa-cloud-upload-alt me-2"></i> Upload Marks</h5>
                    <button type="button" class="btn-close" onclick="document.getElementById('importArea').style.display='none'"></button>
                </div>
                <p class="small text-muted mb-3">Please use the "Download Excel" button first to get the correct format. Fill in the marks and upload it back here.</p>
                
                <form method="POST" enctype="multipart/form-data" class="d-flex gap-3">
                    <input type="hidden" name="subject_id" value="<?php echo $sel_sub; ?>">
                    <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    <button type="submit" name="import_marks" class="btn btn-warning fw-bold">Upload & Process</button>
                </form>
            </div>

            <?php if($sel_sub && $students): ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-val"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-val text-success"><?php echo $stats['graded']; ?></div>
                        <div class="stat-label">Marks Entered</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-val text-warning"><?php echo $stats['avg']; ?></div>
                        <div class="stat-label">Class Average</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Student Roster</h5>
                    <button type="submit" form="gradingForm" name="save_marks" class="btn btn-primary px-4 fw-bold">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
                </div>
                <div class="card-body p-0">
                    <form method="POST" id="gradingForm">
                        <input type="hidden" name="subject_id" value="<?php echo $sel_sub; ?>">
                        <input type="hidden" name="exam_type" value="<?php echo $sel_exam; ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 text-secondary text-uppercase small">Student Name</th>
                                        <th class="text-secondary text-uppercase small">Register No</th>
                                        <th class="text-center text-secondary text-uppercase small">Mark (0-100)</th>
                                        <th class="text-center text-secondary text-uppercase small">Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($students->num_rows > 0): ?>
                                        <?php while($row = $students->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo $row['student_name']; ?></td>
                                            <td class="text-muted font-monospace"><?php echo $row['school_register_no']; ?></td>
                                            <td class="text-center">
                                                <input type="number" step="0.01" min="0" max="100" 
                                                       name="marks[<?php echo $row['enrollment_id']; ?>]" 
                                                       value="<?php echo $row['mark_obtained']; ?>" 
                                                       class="mark-input" placeholder="-">
                                            </td>
                                            <td class="text-center fw-bold text-secondary">
                                                <?php echo $row['grade'] ? $row['grade'] : '-'; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-5 text-muted">No students enrolled in this subject yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php else: ?>
                <div class="card py-5 text-center">
                    <div class="card-body">
                        <i class="fas fa-book-reader fa-3x text-muted mb-3 opacity-25"></i>
                        <h4 class="fw-bold text-secondary">No Subject Selected</h4>
                        <p class="text-muted">Please select a Class, Subject, and Exam Type from the menu above to begin grading.</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>