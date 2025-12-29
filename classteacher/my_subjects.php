<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin' && $_SESSION['role'] != 'subject_teacher') {
    header("Location: ../index.php");
    exit();
}

$tid = $_SESSION['user_id'];

// 2. FETCH ALL SUBJECTS TAUGHT BY THIS TEACHER (Updated for Many-to-Many)
$sql = "SELECT s.subject_id, s.subject_name, s.subject_code, c.class_name,
        (SELECT COUNT(*) FROM student_subject_enrollment WHERE subject_id = s.subject_id) as enrollment
        FROM subjects s
        JOIN classes c ON s.class_id = c.class_id
        JOIN subject_teachers st ON s.subject_id = st.subject_id
        WHERE st.teacher_id = $tid
        ORDER BY c.class_name, s.subject_name";
$subjects = $conn->query($sql);
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

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        transition: 0.2s;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
    }

    .icon-square {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
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
                    <h2 class="fw-bold text-dark mb-1">My Teaching Subjects</h2>
                    <p class="text-secondary mb-0">Manage grading for all your assigned classes.</p>
                </div>
            </div>

            <div class="row g-4">
                <?php if ($subjects->num_rows > 0): ?>
                    <?php while ($row = $subjects->fetch_assoc()): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="icon-square bg-warning bg-opacity-10 text-warning">
                                            <i class="fas fa-book fa-lg"></i>
                                        </div>
                                        <span class="badge bg-dark"><?php echo $row['class_name']; ?></span>
                                    </div>

                                    <h5 class="fw-bold text-dark mb-1"><?php echo $row['subject_name']; ?></h5>
                                    <p class="text-muted font-monospace small mb-3"><?php echo $row['subject_code']; ?></p>

                                    <div class="d-flex align-items-center text-secondary small mb-4">
                                        <i class="fas fa-users me-2"></i> <?php echo $row['enrollment']; ?> Students Enrolled
                                    </div>

                                    <div class="d-grid gap-2">
                                        <a href="grade_book.php?subject_id=<?php echo $row['subject_id']; ?>"
                                            class="btn btn-primary fw-bold shadow-sm">
                                            <i class="fas fa-pen-alt me-2"></i> Grade Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-5 border-0 shadow-sm">
                            <i class="fas fa-info-circle fa-2x mb-3 text-info"></i><br>
                            <h5 class="fw-bold">No Subjects Assigned</h5>
                            <p class="text-muted">You have not been assigned to teach any subjects yet.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>