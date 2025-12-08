<?php
include '../config/db.php';
include 'includes/header.php';

// 1. CHECK ID
if(!isset($_GET['class_id'])){
    echo "<script>window.location='manage_classes.php';</script>";
    exit();
}

$cid = $_GET['class_id'];

// 2. HANDLE UPDATE
if(isset($_POST['update_class'])){
    $name = $_POST['class_name'];
    $year = $_POST['year'];
    $tid = $_POST['teacher_id'];

    $stmt = $conn->prepare("UPDATE classes SET class_name=?, year=?, class_teacher_id=? WHERE class_id=?");
    $stmt->bind_param("siii", $name, $year, $tid, $cid);

    if($stmt->execute()){
        // Redirect back to list with success message
        echo "<script>window.location='manage_classes.php?msg=updated';</script>";
        exit();
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
    echo "<div class='main-content'><h3>Class not found.</h3></div>";
    exit();
}

// 4. FETCH TEACHERS FOR DROPDOWN
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role='class_teacher'");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Edit Class Details</h1>
                <p>Updating: <strong><?php echo $class['class_name']; ?></strong></p>
            </div>
            <a href="manage_classes.php" class="btn btn-secondary" style="background:#ddd; color:#333;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if(isset($error)) echo "<div style='padding:15px; background:#ffebee; color:#c62828; border-radius:5px; margin-bottom:20px;'>$error</div>"; ?>

        <div class="card" style="max-width: 600px;">
            <form method="POST">
                <div class="form-group">
                    <label>Class Name</label>
                    <input type="text" name="class_name" value="<?php echo $class['class_name']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="number" name="year" value="<?php echo $class['year']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Assigned Class Teacher</label>
                    <select name="teacher_id" required>
                        <option value="">-- Select Teacher --</option>
                        <?php while($t = $teachers->fetch_assoc()): ?>
                            <option value="<?php echo $t['user_id']; ?>" 
                                <?php echo ($t['user_id'] == $class['class_teacher_id']) ? 'selected' : ''; ?>>
                                <?php echo $t['full_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="margin-top:20px; text-align:right;">
                    <button type="submit" name="update_class" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>