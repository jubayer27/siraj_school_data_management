<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. HANDLE DELETE
if (isset($_GET['delete_id'])) {
    $did = $_GET['delete_id'];
    $conn->query("DELETE FROM notices WHERE notice_id = $did");
    header("Location: manage_notices.php?msg=deleted");
    exit();
}

// 3. HANDLE CREATE
if (isset($_POST['post_notice'])) {
    $title = $_POST['title'];
    $msg = $_POST['message'];
    $type = $_POST['type'];
    $date = $_POST['event_date'];
    $uid = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO notices (title, message, type, event_date, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $title, $msg, $type, $date, $uid);

    if ($stmt->execute()) {
        $success = "Notice posted successfully!";
    } else {
        $error = "Error posting notice.";
    }
}

// 4. FETCH NOTICES
$notices = $conn->query("SELECT * FROM notices ORDER BY event_date DESC, created_at DESC");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    .main-content {
        position: absolute;
        top: 0;
        right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh;
        padding: 0 !important;
        display: block !important;
    }

    .container-fluid {
        padding: 30px !important;
    }

    /* Card Styling */
    .notice-form-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        height: 100%;
    }

    .card-header-custom {
        background: white;
        padding: 20px;
        border-bottom: 1px solid #f0f0f0;
    }

    /* Notice Item Styling */
    .notice-item {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        position: relative;
        border-left: 5px solid #ccc;
        transition: transform 0.2s;
    }

    .notice-item:hover {
        transform: translateX(5px);
    }

    /* Color Coding */
    .type-alert {
        border-left-color: #e74c3c;
    }

    .type-event {
        border-left-color: #27ae60;
    }

    .type-info {
        border-left-color: #3498db;
    }

    .icon-box {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.2rem;
    }

    .bg-alert {
        background: #fadbd8;
        color: #c0392b;
    }

    .bg-event {
        background: #d5f5e3;
        color: #1e8449;
    }

    .bg-info {
        background: #d6eaf8;
        color: #2874a6;
    }

    .date-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        color: #999;
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .delete-btn {
        position: absolute;
        bottom: 20px;
        right: 20px;
        color: #e74c3c;
        opacity: 0.6;
        transition: 0.2s;
    }

    .delete-btn:hover {
        opacity: 1;
    }

    @media (max-width: 992px) {
        .main-content {
            width: 100% !important;
            margin-left: 0 !important;
        }
    }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Notice Board</h2>
                    <p class="text-secondary mb-0">Manage school-wide announcements and events.</p>
                </div>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success d-flex align-items-center"><i class="fas fa-trash-alt me-2"></i> Notice
                    deleted successfully.</div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success d-flex align-items-center"><i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?></div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="card notice-form-card">
                        <div class="card-header-custom">
                            <h5 class="fw-bold m-0 text-dark"><i class="fas fa-edit text-warning me-2"></i> Post New
                                Notice</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Title</label>
                                    <input type="text" name="title" class="form-control"
                                        placeholder="e.g. Sports Day 2025" required>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Type</label>
                                        <select name="type" class="form-select">
                                            <option value="info">Info</option>
                                            <option value="alert">Alert / Urgent</option>
                                            <option value="event">Event</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Date</label>
                                        <input type="date" name="event_date" class="form-control"
                                            value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Details</label>
                                    <textarea name="message" class="form-control" rows="5"
                                        placeholder="Write your announcement here..." required></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="post_notice" class="btn btn-primary fw-bold">
                                        <i class="fas fa-paper-plane me-2"></i> Publish Notice
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <h5 class="fw-bold text-dark mb-3 ps-1">Recent Announcements</h5>

                    <?php if ($notices->num_rows > 0): ?>
                        <?php while ($row = $notices->fetch_assoc()):
                            // Determine styles based on type
                            $borderClass = "type-info";
                            $bgClass = "bg-info";
                            $icon = "fa-info";

                            if ($row['type'] == 'alert') {
                                $borderClass = "type-alert";
                                $bgClass = "bg-alert";
                                $icon = "fa-exclamation-triangle";
                            } elseif ($row['type'] == 'event') {
                                $borderClass = "type-event";
                                $bgClass = "bg-event";
                                $icon = "fa-calendar-check";
                            }
                            ?>
                            <div class="notice-item <?php echo $borderClass; ?>">
                                <div class="d-flex align-items-start">
                                    <div class="icon-box <?php echo $bgClass; ?>">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="w-100">
                                        <div class="date-badge">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date('d M Y', strtotime($row['event_date'])); ?>
                                        </div>

                                        <h5 class="fw-bold text-dark mb-1"><?php echo $row['title']; ?></h5>
                                        <span class="badge <?php echo $bgClass; ?> border border-opacity-25 mb-2 text-uppercase"
                                            style="font-size:0.7rem;">
                                            <?php echo $row['type']; ?>
                                        </span>

                                        <p class="text-secondary mb-0 mt-2" style="white-space: pre-line;">
                                            <?php echo $row['message']; ?>
                                        </p>
                                    </div>
                                </div>

                                <a href="manage_notices.php?delete_id=<?php echo $row['notice_id']; ?>"
                                    class="delete-btn btn btn-sm btn-link text-decoration-none"
                                    onclick="return confirm('Are you sure you want to delete this notice?');">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted bg-white rounded shadow-sm">
                            <i class="far fa-clipboard fa-3x mb-3 opacity-50"></i>
                            <p>No notices posted yet.</p>
                        </div>
                    <?php endif; ?>

                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>