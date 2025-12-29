<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$msg = "";
$msg_type = "";
$edit_mode = false;
$edit_data = ['exam_name' => '', 'max_marks' => 100, 'status' => 'active'];

// 2. HANDLE ACTIONS

// A. ADD NEW EXAM
if (isset($_POST['add_exam'])) {
    $name = $conn->real_escape_string($_POST['exam_name']);
    $max = intval($_POST['max_marks']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO exam_types (exam_name, max_marks, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $name, $max, $status);

    if ($stmt->execute()) {
        $msg = "Exam Type added successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error: " . $conn->error;
        $msg_type = "danger";
    }
}

// B. UPDATE EXISTING EXAM
if (isset($_POST['update_exam'])) {
    $id = intval($_POST['exam_id']);
    $name = $conn->real_escape_string($_POST['exam_name']);
    $max = intval($_POST['max_marks']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE exam_types SET exam_name=?, max_marks=?, status=? WHERE exam_id=?");
    $stmt->bind_param("sisi", $name, $max, $status, $id);

    if ($stmt->execute()) {
        $msg = "Exam Type updated successfully!";
        $msg_type = "success";
        // Refresh to clear edit mode
        echo "<script>window.setTimeout(function(){ window.location.href = 'exam_setup.php'; }, 1500);</script>";
    } else {
        $msg = "Error updating record.";
        $msg_type = "danger";
    }
}

// C. DELETE EXAM
if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);

    // Safety Check: Marks exist?
    $check_name = $conn->query("SELECT exam_name FROM exam_types WHERE exam_id = $did")->fetch_assoc()['exam_name'];
    $usage_check = $conn->query("SELECT mark_id FROM student_marks WHERE exam_type = '$check_name'");

    if ($usage_check->num_rows > 0) {
        $msg = "Cannot delete: Students already have marks recorded for '$check_name'.";
        $msg_type = "danger";
    } else {
        $conn->query("DELETE FROM exam_types WHERE exam_id = $did");
        $msg = "Exam Type deleted.";
        $msg_type = "success";
    }
}

// D. FETCH DATA FOR EDIT
if (isset($_GET['edit_id'])) {
    $eid = intval($_GET['edit_id']);
    $res = $conn->query("SELECT * FROM exam_types WHERE exam_id = $eid");
    if ($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
        $edit_mode = true;
    }
}

// 4. FETCH ALL EXAMS
$exams = $conn->query("SELECT * FROM exam_types ORDER BY exam_id ASC");
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
        padding: 30px !important;
        display: block !important;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .form-header {
        background: linear-gradient(135deg, #2c3e50, #4a6fa5);
        color: white;
        padding: 15px 20px;
        border-radius: 12px 12px 0 0;
    }

    .form-header.edit-mode {
        background: linear-gradient(135deg, #f39c12, #d35400);
        /* Orange for Edit Mode */
    }

    .status-active {
        background: #d4edda;
        color: #155724;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .status-inactive {
        background: #f8d7da;
        color: #721c24;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .table-hover tbody tr:hover {
        background-color: #fffcf5;
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
                    <h2 class="fw-bold text-dark mb-1">Exam Configuration</h2>
                    <p class="text-secondary mb-0">Define exam types and grading ceilings.</p>
                </div>
                <?php if ($edit_mode): ?>
                    <a href="exam_setup.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i> Cancel
                        Edit</a>
                <?php endif; ?>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> shadow-sm border-0 d-flex align-items-center mb-4">
                    <i
                        class="fas fa-<?php echo ($msg_type == 'success') ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="form-header <?php echo $edit_mode ? 'edit-mode' : ''; ?>">
                            <h5 class="m-0 fw-bold">
                                <i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-plus-circle'; ?> me-2"></i>
                                <?php echo $edit_mode ? 'Edit Exam' : 'Add New Exam'; ?>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <?php if ($edit_mode): ?>
                                    <input type="hidden" name="exam_id" value="<?php echo $eid; ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Exam Name</label>
                                    <input type="text" name="exam_name" class="form-control"
                                        value="<?php echo htmlspecialchars($edit_data['exam_name']); ?>"
                                        placeholder="e.g. Unit Test 1" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Max Marks</label>
                                    <input type="number" name="max_marks" class="form-control"
                                        value="<?php echo $edit_data['max_marks']; ?>" required>
                                    <div class="form-text">Default denominator for calculations.</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo ($edit_data['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($edit_data['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <?php if ($edit_mode): ?>
                                        <button type="submit" name="update_exam"
                                            class="btn btn-warning fw-bold text-dark py-2">
                                            Update Changes
                                        </button>
                                        <a href="exam_setup.php" class="btn btn-light border text-muted">Cancel</a>
                                    <?php else: ?>
                                        <button type="submit" name="add_exam" class="btn btn-primary fw-bold py-2">
                                            Create Exam Type
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header bg-white py-3 border-bottom-0">
                            <h5 class="fw-bold m-0 text-dark">Existing Exam Types</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Exam Name</th>
                                        <th class="text-center">Max Marks</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($exams->num_rows > 0): ?>
                                        <?php while ($row = $exams->fetch_assoc()): ?>
                                            <tr
                                                class="<?php echo ($edit_mode && $eid == $row['exam_id']) ? 'table-warning' : ''; ?>">
                                                <td class="ps-4 text-muted small">#<?php echo $row['exam_id']; ?></td>
                                                <td class="fw-bold text-dark"><?php echo $row['exam_name']; ?></td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-light text-dark border"><?php echo $row['max_marks']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="status-<?php echo $row['status']; ?>">
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="exam_setup.php?edit_id=<?php echo $row['exam_id']; ?>"
                                                        class="btn btn-sm btn-outline-warning text-dark me-1" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="exam_setup.php?delete_id=<?php echo $row['exam_id']; ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Are you sure? This cannot be undone.');"
                                                        title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">No exam types defined yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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