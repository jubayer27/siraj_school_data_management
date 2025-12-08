<?php
session_start();
include '../config/db.php';
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }

if(isset($_POST['post_notice'])){
    $title = $_POST['title'];
    $msg = $_POST['message'];
    $type = $_POST['type'];
    $date = $_POST['event_date'];
    $uid = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO notices (title, message, type, event_date, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $title, $msg, $type, $date, $uid);
    $stmt->execute();
}
include 'includes/header.php';
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Notice Board</h1>
        <div class="card">
            <form method="POST">
                <input type="text" name="title" placeholder="Notice Title" required style="margin-bottom:10px;">
                <textarea name="message" rows="4" placeholder="Notice Details..." required></textarea>
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <select name="type">
                        <option value="info">Info</option>
                        <option value="alert">Alert</option>
                        <option value="event">Event</option>
                    </select>
                    <input type="date" name="event_date" required>
                    <button type="submit" name="post_notice" class="btn btn-primary">Post Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>