<?php
session_start();
include 'config/db.php';

// HANDLE LOGIN LOGIC
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Check User in Database
    $stmt = $conn->prepare("SELECT user_id, password, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 2. Verify Password
        if (password_verify($password, $row['password'])) {
            // 3. Set Session Variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];

            // 4. Redirect Based on Role
            switch ($row['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'class_teacher':
                    header("Location: classTeacher/my_class.php");
                    break;
                case 'subject_teacher':
                    header("Location: subjectTeacher/my_subjects.php");
                    break;
                default:
                    $error = "Role not recognized.";
                    break;
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign-in - SIRAJ Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* BASE STYLES matching your theme */
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        /* MAIN CARD */
        .box {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-top: 5px solid #FFD700;
            /* Gold Top Border */
            text-align: center;
        }

        /* LOGO & TITLE */
        .box img {
            width: 80px;
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* INPUT FIELDS */
        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 12px 12px 45px;
            /* Space for Icon */
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: 0.3s;
            background: #fafafa;
        }

        input:focus {
            border-color: #FFD700;
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        /* ERROR MESSAGE */
        .error-msg {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            border: 1px solid #ffcdd2;
        }

        /* BUTTONS */
        .sign-in {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
            border: none;
            color: #333;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
            text-transform: uppercase;
        }

        .sign-in:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* LINKS */
        .link-text {
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .link-text a {
            color: #666;
            text-decoration: none;
        }

        .link-text a:hover {
            color: #DAA520;
            text-decoration: underline;
        }

        /* FOOTER / SIGN UP AREA */
        .inquire-box {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #eee;
        }

        .inquire-box .title {
            font-size: 0.9rem;
            margin-bottom: 10px;
            color: #777;
            font-weight: normal;
            text-transform: none;
            letter-spacing: 0;
        }

        .sign-up {
            background: white;
            border: 2px solid #eee;
            color: #555;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }

        .sign-up:hover {
            border-color: #DAA520;
            color: #DAA520;
        }
    </style>
</head>

<body>

    <div class="box">
        <img src="assets/images/siraj-logo.png" alt="SIRAJ Logo" onerror="this.style.display='none'">

        <div class="title">Sign-in to SIRAJ</div>

        <?php if (isset($error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input name="username" type="text" placeholder="Username" required autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input name="password" type="password" placeholder="Password" required>
            </div>

            <div class="link-text" style="text-align: right; margin-bottom: 15px; margin-top: -10px;">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" name="login" class="sign-in">Sign In</button>
        </form>

        <div class="inquire-box">
            <div class="title">Don't have an account?</div>
            <button type="button" class="sign-up"
                onclick="alert('Please contact the Administrator to register a new staff account.')">
                Create Account
            </button>
        </div>
    </div>

</body>

</html>