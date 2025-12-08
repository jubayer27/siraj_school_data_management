<?php
// setup_users.php
include 'config/db.php';

// The users you requested
$users = [
    [
        'username' => 'admin', 
        'password' => 'admin123', 
        'name' => 'Super Admin', 
        'role' => 'admin'
    ],
    [
        'username' => 'subjectteacher', 
        'password' => 'subject123', 
        'name' => 'Demo Subject Teacher', 
        'role' => 'subject_teacher'
    ],
    [
        'username' => 'classteacher', 
        'password' => 'class123', 
        'name' => 'Demo Class Teacher', 
        'role' => 'class_teacher'
    ]
];

echo "<h3>Setting up Dummy Users...</h3>";

foreach ($users as $user) {
    $u = $user['username'];
    $p = $user['password'];
    $n = $user['name'];
    $r = $user['role'];

    // 1. Generate the secure hash
    $hashed_password = password_hash($p, PASSWORD_DEFAULT);

    // 2. Delete existing user with this username to avoid duplicates
    $conn->query("DELETE FROM users WHERE username = '$u'");

    // 3. Insert new user
    $sql = "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $u, $hashed_password, $n, $r);

    if ($stmt->execute()) {
        echo "<div style='color:green; margin:10px 0;'>";
        echo "✅ User <b>$u</b> created successfully.<br>";
        echo "Password: <b>$p</b><br>";
        echo "Role: <b>$r</b>";
        echo "</div>";
    } else {
        echo "<div style='color:red;'>Error creating $u: " . $conn->error . "</div>";
    }
}
?>
<br>
<a href="index.php">Go to Login Page</a>