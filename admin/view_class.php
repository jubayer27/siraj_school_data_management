<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['class_id'])) {
    echo "<script>window.location='manage_classes.php';</script>";
    exit();
}
$cid = intval($_GET['class_id']);

// 2. FETCH CLASS DETAILS
$c_query = $conn->query("SELECT c.*, u.full_name as teacher_name, u.phone, u.avatar 
                         FROM classes c 
                         LEFT JOIN users u ON c.class_teacher_id = u.user_id 
                         WHERE c.class_id = $cid");
$class = $c_query->fetch_assoc();

if (!$class)
    die("Class not found.");

// 3. FETCH STATISTICS
$total_stu = $conn->query("SELECT count(*) as c FROM students WHERE class_id = $cid")->fetch_assoc()['c'];
$male_stu = $conn->query("SELECT count(*) as c FROM students WHERE class_id = $cid AND gender = 'Male'")->fetch_assoc()['c'];
$female_stu = $conn->query("SELECT count(*) as c FROM students WHERE class_id = $cid AND gender = 'Female'")->fetch_assoc()['c'];

// 4. FETCH SUBJECTS & TEACHERS (FIXED for Many-to-Many)
// We concat ID and Name like "12:John Doe" so we can parse it later for links
$subjects = $conn->query("SELECT s.*, 
                          GROUP_CONCAT(CONCAT(u.user_id, ':', u.full_name) SEPARATOR '||') as teacher_data
                          FROM subjects s 
                          LEFT JOIN subject_teachers st ON s.subject_id = st.subject_id
                          LEFT JOIN users u ON st.teacher_id = u.user_id 
                          WHERE s.class_id = $cid
                          GROUP BY s.subject_id");

// 5. FETCH STUDENTS LIST
$students = $conn->query("SELECT * FROM students WHERE class_id = $cid ORDER BY student_name ASC");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    /* Layout Fix */
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

    /* Custom Cards */
    .view-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        margin-bottom: 20px;
    }

    .card-header-custom {
        background: white;
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Teacher Avatar */
    .avatar-md {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .avatar-sm {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #eee;
        margin-right: 5px;
    }

    /* Stat Cards */
    .mini-stat {
        background: white;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        border-bottom: 3px solid transparent;
    }

    .stat-blue {
        border-color: #3498db;
    }

    .stat-pink {
        border-color: #e91e63;
    }

    .stat-gold {
        border-color: #f1c40f;
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
                    <h2 class="fw-bold text-dark mb-0">Class Profile</h2>
                    <p class="text-secondary mb-0"><strong><?php echo $class['class_name']; ?></strong> | Year:
                        <?php echo $class['year']; ?>
                    </p>
                </div>
                <a href="manage_classes.php" class="btn btn-light shadow-sm border">
                    <i class="fas fa-arrow-left me-2"></i> Back to Classes
                </a>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card view-card h-100 p-3 border-start border-4 border-warning">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <?php $t_img = $class['avatar'] ? "../uploads/" . $class['avatar'] : "https://ui-avatars.com/api/?name=" . $class['teacher_name']; ?>
                                <img src="<?php echo $t_img; ?>" class="avatar-md">
                            </div>
                            <div>
                                <small class="text-uppercase text-muted fw-bold">Class Mentor</small>
                                <h4 class="mb-0 text-dark fw-bold">
                                    <?php echo $class['teacher_name'] ? $class['teacher_name'] : "<span class='text-danger'>Unassigned</span>"; ?>
                                </h4>
                                <small class="text-secondary"><i class="fas fa-phone me-1"></i>
                                    <?php echo $class['phone'] ? $class['phone'] : "N/A"; ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 col-4">
                    <div class="mini-stat stat-gold h-100">
                        <h2 class="mb-0 fw-bold"><?php echo $total_stu; ?></h2>
                        <small class="text-muted text-uppercase">Total</small>
                    </div>
                </div>
                <div class="col-lg-2 col-4">
                    <div class="mini-stat stat-blue h-100">
                        <h2 class="mb-0 fw-bold text-primary"><?php echo $male_stu; ?></h2>
                        <small class="text-muted text-uppercase">Boys</small>
                    </div>
                </div>
                <div class="col-lg-2 col-4">
                    <div class="mini-stat stat-pink h-100">
                        <h2 class="mb-0 fw-bold text-danger"><?php echo $female_stu; ?></h2>
                        <small class="text-muted text-uppercase">Girls</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-8">
                    <div class="card view-card h-100">
                        <div class="card-header-custom">
                            <h5 class="fw-bold m-0 text-dark"><i class="fas fa-user-graduate text-success me-2"></i>
                                Student Directory</h5>
                            <a href="manage_students.php?class_filter=<?php echo $cid; ?>"
                                class="btn btn-sm btn-outline-primary">Manage Students</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 500px; overflow-y:auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th class="ps-4">Reg No</th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th class="text-end pe-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($students->num_rows > 0): ?>
                                            <?php while ($stu = $students->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4"><span
                                                            class="badge bg-light text-dark border font-monospace"><?php echo $stu['school_register_no']; ?></span>
                                                    </td>
                                                    <td class="fw-bold text-dark"><?php echo $stu['student_name']; ?></td>
                                                    <td><?php echo $stu['gender']; ?></td>
                                                    <td class="text-end pe-4">
                                                        <a href="view_student.php?student_id=<?php echo $stu['student_id']; ?>"
                                                            class="btn btn-sm btn-info text-white"><i
                                                                class="fas fa-eye"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">No students enrolled in
                                                    this class.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card view-card h-100">
                        <div class="card-header-custom">
                            <h5 class="fw-bold m-0 text-dark"><i class="fas fa-book text-warning me-2"></i> Curriculum
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if ($subjects->num_rows > 0): ?>
                                    <?php while ($sub = $subjects->fetch_assoc()): ?>
                                        <li class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo $sub['subject_name']; ?></div>
                                                <small
                                                    class="text-muted font-monospace"><?php echo $sub['subject_code']; ?></small>
                                            </div>
                                            <div class="text-end" style="max-width: 50%;">
                                                <?php if ($sub['teacher_data']): ?>
                                                    <?php
                                                    // Parse the GROUP_CONCAT string: "ID:Name||ID:Name"
                                                    $t_list = explode('||', $sub['teacher_data']);
                                                    foreach ($t_list as $t_str):
                                                        list($tid, $tname) = explode(':', $t_str);
                                                        ?>
                                                        <a href="view_user.php?user_id=<?php echo $tid; ?>"
                                                            class="badge bg-light text-primary border border-primary-subtle text-decoration-none d-block mb-1 text-truncate">
                                                            <i class="fas fa-user me-1"></i> <?php echo $tname; ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">No
                                                        Teacher</span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <li class="list-group-item p-4 text-center text-muted">No subjects assigned yet.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>