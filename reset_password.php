<?php
session_start();
date_default_timezone_set('Asia/Dhaka'); // Ensure timezone matches the one used in forgot_password.php
include 'config/db.php';

$msg = "";
$msg_type = "";
$valid_link = false;

// 1. Verify the Link (GET Request)
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    $current_time = date("Y-m-d H:i:s");

    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND email = ? AND expiry > ? LIMIT 1");
    $stmt->bind_param("sss", $token, $email, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $valid_link = true;
    } else {
        $msg = "Invalid or expired reset link. Please request a new one.";
        $msg_type = "danger";
    }
} else {
    $msg = "Missing token or email parameters.";
    $msg_type = "danger";
}

// 2. Handle Password Update (POST Request)
if (isset($_POST['update_password']) && $valid_link) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (strlen($new_pass) < 6) {
        $msg = "Password must be at least 6 characters long.";
        $msg_type = "warning";
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "Passwords do not match.";
        $msg_type = "warning";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

        // A. Update the User's Password
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $email);

        if ($update_stmt->execute()) {
            // B. Delete the Token (So it can't be used again)
            $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del_stmt->bind_param("s", $email);
            $del_stmt->execute();

            $msg = "Password updated successfully! <a href='index.php'>Login Now</a>";
            $msg_type = "success";
            $valid_link = false; // Hide the form
        } else {
            $msg = "Database error. Could not update password.";
            $msg_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password | SIRAJ School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .reset-card {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-top: 5px solid #FFD700;
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

    <div class="reset-card">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-dark">Create New Password</h4>
            <p class="text-muted small">Enter your new secure password below.</p>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> text-center small">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($valid_link): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="new_password" class="form-control border-start-0" required
                            placeholder="Min 6 characters">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i
                                class="fas fa-check-circle text-muted"></i></span>
                        <input type="password" name="confirm_password" class="form-control border-start-0" required
                            placeholder="Re-type password">
                    </div>
                </div>

                <button type="submit" name="update_password" class="btn btn-gold">
                    <i class="fas fa-save me-2"></i> Update Password
                </button>
            </form>
        <?php elseif ($msg_type == "success"): ?>
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-dark w-100">
                    <i class="fas fa-sign-in-alt me-2"></i> Go to Login
                </a>
            </div>
        <?php else: ?>
            <div class="text-center mt-4">
                <a href="forget_password.php" class="btn btn-secondary w-100">
                    <i class="fas fa-redo me-2"></i> Request New Link
                </a>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>