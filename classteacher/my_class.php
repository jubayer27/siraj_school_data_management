<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY: Ensure User is Class Teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'class_teacher') {
    header("Location: ../index.php");
    exit();
}
$tid = $_SESSION['user_id'];

// 2. GET TEACHER'S CLASS
$c_query = $conn->query("SELECT * FROM classes WHERE class_teacher_id = $tid");
$my_class = $c_query->fetch_assoc();

if (!$my_class)
    die("<div class='main-content'><div class='alert alert-danger m-5'>You are not assigned to any class yet. Please contact the Administrator.</div></div>");
$my_cid = $my_class['class_id'];

// ---------------------------------------------------------
// 3. HANDLE ACTIONS
// ---------------------------------------------------------

$success = "";
$error = "";

// A. BULK TRANSFER REQUEST
if (isset($_POST['bulk_transfer'])) {
    if (!empty($_POST['student_ids']) && !empty($_POST['to_class_id'])) {
        $target_cid = $_POST['to_class_id'];
        $ids = $_POST['student_ids']; // Array
        $count = 0;

        // Prepare Check & Insert statements
        $stmt_check = $conn->prepare("SELECT transfer_id FROM student_transfers WHERE student_id = ? AND status = 'pending'");
        $stmt_insert = $conn->prepare("INSERT INTO student_transfers (student_id, from_class_id, to_class_id) VALUES (?, ?, ?)");

        foreach ($ids as $stu_id) {
            $stmt_check->bind_param("i", $stu_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows == 0) {
                $stmt_insert->bind_param("iii", $stu_id, $my_cid, $target_cid);
                if ($stmt_insert->execute())
                    $count++;
            }
        }
        $success = "Transfer request sent for $count students.";
    } else {
        $error = "Please select students and a target class.";
    }
}

// B. APPROVE INCOMING
if (isset($_GET['approve_id'])) {
    $tr_id = intval($_GET['approve_id']);
    $tr = $conn->query("SELECT * FROM student_transfers WHERE transfer_id = $tr_id AND to_class_id = $my_cid AND status = 'pending'")->fetch_assoc();

    if ($tr) {
        $conn->query("UPDATE students SET class_id = $my_cid WHERE student_id = {$tr['student_id']}");
        $conn->query("UPDATE student_transfers SET status = 'approved' WHERE transfer_id = $tr_id");
        $success = "Student accepted successfully.";
    }
}

// C. REJECT INCOMING
if (isset($_GET['reject_id'])) {
    $tr_id = intval($_GET['reject_id']);
    $conn->query("UPDATE student_transfers SET status = 'rejected' WHERE transfer_id = $tr_id AND to_class_id = $my_cid");
    $error = "Transfer request rejected.";
}

// ---------------------------------------------------------
// 4. DATA FETCHING & SEARCH
// ---------------------------------------------------------

$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql_students = "SELECT * FROM students WHERE class_id = $my_cid";
if ($search) {
    $sql_students .= " AND (student_name LIKE '%$search%' OR school_register_no LIKE '%$search%')";
}
$sql_students .= " ORDER BY student_name ASC";
$my_students = $conn->query($sql_students);

// Incoming Requests
$incoming = $conn->query("SELECT t.*, s.student_name, c.class_name as from_class 
                          FROM student_transfers t 
                          JOIN students s ON t.student_id = s.student_id 
                          JOIN classes c ON t.from_class_id = c.class_id 
                          WHERE t.to_class_id = $my_cid AND t.status = 'pending'");

// Outgoing Requests
$outgoing = $conn->query("SELECT t.*, s.student_name, c.class_name as target_class 
                          FROM student_transfers t 
                          JOIN students s ON t.student_id = s.student_id 
                          JOIN classes c ON t.to_class_id = c.class_id 
                          WHERE t.from_class_id = $my_cid AND t.status = 'pending'");

// Other Classes for Dropdown
$all_classes = $conn->query("SELECT * FROM classes WHERE class_id != $my_cid ORDER BY class_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    /* Content Wrapper matching Sidebar */
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

    /* Quick Nav Cards */
    .nav-card {
        border: none;
        border-radius: 10px;
        padding: 20px;
        background: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        transition: 0.2s;
        text-decoration: none;
        color: #555;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .nav-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
        color: #2980b9;
    }

    .nav-icon {
        font-size: 2rem;
        opacity: 0.2;
    }

    /* Tables & Sections */
    .card-custom {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        margin-bottom: 25px;
    }

    .header-myclass {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
    }

    /* Status Badges */
    .badge-pending {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
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
                    <h2 class="fw-bold text-dark mb-0">Class Management</h2>
                    <p class="text-secondary mb-0">
                        Home Room: <strong><?php echo $my_class['class_name']; ?></strong>
                        <span class="badge bg-warning text-dark ms-2"><?php echo $my_class['year']; ?></span>
                    </p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small"><i class="fas fa-users me-1"></i>
                        <?php echo $my_students->num_rows; ?> Students</span>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center"><i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <a href="my_class_students.php" class="nav-card">
                        <div>
                            <h6 class="fw-bold m-0">Student List</h6><span class="small text-muted">View full
                                profiles</span>
                        </div>
                        <i class="fas fa-user-graduate nav-icon text-primary"></i>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="my_subject_teachers.php" class="nav-card">
                        <div>
                            <h6 class="fw-bold m-0">Subject Teachers</h6><span class="small text-muted">Manage
                                instructors</span>
                        </div>
                        <i class="fas fa-chalkboard-teacher nav-icon text-success"></i>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="assign_subjects.php" class="nav-card">
                        <div>
                            <h6 class="fw-bold m-0">Enrollments</h6><span class="small text-muted">Assign
                                subjects</span>
                        </div>
                        <i class="fas fa-tasks nav-icon text-warning"></i>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="class_performance.php" class="nav-card">
                        <div>
                            <h6 class="fw-bold m-0">Performance</h6><span class="small text-muted">Class
                                analytics</span>
                        </div>
                        <i class="fas fa-chart-line nav-icon text-danger"></i>
                    </a>
                </div>
            </div>

            <div class="row">

                <div class="col-lg-8">
                    <div class="card card-custom h-100">
                        <div
                            class="card-header p-3 header-myclass rounded-top d-flex justify-content-between align-items-center">
                            <h5 class="m-0 fw-bold"><i class="fas fa-users me-2"></i> My Students</h5>

                            <form method="GET" class="d-flex" style="max-width: 250px;">
                                <input type="text" name="search" class="form-control form-control-sm me-2"
                                    placeholder="Search student..." value="<?php echo $search; ?>">
                                <button type="submit" class="btn btn-sm btn-light"><i
                                        class="fas fa-search"></i></button>
                            </form>
                        </div>

                        <div class="card-body p-0">
                            <form method="POST" id="bulkForm">

                                <div
                                    class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                        <label class="form-check-label small fw-bold" for="selectAll">Select All</label>
                                    </div>
                                    <button type="button" class="btn btn-warning btn-sm fw-bold text-dark shadow-sm"
                                        onclick="openTransferModal()">
                                        <i class="fas fa-exchange-alt me-1"></i> Transfer Selected
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-white">
                                            <tr>
                                                <th width="40"></th>
                                                <th>Student Name</th>
                                                <th>Reg No</th>
                                                <th class="text-end pe-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($my_students->num_rows > 0): ?>
                                                <?php while ($stu = $my_students->fetch_assoc()): ?>
                                                    <tr>
                                                        <td class="ps-3">
                                                            <input type="checkbox" name="student_ids[]"
                                                                value="<?php echo $stu['student_id']; ?>"
                                                                class="form-check-input stu-checkbox">
                                                        </td>
                                                        <td class="fw-bold text-dark"><?php echo $stu['student_name']; ?></td>
                                                        <td class="text-muted small font-monospace">
                                                            <?php echo $stu['school_register_no']; ?>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <span
                                                                class="badge bg-success-subtle text-success rounded-pill">Active</span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-5 text-muted">No students found.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <input type="hidden" name="to_class_id" id="hidden_target_class">
                                <input type="hidden" name="bulk_transfer" value="1">
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">

                    <div class="card card-custom mb-4 border-start border-4 border-success">
                        <div class="card-header bg-white fw-bold text-success">
                            <i class="fas fa-inbox me-2"></i> Incoming Requests
                            <span class="badge bg-success float-end"><?php echo $incoming->num_rows; ?></span>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($incoming->num_rows > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($inc = $incoming->fetch_assoc()): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong><?php echo $inc['student_name']; ?></strong>
                                                <small class="text-muted">From: <?php echo $inc['from_class']; ?></small>
                                            </div>
                                            <div class="d-flex gap-2 mt-2">
                                                <a href="my_class.php?approve_id=<?php echo $inc['transfer_id']; ?>"
                                                    class="btn btn-sm btn-success flex-fill"><i class="fas fa-check"></i>
                                                    Accept</a>
                                                <a href="my_class.php?reject_id=<?php echo $inc['transfer_id']; ?>"
                                                    class="btn btn-sm btn-outline-danger flex-fill"><i class="fas fa-times"></i>
                                                    Reject</a>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted small">No pending incoming requests.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card card-custom border-start border-4 border-warning">
                        <div class="card-header bg-white fw-bold text-warning">
                            <i class="fas fa-paper-plane me-2"></i> Outgoing Status
                            <span class="badge bg-warning text-dark float-end"><?php echo $outgoing->num_rows; ?></span>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($outgoing->num_rows > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($out = $outgoing->fetch_assoc()): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold"><?php echo $out['student_name']; ?></div>
                                                <small class="text-muted">To: <?php echo $out['target_class']; ?></small>
                                            </div>
                                            <span class="badge badge-pending">Pending</span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted small">No pending outgoing transfers.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-exchange-alt me-2 text-warning"></i> Transfer
                        Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Select the destination class for the selected students.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Class</label>
                        <select id="modalClassSelect" class="form-select">
                            <option value="">-- Choose Class --</option>
                            <?php
                            if ($all_classes->num_rows > 0) {
                                $all_classes->data_seek(0);
                                while ($c = $all_classes->fetch_assoc()) {
                                    echo "<option value='{$c['class_id']}'>{$c['class_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        These students will remain in your list until the new class teacher accepts the request.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning fw-bold" onclick="submitBulkTransfer()">Send
                        Request</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 1. Select All Checkboxes
        document.getElementById('selectAll').onclick = function () {
            var checkboxes = document.querySelectorAll('.stu-checkbox');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        }

        // 2. Open Modal Logic
        var transferModal = new bootstrap.Modal(document.getElementById('transferModal'));

        function openTransferModal() {
            // Check if any student selected
            var checked = document.querySelectorAll('.stu-checkbox:checked');
            if (checked.length === 0) {
                alert("Please select at least one student to transfer.");
                return;
            }
            transferModal.show();
        }

        // 3. Submit Form via JS
        function submitBulkTransfer() {
            var targetClass = document.getElementById('modalClassSelect').value;
            if (targetClass === "") {
                alert("Please select a target class.");
                return;
            }
            // Set hidden input value
            document.getElementById('hidden_target_class').value = targetClass;
            // Submit the form
            document.getElementById('bulkForm').submit();
        }
    </script>
    </body>

    </html>