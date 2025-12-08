<?php
session_start();
include '../config/db.php';
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

// --- 1. HANDLE ADD STUDENT ---
if(isset($_POST['add_student'])){
    $name = $_POST['student_name'];
    $reg_no = $_POST['school_register_no'];
    $ic = $_POST['ic_no'];
    $gender = $_POST['gender'];
    $class_id = $_POST['class_id'];
    
    // Handle Photo Upload
    $photo_name = "";
    if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
        $target_dir = "../uploads/";
        // Create unique name to avoid overwriting
        $photo_name = uniqid() . "_" . basename($_FILES["photo"]["name"]); 
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo_name);
    }

    // Insert into DB
    // Note: This is a simplified insert. In a real app, you'd add all parent details too.
    $sql = "INSERT INTO students (student_name, school_register_no, ic_no, gender, class_id, photo, enrollment_date) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssis", $name, $reg_no, $ic, $gender, $class_id, $photo_name);
    
    if($stmt->execute()){
        $msg = "Student registered successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// --- 2. HANDLE DELETE STUDENT ---
if(isset($_GET['delete_id'])){
    $did = $_GET['delete_id'];
    $conn->query("DELETE FROM students WHERE student_id = $did");
    header("Location: manage_students.php"); // Refresh to clear GET params
    exit();
}

// --- 3. FETCH DATA FOR UI ---
// Get Classes for Dropdown
$classes = $conn->query("SELECT * FROM classes");

// Get Students for List
$students = $conn->query("SELECT s.*, c.class_name 
                          FROM students s 
                          LEFT JOIN classes c ON s.class_id = c.class_id 
                          ORDER BY s.student_id DESC");

include 'includes/header.php';
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Manage Students</h1>
        
        <div class="card">
            <h3>Register New Student</h3>
            <?php if(isset($msg)) echo "<p style='color:green; font-weight:bold;'>$msg</p>"; ?>
            <?php if(isset($error)) echo "<p style='color:red; font-weight:bold;'>$error</p>"; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="student_name" required>
                    </div>
                    <div>
                        <label>Register No / ID</label>
                        <input type="text" name="school_register_no" required>
                    </div>

                    <div>
                        <label>IC / Passport No</label>
                        <input type="text" name="ic_no" required>
                    </div>
                    <div>
                        <label>Gender</label>
                        <select name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div>
                        <label>Assign Class</label>
                        <select name="class_id">
                            <option value="">-- No Class Assigned --</option>
                            <?php 
                            // Loop through classes
                            if($classes->num_rows > 0){
                                while($c = $classes->fetch_assoc()){
                                    echo "<option value='".$c['class_id']."'>".$c['class_name']." (".$c['year'].")</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Student Photo</label>
                        <input type="file" name="photo" accept="image/*">
                    </div>
                </div>
                <button type="submit" name="add_student" class="btn btn-primary" style="margin-top:20px;">Register Student</button>
            </form>
        </div>

        <div class="card">
            <h3>Student Directory</h3>
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Register No</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Enroll Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($students->num_rows > 0): ?>
                        <?php while($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php 
                                    $img_path = $row['photo'] ? "../uploads/".$row['photo'] : "https://via.placeholder.com/40"; 
                                ?>
                                <img src="<?php echo $img_path; ?>" width="40" height="40" style="object-fit:cover; border-radius:4px;">
                            </td>
                            <td><?php echo $row['school_register_no']; ?></td>
                            <td><?php echo $row['student_name']; ?></td>
                            <td>
                                <?php 
                                    echo $row['class_name'] ? "<span style='color:#2980b9; font-weight:bold;'>".$row['class_name']."</span>" : "<span style='color:red;'>Unassigned</span>"; 
                                ?>
                            </td>
                            <td><?php echo $row['enrollment_date']; ?></td>
                            <td>
                                <a href="../public/student_view.php?student_id=<?php echo $row['student_id']; ?>" class="btn btn-primary">View Profile</a>
                                <a href="manage_students.php?delete_id=<?php echo $row['student_id']; ?>" class="btn btn-danger" style="font-size:0.8em;" onclick="return confirm('Are you sure you want to remove this student?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No students found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>