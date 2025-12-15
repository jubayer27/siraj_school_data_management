<?php
$host = "localhost";
$user = "root";          // default user for XAMPP
$pass = "";              // default password = empty
$dbname = "school";      // your database name

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// FUNCTION: Auto-enroll student to all subjects of a class
function autoEnrollSubject($conn, $student_id, $class_id)
{
    // 1. Fetch all subjects linked to this class
    $sub_q = $conn->query("SELECT subject_id FROM subjects WHERE class_id = $class_id");

    if ($sub_q->num_rows > 0) {
        // 2. Prepare Insert Statement (INSERT IGNORE skips duplicates)
        $stmt = $conn->prepare("INSERT IGNORE INTO student_subject_enrollment (student_id, subject_id, class_id, enrollment_date) VALUES (?, ?, ?, NOW())");

        while ($row = $sub_q->fetch_assoc()) {
            $sid = $row['subject_id'];
            $stmt->bind_param("iii", $student_id, $sid, $class_id);
            $stmt->execute();
        }
    }
}
?>