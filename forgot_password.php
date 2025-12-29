<?php
session_start();
include 'config/db.php'; // Adjust path if your db.php is in a subfolder

$msg = "";
$msg_type = "";

// Helper: Generate Random Password
function generateRandomPassword($length = 8)
{
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle($chars), 0, $length);
}

if (isset($_POST['reset_password'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);

    // 1. Check if user exists
    $sql = "SELECT user_id, full_name FROM users WHERE username = '$username' AND email = '$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 2. Generate New Password
        $new_password = generateRandomPassword(8);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // 3. Update Database
        $uid = $user['user_id'];
        $update = $conn->query("UPDATE users SET password = '$hashed_password' WHERE user_id = $uid");

        if ($update) {
            // 4. Send Email
            $to = $email;
            $subject = "Password Reset - SIRAJ School Management";
            $message = "Hello " . $user['full_name'] . ",\n\n";
            $message .= "Your password has been successfully reset.\n";
            $message .= "Your New Password is: " . $new_password . "\n\n";
            $message .= "Please login and change this password immediately.\n";
            $headers = "From: no-reply@sirajschool.com";

            // NOTE: mail() requires a configured mail server (like Sendmail/Postfix) on your server.
            // On Localhost (XAMPP), this usually fails without specific config.
            if (@mail($to, $subject, $message, $headers)) {
                $msg = "A new password has been sent to your email.";
                $msg_type = "success";
            } else {
                // FALLBACK FOR LOCALHOST TESTING ONLY
                $msg = "Password reset successful! <br><strong>(Localhost Mode) Your New Password is: " . $new_password . "</strong>";
                $msg_type = "warning";
            }
        } else {
            $msg = "Database error. Could not reset password.";
            $msg_type = "danger";
        }
    } else {
        $msg = "No account found with that Username and Email combination.";
        $msg_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | SIRAJ School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-top: 5px solid #FFD700;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: #2c3e50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .brand-logo img {
            width: 50px;
        }

        .btn-gold {
            background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
            border: none;
            color: #333;
            font-weight: bold;
            width: 100%;
            padding: 12px;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .form-control {
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #eee;
        }

        .form-control:focus {
            border-color: #FFD700;
            box-shadow: none;
            background: white;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-logo">
                <img src="assets/siraj-logo.png" alt="Logo" onerror="this.style.display='none'">
            </div>
            <h4 class="fw-bold text-dark">Forgot Password?</h4>
            <p class="text-muted small">Enter your details to receive a new password.</p>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> text-center small"><?php echo $msg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0"
                        placeholder="Enter your username" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">Registered Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="Enter your email"
                        required>
                </div>
            </div>

            <button type="submit" name="reset_password" class="btn btn-gold mb-3">
                <i class="fas fa-paper-plane me-2"></i> Send New Password
            </button>

            <div class="text-center">
                <a href="index.php" class="text-decoration-none text-muted small">
                    <i class="fas fa-arrow-left me-1"></i> Back to Login
                </a>
            </div>
        </form>
    </div>

</body>

</html>