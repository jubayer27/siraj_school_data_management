<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if ($_SESSION['role'] != 'subject_teacher' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// 2. SEARCH LOGIC
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 3. FETCH MY STUDENTS
// We use GROUP BY to ensure unique students, and GROUP_CONCAT to list all subjects they take from you
$sql = "SELECT s.student_id, s.school_register_no, s.student_name, s.phone, s.photo, 
               c.class_name,
               GROUP_CONCAT(sub.subject_name SEPARATOR ', ') as enrolled_subjects
        FROM students s
        JOIN student_subject_enrollment sse ON s.student_id = sse.student_id
        JOIN subjects sub ON sse.subject_id = sub.subject_id
        LEFT JOIN classes c ON s.class_id = c.class_id
        WHERE sub.teacher_id = $teacher_id";

if ($search) {
    $sql .= " AND (s.student_name LIKE '%$search%' OR s.school_register_no LIKE '%$search%')";
}

$sql .= " GROUP BY s.student_id ORDER BY c.class_name, s.student_name";

$students = $conn->query($sql);
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>My Students Directory</h1>
                <p>List of all students enrolled in your subjects.</p>
            </div>
        </div>

        <div class="card filter-card">
            <form method="GET" style="display:flex; gap:15px; width:100%;">
                <input type="text" name="search" placeholder="Search by Student Name or ID..."
                    value="<?php echo $search; ?>"
                    style="flex:1; padding:12px; border:1px solid #ddd; border-radius:6px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?>
                    <a href="student_list.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card table-card">
            <div class="card-header-row">
                <h3>Enrolled Students (<?php echo $students->num_rows; ?>)</h3>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Profile</th>
                            <th>Register No</th>
                            <th>Class</th>
                            <th>Your Subjects</th>
                            <th>Contact</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                            <?php while ($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <?php
                                            $photo = $row['photo'] ? "../uploads/" . $row['photo'] : "https://ui-avatars.com/api/?name=" . $row['student_name'] . "&background=f0f0f0&color=333";
                                            ?>
                                            <img src="<?php echo $photo; ?>"
                                                style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid #eee;">
                                            <span
                                                style="font-weight:600; color:#2c3e50;"><?php echo $row['student_name']; ?></span>
                                        </div>
                                    </td>
                                    <td style="font-family:monospace; color:#666;"><?php echo $row['school_register_no']; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['class_name']): ?>
                                            <span class="badge-class"><?php echo $row['class_name']; ?></span>
                                        <?php else: ?>
                                            <span style="color:#ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="max-width:200px; font-size:0.85rem; line-height:1.4; color:#555;">
                                            <?php echo $row['enrolled_subjects']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($row['phone']): ?>
                                            <span style="font-size:0.9rem;"><i class="fas fa-phone" style="color:#ccc;"></i>
                                                <?php echo $row['phone']; ?></span>
                                        <?php else: ?>
                                            <span style="color:#ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="view_profile.php?student_id=<?php echo $row['student_id']; ?>"
                                            class="btn btn-sm btn-primary" title="View Full Profile">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:30px; color:#999; font-style:italic;">No
                                    students found enrolled in your subjects.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Specific Styles for this Page */
    .filter-card {
        padding: 20px;
        border-top: 4px solid #DAA520;
        margin-bottom: 25px;
    }

    .table-card {
        padding: 0;
        overflow: hidden;
    }

    .card-header-row {
        padding: 20px 25px;
        border-bottom: 1px solid #f0f0f0;
        background: #fff;
    }

    .card-header-row h3 {
        margin: 0;
        font-size: 1.1rem;
        color: #2c3e50;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: #f9f9f9;
        padding: 15px 20px;
        text-align: left;
        font-weight: 600;
        color: #555;
        text-transform: uppercase;
        font-size: 0.8rem;
        border-bottom: 2px solid #eee;
    }

    .data-table td {
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .data-table tr:hover {
        background: #fffdf0;
    }

    .badge-class {
        background: #fff8e1;
        color: #b8860b;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.8rem;
        border: 1px solid #ffe082;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.85rem;
    }

    @media(max-width: 768px) {

        .data-table th:nth-child(4),
        .data-table td:nth-child(4) {
            display: none;
        }

        /* Hide subjects on mobile */
    }
</style>
<?php include 'includes/footer.php'; ?>