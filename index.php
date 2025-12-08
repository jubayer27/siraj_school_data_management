<?php
session_start();
include 'config/db.php';

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()){
        if(password_verify($password, $row['password'])){
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            
            // Redirect based on role
            switch($row['role']) {
                case 'admin': header("Location: admin/dashboard.php"); break;
                case 'class_teacher': header("Location: classteacher/dashboard.php"); break;
                case 'subject_teacher': header("Location: subjectTeacher/dashboard.php"); break;
            }
            exit();
        } else { $error = "Invalid credentials"; }
    } else { $error = "User not found"; }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login - School Portal</title>
<style>
    body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: sans-serif; margin:0; }
    .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 350px; border-top: 5px solid #FFD700; }
    .logo { text-align: center; margin-bottom: 20px; font-size: 24px; font-weight: bold; color: #333; }
    input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
    button { width: 100%; padding: 12px; background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%); border: none; color: white; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; }
    button:hover { opacity: 0.9; }
</style>
</head>
<body>
    <div class="login-box">
        <div class="logo">Enter Portal</div>
        <?php if(isset($error)) echo "<p style='color:red; text-align:center; font-size:0.9em;'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">SECURE LOGIN</button>
        </form>
    </div>
</body>
</html>