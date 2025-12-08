<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

if(!isset($_GET['subject_id'])){ echo "<script>window.location='manage_subjects.php';</script>"; exit(); }
$sid = $_GET['subject_id'];

// 1. HANDLE UPDATE
if(isset($_POST['update_subject'])){
    $name = $_POST['subject_name'];
    $code = $_POST['subject_code'];
    $cid = $_POST['class_id'];
    $tid = $_POST['teacher_id'];

    $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, subject_code=?, class_id=?, teacher_id=? WHERE subject_id=?");
    $stmt->bind_param("ssiii", $name, $code, $cid, $tid, $sid);
    
    if($stmt->execute()){
        $success = "Subject updated successfully.";
        // Auto-redirect back to view page
        echo "<script>setTimeout(function(){ window.location='view_subject.php?subject_id=$sid'; }, 1000);</script>";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// 2. FETCH DATA
$sub = $conn->query("SELECT * FROM subjects WHERE subject_id = $sid")->fetch_assoc();
if(!$sub) die("Subject not found.");

// Fetch Dropdowns
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$teachers = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY full_name");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Edit Subject</h1>
                <p>Updating details for: <strong><?php echo $sub['subject_name']; ?></strong></p>
            </div>
            <a href="view_subject.php?subject_id=<?php echo $sid; ?>" class="btn btn-secondary" style="background:#e0e0e0; color:#333;">Cancel</a>
        </div>

        <?php if(isset($success)) echo "<div class='alert-box success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert-box error'>$error</div>"; ?>

        <div class="card" style="max-width: 700px;">
            <form method="POST">
                <div class="section-title">Curriculum Details</div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Subject Name</label>
                        <input type="text" name="subject_name" value="<?php echo $sub['subject_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject Code</label>
                        <input type="text" name="subject_code" value="<?php echo $sub['subject_code']; ?>" required>
                    </div>
                </div>

                <div class="section-title" style="margin-top:30px;">Allocations</div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Assign to Class</label>
                        <select name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php 
                            $classes->data_seek(0);
                            while($c = $classes->fetch_assoc()): 
                                $sel = ($c['class_id'] == $sub['class_id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $c['class_id']; ?>" <?php echo $sel; ?>>
                                    <?php echo $c['class_name']; ?> (<?php echo $c['year']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Assign Teacher</label>
                        <select name="teacher_id" required>
                            <option value="">-- Select Teacher --</option>
                            <?php 
                            $teachers->data_seek(0);
                            while($t = $teachers->fetch_assoc()): 
                                $sel = ($t['user_id'] == $sub['teacher_id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $t['user_id']; ?>" <?php echo $sel; ?>>
                                    <?php echo $t['full_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_subject" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .alert-box { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
    .alert-box.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .alert-box.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    
    .section-title { font-size: 1.1rem; color: #DAA520; font-weight: 600; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-actions { margin-top: 30px; text-align: right; border-top: 1px solid #f0f0f0; padding-top: 20px; }
    .btn-lg { padding: 12px 30px; font-size: 1rem; }
    
    @media(max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
</style>
</body>
</html>