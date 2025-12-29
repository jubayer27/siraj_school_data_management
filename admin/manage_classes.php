<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

// 2. HANDLE DELETE
if(isset($_GET['delete_id'])){
    $did = intval($_GET['delete_id']);
    
    // Check if class has students
    $check_stu = $conn->query("SELECT count(*) as c FROM students WHERE class_id = $did")->fetch_assoc();
    
    if($check_stu['c'] > 0){
        $error = "Cannot delete class. It currently has <strong>".$check_stu['c']."</strong> active students. Please reassign them first.";
    } else {
        $del = $conn->query("DELETE FROM classes WHERE class_id = $did");
        if($del){
            echo "<script>window.location='manage_classes.php?msg=deleted';</script>";
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// 3. HANDLE CREATE NEW CLASS
if(isset($_POST['create_class'])){
    $name = $_POST['class_name'];
    $year = $_POST['year'];
    // Handle optional teacher ID correctly
    $tid = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : NULL;
    
    // Check duplicate
    $dup = $conn->query("SELECT class_id FROM classes WHERE class_name = '$name' AND year = '$year'");
    if($dup->num_rows > 0){
        $error = "A class with this name and year already exists.";
    } else {
        // Prepare statement based on whether Teacher ID is provided (to handle NULL safely)
        if($tid){
            $stmt = $conn->prepare("INSERT INTO classes (class_name, year, class_teacher_id) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $name, $year, $tid);
        } else {
            $stmt = $conn->prepare("INSERT INTO classes (class_name, year, class_teacher_id) VALUES (?, ?, NULL)");
            $stmt->bind_param("si", $name, $year);
        }
        
        if($stmt->execute()){
            $msg = "New class created successfully!";
            // Clear post to prevent resubmission
            echo "<script>setTimeout(function(){ window.location='manage_classes.php'; }, 1000);</script>";
        } else {
            $error = "Error creating class: " . $conn->error;
        }
    }
}

// 4. FETCH DATA
// Teachers for Dropdown
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role='class_teacher' ORDER BY full_name ASC");

// Classes List with Stats
$sql = "SELECT c.*, u.full_name, u.avatar,
        (SELECT COUNT(*) FROM students WHERE class_id = c.class_id) as student_count,
        (SELECT COUNT(*) FROM subjects WHERE class_id = c.class_id) as subject_count
        FROM classes c 
        LEFT JOIN users u ON c.class_teacher_id = u.user_id 
        ORDER BY c.year DESC, c.class_name ASC";
$classes = $conn->query($sql);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important; margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important; display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Cards */
    .manage-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .card-header-custom { background: white; padding: 20px 25px; border-bottom: 1px solid #f0f0f0; }

    /* Teacher Avatar */
    .avatar-sm { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 8px; border: 1px solid #eee; }

    /* Table */
    .table-hover tbody tr:hover { background-color: #ffffed; }
    .table td { vertical-align: middle; }
    
    /* Form */
    .form-control:focus, .form-select:focus { border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.15); }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Manage Classes</h2>
                    <p class="text-secondary mb-0">Academic Structure & Allocations</p>
                </div>
            </div>

            <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted'): ?>
                <div class="alert alert-success d-flex align-items-center"><i class="fas fa-trash-alt me-2"></i> Class deleted successfully.</div>
            <?php endif; ?>
            <?php if(isset($msg)): ?>
                <div class="alert alert-success d-flex align-items-center"><i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row g-4">
                
                <div class="col-lg-4">
                    <div class="card manage-card h-100">
                        <div class="card-header-custom">
                            <h5 class="fw-bold m-0 text-dark"><i class="fas fa-plus-circle text-warning me-2"></i> Create New Class</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Class Name</label>
                                    <input type="text" name="class_name" class="form-control" placeholder="e.g. 5 Amanah" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Academic Year</label>
                                    <input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Assign Class Mentor</label>
                                    <select name="teacher_id" class="form-select">
                                        <option value="">-- Select Teacher --</option>
                                        <?php 
                                        $teachers->data_seek(0);
                                        while($t = $teachers->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $t['user_id']; ?>"><?php echo $t['full_name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text">Optional. Can be assigned later.</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="create_class" class="btn btn-warning fw-bold text-dark py-2">
                                        <i class="fas fa-save me-2"></i> Create Class
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card manage-card h-100">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold m-0 text-dark">Active Classes Directory</h5>
                            <span class="badge bg-light text-dark border"><?php echo $classes->num_rows; ?> Classes</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Class Name</th>
                                            <th>Year</th>
                                            <th>Class Mentor</th>
                                            <th class="text-center">Students</th>
                                            <th class="text-center">Subjects</th>
                                            <th class="text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($classes->num_rows > 0): ?>
                                            <?php while($row = $classes->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-dark fs-6"><?php echo $row['class_name']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning text-dark"><?php echo $row['year']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if($row['full_name']): ?>
                                                        <div class="d-flex align-items-center">
                                                            <?php $avatar = $row['avatar'] ? "../uploads/".$row['avatar'] : "https://ui-avatars.com/api/?name=".$row['full_name']."&background=random"; ?>
                                                            <img src="<?php echo $avatar; ?>" class="avatar-sm">
                                                            <span class="small fw-semibold"><?php echo $row['full_name']; ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary small">Unassigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3">
                                                        <?php echo $row['student_count']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="small text-muted"><?php echo $row['subject_count']; ?></span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="view_class.php?class_id=<?php echo $row['class_id']; ?>" 
                                                       class="btn btn-outline-info btn-sm me-1" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_class.php?class_id=<?php echo $row['class_id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm me-1" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="manage_classes.php?delete_id=<?php echo $row['class_id']; ?>" 
                                                       class="btn btn-outline-danger btn-sm" 
                                                       onclick="return confirm('WARNING: Are you sure? This action cannot be undone.');"
                                                       title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center py-5 text-muted">No classes found. Create one to get started.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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