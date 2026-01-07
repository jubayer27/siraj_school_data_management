<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. HANDLE DELETION
if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);
    if ($did != $_SESSION['user_id']) {
        // Optional: Check if teacher has dependencies (like classes) before deleting
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
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $ic_no = $_POST['ic_no'];
    $staff_id = $_POST['teacher_id_no'];

    // Check duplicate username
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Error: Username '$username' is already taken.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, phone, ic_no, teacher_id_no, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $username, $password, $full_name, $role, $phone, $ic_no, $staff_id);

        if ($stmt->execute()) {
            $success = "New user profile created successfully!";
            // Clear post to prevent duplicate submission on refresh
            echo "<script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}

// 4. GET STATISTICS
$stats = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role = 'class_teacher' THEN 1 ELSE 0 END) as class_t,
    SUM(CASE WHEN role = 'subject_teacher' THEN 1 ELSE 0 END) as subj_t
    FROM users")->fetch_assoc();

// 5. BUILD QUERY WITH FILTERS (SECURE)
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT u.*, 
        (
            SELECT GROUP_CONCAT(s.subject_name SEPARATOR ', ') 
            FROM subjects s 
            JOIN subject_teachers st ON s.subject_id = st.subject_id 
            WHERE st.teacher_id = u.user_id
        ) as teaching_subjects 
        FROM users u WHERE 1=1";

$params = [];
$types = "";

// Apply Role Filter
if (!empty($filter_role)) {
    $sql .= " AND u.role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

// Apply Search Filter
if (!empty($search_query)) {
    $sql .= " AND (u.full_name LIKE ? OR u.teacher_id_no LIKE ? OR u.username LIKE ?)";
    $searchTerm = "%" . $search_query . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

$sql .= " ORDER BY u.created_at DESC";

// Prepare and Execute Query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
        font-family: 'Segoe UI', sans-serif;
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
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        transition: 0.2s;
        background: white;
        padding: 20px;
        display: flex;
        align-items: center;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
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

    /* Filter Card */
    .filter-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        background: #fff;
    }

    /* Add User Form */
    #addUserForm {
        border-top: 4px solid #FFD700;
        background: #fff;
        border-radius: 12px;
    }

    /* Table Styling */
    .custom-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .custom-table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        color: #6c757d;
        padding: 15px;
    }

    .custom-table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
        background: #fff;
    }

    .custom-table tbody tr:hover td {
        background-color: #fafbfc;
    }

    .avatar-sm {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-right: 12px;
    }

    /* Role Badges */
    .badge-role {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .badge-admin {
        background-color: #2c3e50;
        color: #fff;
    }

    .badge-class {
        background-color: #ffc107;
        color: #000;
    }

    .badge-subject {
        background-color: #17a2b8;
        color: #fff;
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
                    <h2 class="fw-bold text-dark mb-0">Staff Directory</h2>
                    <p class="text-secondary mb-0">Manage system access and teaching staff.</p>
                </div>
                <button onclick="toggleForm()" class="btn btn-warning fw-bold text-dark shadow-sm px-4">
                    <i class="fas fa-plus me-2"></i> Add New Staff
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
                    <i class="fas fa-check-circle me-2 fa-lg"></i>
                    <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
                    <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
                    <i class="fas fa-trash-alt me-2 fa-lg"></i>
                    <div>User account has been deleted.</div>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-users"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['total']; ?></h3>
                            <span class="text-muted small text-uppercase fw-bold">Total Staff</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i
                                class="fas fa-chalkboard-teacher"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['class_t']; ?></h3>
                            <span class="text-muted small text-uppercase fw-bold">Class Teachers</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-book"></i></div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $stats['subj_t']; ?></h3>
                            <span class="text-muted small text-uppercase fw-bold">Subject Teachers</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4" id="addUserForm" style="display:none;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark m-0"><i class="fas fa-user-plus text-warning me-2"></i> Register
                            New Staff</h5>
                        <button type="button" class="btn-close" onclick="toggleForm()"></button>
                    </div>

                    <form method="POST" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">FULL NAME</label>
                                <input type="text" name="full_name" class="form-control" required
                                    placeholder="e.g. John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">SYSTEM ROLE</label>
                                <select name="role" class="form-select bg-light">
                                    <option value="subject_teacher">Subject Teacher</option>
                                    <option value="class_teacher">Class Teacher</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">STAFF ID</label>
                                <input type="text" name="teacher_id_no" class="form-control"
                                    placeholder="e.g. T-2025-001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">IC NUMBER</label>
                                <input type="text" name="ic_no" class="form-control" placeholder="No Dashes">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">CONTACT PHONE</label>
                                <input type="text" name="phone" class="form-control" placeholder="+60...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">USERNAME</label>
                                <input type="text" name="username" class="form-control" required
                                    autocomplete="new-username">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                                <input type="password" name="password" class="form-control" required
                                    autocomplete="new-password">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-light me-2" onclick="toggleForm()">Cancel</button>
                            <button type="submit" name="add_user" class="btn btn-primary px-4 fw-bold"><i
                                    class="fas fa-save me-2"></i> Create Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card filter-card mb-4">
                <div class="card-body p-3">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <select name="role" class="form-select border-0 bg-light fw-bold text-secondary">
                                <option value="">Filter by Role: All</option>
                                <option value="subject_teacher" <?php echo ($filter_role == 'subject_teacher') ? 'selected' : ''; ?>>Subject Teacher</option>
                                <option value="class_teacher" <?php echo ($filter_role == 'class_teacher') ? 'selected' : ''; ?>>Class Teacher</option>
                                <option value="admin" <?php echo ($filter_role == 'admin') ? 'selected' : ''; ?>>
                                    Administrator</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light text-muted"><i
                                        class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control border-0 bg-light"
                                    placeholder="Search by Name, Staff ID, or Username..."
                                    value="<?php echo htmlspecialchars($search_query); ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary fw-bold">Apply Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Staff Profile</th>
                                    <th>Contact Info</th>
                                    <th>Assigned Role</th>
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
                                                    <?php
                                                    // Fallback for avatar
                                                    $avatarUrl = $row['avatar']
                                                        ? "../uploads/" . htmlspecialchars($row['avatar'])
                                                        : "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']) . "&background=random&color=fff";
                                                    ?>
                                                    <img src="<?php echo $avatarUrl; ?>" class="avatar-sm">
                                                    <div>
                                                        <div class="fw-bold text-dark">
                                                            <?php echo htmlspecialchars($row['full_name']); ?></div>
                                                        <div class="small text-muted">ID: <span
                                                                class="font-monospace"><?php echo $row['teacher_id_no'] ? htmlspecialchars($row['teacher_id_no']) : 'N/A'; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column small">
                                                    <span class="text-dark"><i class="fas fa-phone-alt text-muted me-2"
                                                            style="width:15px;"></i>
                                                        <?php echo $row['phone'] ? htmlspecialchars($row['phone']) : '-'; ?></span>
                                                    <span class="text-muted"><i class="fas fa-user text-muted me-2"
                                                            style="width:15px;"></i>
                                                        @<?php echo htmlspecialchars($row['username']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                if ($row['role'] == 'admin')
                                                    echo '<span class="badge badge-role badge-admin">Administrator</span>';
                                                elseif ($row['role'] == 'class_teacher')
                                                    echo '<span class="badge badge-role badge-class">Class Teacher</span>';
                                                else
                                                    echo '<span class="badge badge-role badge-subject">Subject Teacher</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($row['teaching_subjects']): ?>
                                                    <small class="text-muted d-block text-truncate" style="max-width: 250px;"
                                                        title="<?php echo htmlspecialchars($row['teaching_subjects']); ?>">
                                                        <i class="fas fa-book me-1 text-warning"></i>
                                                        <?php echo htmlspecialchars($row['teaching_subjects']); ?>
                                                    </small>
                                                <?php elseif ($row['role'] == 'admin'): ?>
                                                    <span class="small text-muted">-</span>
                                                <?php else: ?>
                                                    <span class="small text-muted fst-italic">No subjects assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="view_user.php?user_id=<?php echo $row['user_id']; ?>"
                                                    class="btn btn-sm btn-outline-secondary me-1" title="View Profile"><i
                                                        class="fas fa-eye"></i></a>

                                                <a href="edit_user.php?user_id=<?php echo $row['user_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary me-1" title="Edit Profile"><i
                                                        class="fas fa-edit"></i></a>

                                                <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                                    <a href="manage_users.php?delete_id=<?php echo $row['user_id']; ?>"
                                                        class="btn btn-sm btn-outline-danger" title="Delete User"
                                                        onclick="return confirm('Are you sure you want to PERMANENTLY delete this user?');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-light text-muted" disabled
                                                        title="Cannot delete yourself"><i class="fas fa-ban"></i></button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                                                <p class="mb-0 fw-bold">No users found matching your criteria.</p>
                                                <a href="manage_users.php"
                                                    class="btn btn-link btn-sm text-decoration-none">Clear Filters</a>
                                            </div>
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

<script>
    function toggleForm() {
        var form = document.getElementById("addUserForm");
        if (form.style.display === "none") {
            form.style.display = "block";
            // Smooth scroll to form
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            form.style.display = "none";
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>