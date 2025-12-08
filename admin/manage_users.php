<?php
session_start();
include '../config/db.php';
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

// Add User Logic
if(isset($_POST['add_user'])){
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $full_name, $role);
    if($stmt->execute()){
        $msg = "User added successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}
include 'includes/header.php';
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Manage Users</h1>
        
        <div class="card">
            <h3>Add New User</h3>
            <?php if(isset($msg)) echo "<p style='color:green'>$msg</p>"; ?>
            <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
            <form method="POST">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <select name="role">
                        <option value="subject_teacher">Subject Teacher</option>
                        <option value="class_teacher">Class Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary" style="margin-top:10px;">Create User</button>
            </form>
        </div>

        <div class="card">
            <h3>Existing Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
                    while($row = $users->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $row['full_name']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><span style="padding:5px; background:#eee; border-radius:4px;"><?php echo $row['role']; ?></span></td>
                        <td><?php echo $row['created_at']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>