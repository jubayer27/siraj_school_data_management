<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

// 2. HANDLE DELETION
if(isset($_GET['delete_id'])){
    $did = $_GET['delete_id'];
    $del = $conn->query("DELETE FROM subjects WHERE subject_id = $did");
    if($del) echo "<script>window.location='manage_subjects.php?msg=deleted';</script>";
    else $error = "Error: Could not delete subject.";
}

// 3. HANDLE ADD SUBJECT
if(isset($_POST['add_subject'])){
    $name = $_POST['subject_name'];
    $code = $_POST['subject_code'];
    $cid = $_POST['class_id'];
    $tid = $_POST['teacher_id'];

    // Check Duplicate Code
    $chk = $conn->query("SELECT subject_id FROM subjects WHERE subject_code = '$code'");
    if($chk->num_rows > 0){
        $error = "Subject Code '$code' already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id, teacher_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $name, $code, $cid, $tid);
        if($stmt->execute()){
            $success = "Subject created successfully!";
            echo "<script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// 4. STATISTICS QUERIES
$stats_total = $conn->query("SELECT count(*) as c FROM subjects")->fetch_assoc()['c'];
$stats_no_teacher = $conn->query("SELECT count(*) as c FROM subjects WHERE teacher_id IS NULL OR teacher_id = 0")->fetch_assoc()['c'];
$stats_active = $conn->query("SELECT count(DISTINCT class_id) as c FROM subjects")->fetch_assoc()['c'];

// 5. FILTER LOGIC
$filter_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$filter_teacher = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT s.*, c.class_name, u.full_name as teacher_name,
        (SELECT COUNT(*) FROM student_subject_enrollment WHERE subject_id = s.subject_id) as enrolled_count
        FROM subjects s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        LEFT JOIN users u ON s.teacher_id = u.user_id 
        WHERE 1=1";

if($filter_class) $sql .= " AND s.class_id = $filter_class";
if($filter_teacher) $sql .= " AND s.teacher_id = $filter_teacher";
if($search) $sql .= " AND (s.subject_name LIKE '%$search%' OR s.subject_code LIKE '%$search%')";

$sql .= " ORDER BY c.class_name ASC, s.subject_name ASC";
$subjects = $conn->query($sql);

// Fetch Dropdowns
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$teachers = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY full_name");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Subject Management</h1>
                <p>Curriculum planning and teacher allocations.</p>
            </div>
            <button onclick="toggleForm()" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Subject
            </button>
        </div>

        <?php if(isset($success)) echo "<div class='alert-box success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert-box error'>$error</div>"; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted') echo "<div class='alert-box error'>Subject deleted permanently.</div>"; ?>

        <div class="stats-grid">
            <div class="card stat-card">
                <div class="stat-icon" style="background: rgba(255, 215, 0, 0.15); color: #DAA520;">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <h3><?php echo $stats_total; ?></h3>
                    <span>Total Subjects</span>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon" style="background: rgba(231, 76, 60, 0.15); color: #c0392b;">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div>
                    <h3><?php echo $stats_no_teacher; ?></h3>
                    <span>Unassigned (No Teacher)</span>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.15); color: #2980b9;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h3><?php echo $stats_active; ?></h3>
                    <span>Active Classes</span>
                </div>
            </div>
        </div>

        <div class="card" id="addSubjectForm" style="display:none; border-top: 5px solid #FFD700;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="margin:0;">Add New Curriculum</h3>
                <button onclick="toggleForm()" style="border:none; background:none; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="form-grid">
                <div class="form-group">
                    <label>Subject Name</label>
                    <input type="text" name="subject_name" placeholder="e.g. Advanced Mathematics" required>
                </div>
                <div class="form-group">
                    <label>Subject Code</label>
                    <input type="text" name="subject_code" placeholder="e.g. MTH-501" required>
                </div>
                <div class="form-group">
                    <label>Assign to Class</label>
                    <select name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php 
                        $classes->data_seek(0);
                        while($c = $classes->fetch_assoc()) echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>"; 
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assign Teacher</label>
                    <select name="teacher_id" required>
                        <option value="">-- Select Teacher --</option>
                        <?php 
                        $teachers->data_seek(0);
                        while($t = $teachers->fetch_assoc()) echo "<option value='{$t['user_id']}'>{$t['full_name']}</option>"; 
                        ?>
                    </select>
                </div>
                <div style="grid-column:1/-1; text-align:right; margin-top:10px;">
                    <button type="submit" name="add_subject" class="btn btn-primary">Save Subject</button>
                </div>
            </form>
        </div>

        <div class="card filter-bar">
            <form method="GET" style="display:flex; gap:15px; width:100%; align-items:center;">
                <div style="flex:1;">
                    <select name="class_id">
                        <option value="">Filter by Class</option>
                        <?php 
                        $classes->data_seek(0);
                        while($c = $classes->fetch_assoc()): 
                            $sel = ($filter_class == $c['class_id']) ? 'selected' : '';
                            echo "<option value='{$c['class_id']}' $sel>{$c['class_name']}</option>";
                        endwhile; 
                        ?>
                    </select>
                </div>
                <div style="flex:1;">
                    <select name="teacher_id">
                        <option value="">Filter by Teacher</option>
                        <?php 
                        $teachers->data_seek(0);
                        while($t = $teachers->fetch_assoc()): 
                             $sel = ($filter_teacher == $t['user_id']) ? 'selected' : '';
                            echo "<option value='{$t['user_id']}' $sel>{$t['full_name']}</option>";
                        endwhile; 
                        ?>
                    </select>
                </div>
                <div style="flex:2;">
                    <input type="text" name="search" placeholder="Search subject name or code..." value="<?php echo $search; ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                <?php if($filter_class || $filter_teacher || $search): ?>
                    <a href="manage_subjects.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Class Assigned</th>
                            <th>Teacher Assigned</th>
                            <th>Enrolled</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($subjects->num_rows > 0): ?>
                            <?php while($row = $subjects->fetch_assoc()): ?>
                            <tr>
                                <td><span class="code-badge"><?php echo $row['subject_code']; ?></span></td>
                                <td style="font-weight:600; color:#333;"><?php echo $row['subject_name']; ?></td>
                                <td>
                                    <?php if($row['class_name']): ?>
                                        <a href="view_class.php?class_id=<?php echo $row['class_id']; ?>" style="text-decoration:none; color:#DAA520; font-weight:500;">
                                            <?php echo $row['class_name']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#999;">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['teacher_name']): ?>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <div style="width:25px; height:25px; background:#f0f0f0; border-radius:50%; display:flex; justify-content:center; align-items:center; font-size:0.7rem; color:#888;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <a href="view_user.php?user_id=<?php echo $row['teacher_id']; ?>" style="text-decoration:none; color:#555;">
                                                <?php echo $row['teacher_name']; ?>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge-warning">No Teacher</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="background:#e3f2fd; color:#1565c0; padding:2px 8px; border-radius:10px; font-size:0.8rem; font-weight:bold;">
                                        <?php echo $row['enrolled_count']; ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <a href="edit_subject.php?subject_id=<?php echo $row['subject_id']; ?>" class="btn btn-sm btn-primary" title="Edit" style="background:#f0ad4e;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_subjects.php?delete_id=<?php echo $row['subject_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this subject? This will remove all associated marks and enrollment records.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="empty-table">No subjects found matching your criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleForm() {
    var x = document.getElementById("addSubjectForm");
    if (x.style.display === "none") {
        x.style.display = "block";
        x.scrollIntoView({behavior: "smooth"});
    } else {
        x.style.display = "none";
    }
}
</script>

<style>
    /* Custom page styles */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .stat-card { display: flex; align-items: center; gap: 20px; padding: 25px; }
    .stat-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-card h3 { margin: 0; font-size: 1.8rem; color: #333; }
    .stat-card span { color: #888; font-size: 0.9rem; }
    
    .filter-bar { padding: 20px; background: #fff; border-bottom: 2px solid #f0f0f0; margin-bottom: 20px; }
    .filter-bar select, .filter-bar input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 100%; outline: none; }
    .filter-bar select:focus, .filter-bar input:focus { border-color: #DAA520; }
    
    .code-badge { font-family: 'Courier New', monospace; background: #333; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; letter-spacing: 1px; }
    .badge-warning { background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
    .empty-table { text-align: center; padding: 40px; color: #aaa; font-style: italic; }
    
    .alert-box { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
    .alert-box.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .alert-box.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media(max-width: 700px) { .form-grid { grid-template-columns: 1fr; } }
</style>
</body>
</html>