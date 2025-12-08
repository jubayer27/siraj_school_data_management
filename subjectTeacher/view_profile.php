<?php
include '../config/db.php';
include 'includes/header.php';

$sid = $_GET['student_id'];
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Student Profile</h1>
        <button onclick="history.back()" class="btn btn-primary">Back</button>
        
        <div class="card">
            <div style="display:flex; gap: 30px;">
                <div style="width: 200px;">
                     <?php 
                        $photo = $student['photo'] ? "../uploads/".$student['photo'] : "https://via.placeholder.com/150"; 
                    ?>
                    <img src="<?php echo $photo; ?>" style="width:100%; border-radius:10px;">
                </div>
                <div>
                    <h2><?php echo $student['student_name']; ?></h2>
                    <p><strong>Register No:</strong> <?php echo $student['school_register_no']; ?></p>
                    <p><strong>IC No:</strong> <?php echo $student['ic_no']; ?></p>
                    <p><strong>Gender:</strong> <?php echo $student['gender']; ?></p>
                    <p><strong>Birthdate:</strong> <?php echo $student['birthdate']; ?></p>
                    <p><strong>Address:</strong> <?php echo $student['address']; ?></p>
                    <hr>
                    <h3>Parent Info</h3>
                    <p><strong>Father:</strong> <?php echo $student['father_name']; ?> (<?php echo $student['father_phone']; ?>)</p>
                    <p><strong>Mother:</strong> <?php echo $student['mother_name']; ?> (<?php echo $student['mother_phone']; ?>)</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>