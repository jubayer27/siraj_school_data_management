<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

// 2. HANDLE DELETE
if(isset($_GET['delete_id'])){
    $did = $_GET['delete_id'];
    
    // Optional: Delete photo file
    $img_q = $conn->query("SELECT photo FROM students WHERE student_id = $did");
    $img = $img_q->fetch_assoc();
    if($img['photo'] && file_exists("../uploads/".$img['photo'])){
        unlink("../uploads/".$img['photo']);
    }

    $conn->query("DELETE FROM students WHERE student_id = $did");
    echo "<script>window.location='manage_students.php?msg=deleted';</script>";
}

// 3. HANDLE QUICK REGISTRATION
if(isset($_POST['add_student'])){
    $name = $_POST['student_name'];
    $reg_no = $_POST['school_register_no'];
    $ic = $_POST['ic_no'];
    $gender = $_POST['gender'];
    $cid = $_POST['class_id'];
    $enroll_date = date('Y-m-d'); // Default to today

    // Check Duplicate ID
    $chk = $conn->query("SELECT student_id FROM students WHERE school_register_no = '$reg_no'");
    if($chk->num_rows > 0){
        $error = "Student ID '$reg_no' already exists.";
    } else {
        // Handle Photo
        $photo_name = "";
        if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
            $target_dir = "../uploads/";
            if(!is_dir($target_dir)) mkdir($target_dir);
            $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            $photo_name = uniqid("stu_") . "." . $ext;
            move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo_name);
        }

        // Insert Basic Info (Full info can be added in Edit Page)
        $stmt = $conn->prepare("INSERT INTO students (student_name, school_register_no, ic_no, gender, class_id, photo, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $name, $reg_no, $ic, $gender, $cid, $photo_name, $enroll_date);
        
        if($stmt->execute()){
            $success = "Student registered successfully!";
            echo "<script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

// 4. STATISTICS
$total_students = $conn->query("SELECT count(*) as c FROM students")->fetch_assoc()['c'];
$boys = $conn->query("SELECT count(*) as c FROM students WHERE gender = 'Male'")->fetch_assoc()['c'];
$girls = $conn->query("SELECT count(*) as c FROM students WHERE gender = 'Female'")->fetch_assoc()['c'];

// 5. FILTER & SEARCH LOGIC
$filter_class = isset($_GET['class_filter']) ? $_GET['class_filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT s.*, c.class_name 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        WHERE 1=1";

if($filter_class) $sql .= " AND s.class_id = $filter_class";
if($search) $sql .= " AND (s.student_name LIKE '%$search%' OR s.school_register_no LIKE '%$search%' OR s.ic_no LIKE '%$search%')";

$sql .= " ORDER BY s.student_id DESC";
$students = $conn->query($sql);

// Fetch Classes for Dropdown
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Student Directory</h1>
                <p>Manage admissions, profiles, and class assignments.</p>
            </div>
            <button onclick="toggleForm()" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Register Student
            </button>
        </div>

        <?php if(isset($success)) echo "<div class='alert-box success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert-box error'>$error</div>"; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted') echo "<div class='alert-box error'>Student record deleted.</div>"; ?>

        <div class="stats-grid">
            <div class="card stat-card">
                <div class="stat-icon" style="background: rgba(255, 215, 0, 0.15); color: #DAA520;">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3><?php echo $total_students; ?></h3>
                    <span>Total Students</span>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon" style="background: #e3f2fd; color: #1e88e5;">
                    <i class="fas fa-male"></i>
                </div>
                <div>
                    <h3><?php echo $boys; ?></h3>
                    <span>Boys</span>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon" style="background: #fce4ec; color: #d81b60;">
                    <i class="fas fa-female"></i>
                </div>
                <div>
                    <h3><?php echo $girls; ?></h3>
                    <span>Girls</span>
                </div>
            </div>
        </div>

        <div class="card" id="addStudentForm" style="display:none; border-top: 5px solid #FFD700;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="margin:0;">Quick Registration</h3>
                <button onclick="toggleForm()" style="border:none; background:none; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="form-grid">
                <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" name="student_name" required>
                </div>
                <div class="form-group">
                    <label>Register No / Student ID</label>
                    <input type="text" name="school_register_no" required>
                </div>
                
                <div class="form-group">
                    <label>Class Assignment</label>
                    <select name="class_id">
                        <option value="">-- Select Class --</option>
                        <?php 
                        $classes->data_seek(0);
                        while($c = $classes->fetch_assoc()) echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>"; 
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>IC / Birth Cert No</label>
                    <input type="text" name="ic_no">
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Photo</label>
                    <input type="file" name="photo" accept=".jpg,.png,.jpeg">
                </div>
                
                <div style="grid-column:1/-1; text-align:right; margin-top:10px;">
                    <button type="submit" name="add_student" class="btn btn-primary">Create Profile</button>
                </div>
            </form>
        </div>

        <div class="card filter-bar">
            <form method="GET" style="display:flex; gap:15px; width:100%;">
                <select name="class_filter" style="flex:1;">
                    <option value="">Filter by Class</option>
                    <?php 
                    $classes->data_seek(0);
                    while($c = $classes->fetch_assoc()): 
                        $sel = ($filter_class == $c['class_id']) ? 'selected' : '';
                        echo "<option value='{$c['class_id']}' $sel>{$c['class_name']}</option>";
                    endwhile; 
                    ?>
                </select>
                <input type="text" name="search" placeholder="Search name or ID..." value="<?php echo $search; ?>" style="flex:2;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <?php if($filter_class || $search): ?>
                    <a href="manage_students.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student Profile</th>
                            <th>Class</th>
                            <th>ID / IC</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students->num_rows > 0): ?>
                            <?php while($row = $students->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center;">
                                        <?php 
                                            $avatar = $row['photo'] ? "../uploads/".$row['photo'] : "https://ui-avatars.com/api/?name=".$row['student_name']."&background=f0f0f0&color=333";
                                        ?>
                                        <img src="<?php echo $avatar; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:15px; border:1px solid #eee;">
                                        <div>
                                            <div style="font-weight:600; color:#333;"><?php echo $row['student_name']; ?></div>
                                            <div style="font-size:0.8rem; color:#888;"><?php echo $row['gender']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if($row['class_name']): ?>
                                        <span class="badge-class"><?php echo $row['class_name']; ?></span>
                                    <?php else: ?>
                                        <span class="badge-none">No Class</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size:0.9rem; font-weight:500;"><?php echo $row['school_register_no']; ?></div>
                                    <div style="font-size:0.8rem; color:#888;"><?php echo $row['ic_no']; ?></div>
                                </td>
                                <td><span style="color:green; font-weight:bold; font-size:0.85rem;">Active</span></td>
                                <td style="text-align:right;">
                                    <a href="view_student.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-sm btn-primary" title="View Profile" style="background:#5bc0de;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_student.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-sm btn-primary" title="Edit Info" style="background:#f0ad4e;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_students.php?delete_id=<?php echo $row['student_id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this student? This will also remove their marks and enrollment history.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="empty-table">No students found matching your criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleForm() {
    var x = document.getElementById("addStudentForm");
    if (x.style.display === "none") {
        x.style.display = "block";
        x.scrollIntoView({behavior: "smooth"});
    } else {
        x.style.display = "none";
    }
}
</script>

<style>
    /* Stats */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .stat-card { display: flex; align-items: center; gap: 20px; padding: 25px; }
    .stat-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-card h3 { margin: 0; font-size: 1.8rem; color: #333; }
    .stat-card span { color: #888; font-size: 0.9rem; }

    /* Alerts */
    .alert-box { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
    .alert-box.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .alert-box.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

    /* Filters & Badges */
    .filter-bar { padding: 20px; background: #fff; margin-bottom: 20px; }
    .badge-class { background: #fff8e1; color: #b8860b; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.85rem; }
    .badge-none { background: #f0f0f0; color: #999; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; }
    
    .empty-table { text-align: center; padding: 40px; color: #999; font-style: italic; }
    
    /* Form */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media(max-width: 700px) { .form-grid { grid-template-columns: 1fr; } }
</style>
</body>
</html>