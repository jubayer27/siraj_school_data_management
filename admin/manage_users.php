<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. HANDLE DELETION
if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);
    if ($did != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $did);
        $stmt->execute();
        echo "<script>window.location='manage_users.php?msg=deleted';</script>";
    } else {
        $error = "System Safety: You cannot delete your own admin account.";
    }
}

// 3. HANDLE ADD USER
if (isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $ic_no = $_POST['ic_no'];
    $staff_id = $_POST['teacher_id_no'];

    // Check duplicate username
    $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $error = "Error: Username '$username' is already taken.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, phone, ic_no, teacher_id_no, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $username, $password, $full_name, $role, $phone, $ic_no, $staff_id);

        if ($stmt->execute()) {
            $success = "New user created successfully!";
            echo "<script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

// 4. GET STATISTICS (NEW)
$stat_total = $conn->query("SELECT count(*) as c FROM users")->fetch_assoc()['c'];
$stat_class = $conn->query("SELECT count(*) as c FROM users WHERE role = 'class_teacher'")->fetch_assoc()['c'];
$stat_subj = $conn->query("SELECT count(*) as c FROM users WHERE role = 'subject_teacher'")->fetch_assoc()['c'];

// 5. BUILD QUERY (With Avatar & Filters)
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT u.*, 
        (
            SELECT GROUP_CONCAT(s.subject_name SEPARATOR ', ') 
            FROM subjects s 
            JOIN subject_teachers st ON s.subject_id = st.subject_id 
            WHERE st.teacher_id = u.user_id
        ) as teaching_subjects 
        FROM users u WHERE 1=1";

if ($filter_role)
    $sql .= " AND u.role = '$filter_role'";
if ($search_query)
    $sql .= " AND (u.full_name LIKE '%$search_query%' OR u.teacher_id_no LIKE '%$search_query%')";

$sql .= " ORDER BY u.created_at DESC";
$users = $conn->query($sql);
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

    /* Stats Cards */
    .stat-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        transition: 0.2s;
        background: white;
        padding: 20px;
        display: flex;
        align-items: center;
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 15px;
    }

    /* Custom Card */
    #addUserForm {
        border-top: 5px solid #FFD700;
        transition: all 0.3s ease;
    }

    /* Table Styling */
    .table-hover tbody tr:hover {
        background-color: #fcfcfc;
    }

    .avatar-sm {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #eee;
        margin-right: 12px;
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
                    <h2 class="fw-bold text-dark mb-0">Staff & User Directory</h2>
                    <p class="text-secondary mb-0">Manage system access and teaching staff.</p>
                </div>
                <button onclick="toggleForm()" class="btn btn-warning fw-bold text-dark shadow-sm">
                    <i class="fas fa-plus-circle me-2"></i> Add New Staff
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4"><i
                        class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="fas fa-trash-alt me-2"></i> User
                    account deleted.</div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-users"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stat_total; ?></h3>
                            <span class="text-muted small text-uppercase">Total Users</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-crown"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stat_class; ?></h3>
                            <span class="text-muted small text-uppercase">Class Teachers</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-book-reader"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stat_subj; ?></h3>
                            <span class="text-muted small text-uppercase">Subject Teachers</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" id="addUserForm" style="display:none;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark m-0"><i class="fas fa-user-plus text-warning me-2"></i> Create New
                            Profile</h5>
                        <button type="button" class="btn-close" onclick="toggleForm()"></button>
                    </div>

                    <form method="POST" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Full Name</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Role</label>
                                <select name="role" class="form-select">
                                    <option value="subject_teacher">Subject Teacher</option>
                                    <option value="class_teacher">Class Teacher</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Staff ID / Teacher No</label>
                                <input type="text" name="teacher_id_no" class="form-control"
                                    placeholder="e.g. T-2025-001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">IC Number</label>
                                <input type="text" name="ic_no" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Phone Contact</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Login Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light me-2" onclick="toggleForm()">Cancel</button>
                            <button type="submit" name="add_user" class="btn btn-warning fw-bold text-dark px-4">Create
                                User</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" class="row g-2">
                        <div class="col-md-3">
                            <select name="role" class="form-select">
                                <option value="">All Roles</option>
                                <option value="subject_teacher" <?php echo ($filter_role == 'subject_teacher') ? 'selected' : ''; ?>>Subject Teacher</option>
                                <option value="class_teacher" <?php echo ($filter_role == 'class_teacher') ? 'selected' : ''; ?>>Class Teacher</option>
                                <option value="admin" <?php echo ($filter_role == 'admin') ? 'selected' : ''; ?>>Admin
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control"
                                    placeholder="Search by Name or ID..." value="<?php echo $search_query; ?>">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary fw-bold flex-grow-1">Filter</button>
                            <?php if ($filter_role || $search_query): ?>
                                <a href="manage_users.php" class="btn btn-secondary flex-grow-1">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Staff Info</th>
                                    <th>Contact</th>
                                    <th>Role</th>
                                    <th>Teaching Subjects</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users->num_rows > 0): ?>
                                    <?php while ($row = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <?php $avatar = $row['avatar'] ? "../uploads/" . $row['avatar'] : "https://ui-avatars.com/api/?name=" . $row['full_name'] . "&background=random"; ?>
                                                    <img src="<?php echo $avatar; ?>" class="avatar-sm">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?php echo $row['full_name']; ?></div>
                                                        <div class="small text-muted">ID:
                                                            <?php echo $row['teacher_id_no'] ? $row['teacher_id_no'] : 'N/A'; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small"><i class="fas fa-phone text-muted me-1"></i>
                                                    <?php echo $row['phone'] ? $row['phone'] : '-'; ?></div>
                                                <div class="small text-muted">@<?php echo $row['username']; ?></div>
                                            </td>
                                            <td>
                                                <?php
                                                $badgeClass = "bg-secondary";
                                                if ($row['role'] == 'admin')
                                                    $badgeClass = "bg-dark";
                                                if ($row['role'] == 'class_teacher')
                                                    $badgeClass = "bg-warning text-dark";
                                                if ($row['role'] == 'subject_teacher')
                                                    $badgeClass = "bg-info text-white";
                                                ?>
                                                <span
                                                    class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo str_replace('_', ' ', $row['role']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($row['teaching_subjects']): ?>
                                                    <small class="text-muted text-truncate d-block" style="max-width: 200px;"
                                                        title="<?php echo $row['teaching_subjects']; ?>">
                                                        <?php echo $row['teaching_subjects']; ?>
                                                    </small>
                                                <?php elseif ($row['role'] != 'admin'): ?>
                                                    <span class="small text-muted fst-italic">No subjects assigned</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="view_user.php?user_id=<?php echo $row['user_id']; ?>"
                                                    class="btn btn-sm btn-outline-info me-1" title="View"><i
                                                        class="fas fa-eye"></i></a>
                                                <a href="edit_user.php?user_id=<?php echo $row['user_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary me-1" title="Edit"><i
                                                        class="fas fa-edit"></i></a>

                                                <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                                    <a href="manage_users.php?delete_id=<?php echo $row['user_id']; ?>"
                                                        class="btn btn-sm btn-outline-danger" title="Delete"
                                                        onclick="return confirm('Are you sure you want to remove this user?');"><i
                                                            class="fas fa-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No users found matching your
                                            criteria.</td>
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

<script>
    function toggleForm() {
        var x = document.getElementById("addUserForm");
        if (x.style.display === "none") {
            x.style.display = "block";
            x.scrollIntoView({ behavior: "smooth" });
        } else {
            x.style.display = "none";
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>