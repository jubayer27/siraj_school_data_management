<?php
session_start();
date_default_timezone_set('Asia/Dhaka'); // Set to your timezone (or Asia/Kuala_Lumpur)
include 'config/db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$msg = "";
$msg_type = "";

if (isset($_POST['reset_password'])) {
    // 1. Sanitize Inputs (Remove accidental spaces)
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    // 2. Check if user exists
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE username = ? AND email = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 3. Generate Token
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // 4. Store Token
        $stmt_insert = $conn->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $email, $token, $expiry);

        if ($stmt_insert->execute()) {
            // 5. Send Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'jubayerislam2702@gmail.com'; // Your Email
                $mail->Password = 'byzr mmis ghhf tokg';         // Your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@sirajschool.com', 'SIRAJ School System');
                $mail->addAddress($email, $user['full_name']);

                // Updated Link to your specific project folder
                $resetLink = "http://localhost/siraj_school_data_management/reset_password.php?token=$token&email=$email";

                $mail->isHTML(true);
                $mail->Subject = 'Reset Your Password - SIRAJ School';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                        <h2 style='color: #2c3e50;'>Password Reset Request</h2>
                        <p>Hello <strong>{$user['full_name']}</strong>,</p>
                        <p>We received a request to reset your password. Click the button below to create a new one:</p>
                        <p>
                            <a href='$resetLink' style='background-color: #FFD700; color: #000; padding: 12px 24px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block;'>Reset Password</a>
                        </p>
                        <p style='color: #666; font-size: 12px; margin-top: 20px;'>Link expires in 1 hour. If you didn't ask for this, ignore it.</p>
                        <p style='font-size: 10px; color: #999;'>Link: $resetLink</p>
                    </div>
                ";

                $mail->send();
                $msg = "Success! We have sent a reset link to <b>$email</b>.";
                $msg_type = "success";
            } catch (Exception $e) {
                $msg = "Mailer Error: {$mail->ErrorInfo}";
                $msg_type = "danger";
            }
        } else {
            $msg = "Database Error: Could not save reset token.";
            $msg_type = "danger";
        }
    } else {
        $msg = "We could not find an account with that Username and Email.";
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
            font-family: 'Segoe UI', sans-serif;
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
            width: 70px;
            height: 70px;
            background: #2c3e50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .brand-logo img {
            width: 40px;
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
    </style>
</head>

<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-logo">
                <img src="assets/siraj-logo.png" alt="Logo" onerror="this.style.display='none'">
            </div>
            <h4 class="fw-bold text-dark">Forgot Password?</h4>
            <p class="text-muted small">Enter your details to receive a reset link.</p>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> text-center small"><?php echo $msg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" placeholder="Enter username"
                        required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">Registered Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="Enter email"
                        required>
                </div>
            </div>

            <button type="submit" name="reset_password" class="btn btn-gold mb-3">
                <i class="fas fa-paper-plane me-2"></i> Send Reset Link
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