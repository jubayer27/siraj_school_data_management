<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); exit();
}

if(!isset($_GET['class_id'])){
    echo "<script>window.location='manage_classes.php';</script>";
    exit();
}

$cid = $_GET['class_id'];
$error = "";
$success = "";

// 2. HANDLE UPDATE
if(isset($_POST['update_class'])){
    $name = $_POST['class_name'];
    $year = $_POST['year'];
    $tid = $_POST['teacher_id'];

    $stmt = $conn->prepare("UPDATE classes SET class_name=?, year=?, class_teacher_id=? WHERE class_id=?");
    $stmt->bind_param("siii", $name, $year, $tid, $cid);

    if($stmt->execute()){
        $success = "Class updated successfully!";
        // Optional: Redirect after delay
        echo "<script>setTimeout(function(){ window.location='manage_classes.php'; }, 1500);</script>";
    } else {
        $error = "Error updating class: " . $conn->error;
    }
}

// 3. FETCH EXISTING DATA
$stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ?");
$stmt->bind_param("i", $cid);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if(!$class){
    echo "<div class='main-content p-5'><div class='alert alert-danger'>Class not found.</div></div>";
    exit();
}

// 4. FETCH TEACHERS
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role='class_teacher'");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    /* Full Width Fix */
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important;
        display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Card Styling */
    .edit-card {
        border: none; border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        max-width: 700px; margin: 0 auto;
    }
    .form-label { font-weight: 600; color: #555; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control, .form-select { padding: 12px 15px; border-radius: 8px; border: 1px solid #dee2e6; }
    .form-control:focus, .form-select:focus { border-color: #FFD700; box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.15); }
    
    /* Header Background */
    .header-bg {
        background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
        height: 180px; width: 100%; border-radius: 0 0 20px 20px;
        position: absolute; top: 0; left: 0; z-index: 0;
    }
    .page-title { position: relative; z-index: 1; color: white; margin-bottom: 30px; }
    .breadcrumb-item a { color: rgba(255,255,255,0.7); text-decoration: none; }
    .breadcrumb-item.active { color: white; }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header-bg"></div>

        <div class="container-fluid position-relative">
            
            <div class="d-flex justify-content-between align-items-center page-title pt-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="dashboard.php">Admin</a></li>
                            <li class="breadcrumb-item"><a href="manage_classes.php">Classes</a></li>
                            <li class="breadcrumb-item active">Edit Class</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Edit Class Details</h2>
                </div>
                <a href="manage_classes.php" class="btn btn-light shadow-sm text-dark fw-bold">
                    <i class="fas fa-arrow-left me-2"></i> Back to List
                </a>
            </div>

            <div class="card edit-card">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h5 class="fw-bold text-dark m-0"><i class="fas fa-pen-square text-warning me-2"></i> Update Information</h5>
                </div>
                
                <div class="card-body p-4">
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($success): ?>
                        <div class="alert alert-success d-flex align-items-center mb-4">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-4">
                            
                            <div class="col-md-8">
                                <label class="form-label">Class Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-layer-group text-secondary"></i></span>
                                    <input type="text" name="class_name" class="form-control" value="<?php echo $class['class_name']; ?>" placeholder="e.g. 5 Amanah" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Academic Year</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-calendar-alt text-secondary"></i></span>
                                    <input type="number" name="year" class="form-control" value="<?php echo $class['year']; ?>" required>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Assigned Class Mentor</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-chalkboard-teacher text-secondary"></i></span>
                                    <select name="teacher_id" class="form-select" required>
                                        <option value="">-- Select Teacher --</option>
                                        <?php 
                                        if ($teachers->num_rows > 0) {
                                            $teachers->data_seek(0); // Reset pointer if reused
                                            while($t = $teachers->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $t['user_id']; ?>" 
                                                <?php echo ($t['user_id'] == $class['class_teacher_id']) ? 'selected' : ''; ?>>
                                                <?php echo $t['full_name']; ?>
                                            </option>
                                        <?php endwhile; } ?>
                                    </select>
                                </div>
                                <div class="form-text">This teacher will have access to the class register and master marksheet.</div>
                            </div>

                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="manage_classes.php" class="btn btn-secondary px-4">Cancel</a>
                            <button type="submit" name="update_class" class="btn btn-warning fw-bold px-4 text-dark">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>