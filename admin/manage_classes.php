<?php
include '../config/db.php';
include 'includes/header.php';

// --- 1. HANDLE DELETE ---
if(isset($_GET['delete_id'])){
    $did = $_GET['delete_id'];
    $del = $conn->query("DELETE FROM classes WHERE class_id = $did");
    if($del){
        echo "<script>window.location='manage_classes.php?msg=deleted';</script>";
    } else {
        $error = "Could not delete class. It might have students assigned.";
    }
}

// --- 2. HANDLE ADD / UPDATE ---
$edit_mode = false;
$edit_data = ['class_name'=>'', 'year'=>date('Y'), 'class_teacher_id'=>'', 'class_id'=>''];

// Check if Edit Button was clicked
if(isset($_GET['edit_id'])){
    $edit_mode = true;
    $eid = $_GET['edit_id'];
    $edit_query = $conn->query("SELECT * FROM classes WHERE class_id = $eid");
    $edit_data = $edit_query->fetch_assoc();
}

if(isset($_POST['save_class'])){
    $name = $_POST['class_name'];
    $year = $_POST['year'];
    $tid = $_POST['teacher_id'];
    
    if(!empty($_POST['class_id'])){
        // UPDATE EXISTING
        $cid = $_POST['class_id'];
        $stmt = $conn->prepare("UPDATE classes SET class_name=?, year=?, class_teacher_id=? WHERE class_id=?");
        $stmt->bind_param("siii", $name, $year, $tid, $cid);
        if($stmt->execute()) $msg = "Class updated successfully!";
        else $error = "Error updating class.";
    } else {
        // CREATE NEW
        $stmt = $conn->prepare("INSERT INTO classes (class_name, year, class_teacher_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $name, $year, $tid);
        if($stmt->execute()) $msg = "New class created successfully!";
        else $error = "Error creating class.";
    }
    // Refresh to clear edit mode
    if(!isset($error)) echo "<script>setTimeout(function(){ window.location='manage_classes.php'; }, 1000);</script>";
}

// --- 3. FETCH DATA ---
// Get Class Teachers for Dropdown
$teachers = $conn->query("SELECT user_id, full_name FROM users WHERE role='class_teacher'");

// Get Classes List with Teacher Name and Student Count
$sql = "SELECT c.*, u.full_name, 
        (SELECT COUNT(*) FROM students WHERE class_id = c.class_id) as student_count 
        FROM classes c 
        LEFT JOIN users u ON c.class_teacher_id = u.user_id 
        ORDER BY c.year DESC, c.class_name ASC";
$classes = $conn->query($sql);
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Manage Classes</h1>
            <p>Create classes, assign teachers, and manage enrollments.</p>
        </div>

        <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted') echo "<div style='padding:15px; background:#ffebee; color:#c62828; border-radius:5px; margin-bottom:20px;'>Class deleted successfully.</div>"; ?>
        <?php if(isset($msg)) echo "<div style='padding:15px; background:#e8f5e9; color:#2e7d32; border-radius:5px; margin-bottom:20px;'>$msg</div>"; ?>
        <?php if(isset($error)) echo "<div style='padding:15px; background:#ffebee; color:#c62828; border-radius:5px; margin-bottom:20px;'>$error</div>"; ?>

        <div class="card">
            <h3 style="margin-top:0; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                <?php echo $edit_mode ? "Edit Class: <span style='color:#DAA520'>".$edit_data['class_name']."</span>" : "Create New Class"; ?>
            </h3>
            
            <form method="POST" class="form-grid" style="display:flex; gap:15px; align-items:flex-end;">
                <input type="hidden" name="class_id" value="<?php echo $edit_data['class_id']; ?>">
                
                <div style="flex:2;">
                    <label>Class Name</label>
                    <input type="text" name="class_name" placeholder="e.g. 5 Science A" value="<?php echo $edit_data['class_name']; ?>" required>
                </div>
                
                <div style="flex:1;">
                    <label>Academic Year</label>
                    <input type="number" name="year" placeholder="2025" value="<?php echo $edit_data['year']; ?>" required>
                </div>
                
                <div style="flex:2;">
                    <label>Assign Class Teacher</label>
                    <select name="teacher_id" required>
                        <option value="">-- Select Teacher --</option>
                        <?php 
                        // Reset pointer for loop
                        $teachers->data_seek(0);
                        while($t = $teachers->fetch_assoc()): 
                            $selected = ($t['user_id'] == $edit_data['class_teacher_id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $t['user_id']; ?>" <?php echo $selected; ?>><?php echo $t['full_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="padding-bottom:1px;">
                    <?php if($edit_mode): ?>
                        <button type="submit" name="save_class" class="btn btn-primary">Update Class</button>
                        <a href="manage_classes.php" class="btn btn-danger" style="background:#888;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="save_class" class="btn btn-primary">Create Class</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Active Classes Directory</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Year</th>
                            <th>Class Teacher</th>
                            <th>Students</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($classes->num_rows > 0): ?>
                            <?php while($row = $classes->fetch_assoc()): ?>
                            <tr>
                                <td>
    <a href="view_class.php?class_id=<?php echo $row['class_id']; ?>" style="font-weight:600; color:#DAA520; text-decoration:none;">
        <?php echo $row['class_name']; ?>
    </a>
</td>
                                <td><span style="background:#f0f0f0; padding:4px 8px; border-radius:4px; font-size:0.85rem;"><?php echo $row['year']; ?></span></td>
                                <td>
                                    <?php 
                                    if($row['full_name']) echo "<i class='fas fa-chalkboard-teacher' style='color:#DAA520; margin-right:5px;'></i> " . $row['full_name'];
                                    else echo "<span style='color:#bbb;'>Unassigned</span>";
                                    ?>
                                </td>
                                <td>
                                    <span style="font-weight:bold;"><?php echo $row['student_count']; ?></span>
                                </td>
                                <td style="text-align:right;">
                                    
                                    
                                    <a href="edit_class.php?class_id=<?php echo $row['class_id']; ?>" class="btn btn-primary btn-sm" title="Edit Class" style="background:#f0ad4e;">
    <i class="fas fa-edit"></i>
</a>
                                    
                                    <a href="manage_classes.php?delete_id=<?php echo $row['class_id']; ?>" class="btn btn-danger btn-sm" title="Delete Class" onclick="return confirm('Are you sure? This will unassign all students in this class.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:20px; color:#888;">No classes found. Please create one above.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>