<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. HANDLE DELETION
if (isset($_GET['delete_id'])) {
    $did = $_GET['delete_id'];
    // Safety check: marks existence
    $chk = $conn->query("SELECT count(*) as c FROM student_marks WHERE enrollment_id IN (SELECT enrollment_id FROM student_subject_enrollment WHERE subject_id = $did)")->fetch_assoc();

    if ($chk['c'] > 0) {
        $error = "Cannot delete subject. There are <strong>" . $chk['c'] . "</strong> marks records associated with it.";
    } else {
        $del = $conn->query("DELETE FROM subjects WHERE subject_id = $did");
        if ($del)
            echo "<script>window.location='manage_subjects.php?msg=deleted';</script>";
        else
            $error = "Error: " . $conn->error;
    }
}

// 3. HANDLE BULK ADD SUBJECT (Many Teachers -> One Subject)
if (isset($_POST['add_subject_bulk'])) {
    $name = $_POST['subject_name'];
    $base_code = trim($_POST['subject_code']);
    $class_ids = isset($_POST['class_ids']) ? $_POST['class_ids'] : [];
    // New: Handle multiple teachers or empty array
    $teacher_ids = isset($_POST['teacher_ids']) ? $_POST['teacher_ids'] : [];

    $count = 0;
    $errors = [];

    foreach ($class_ids as $cid) {
        // Fetch Class Name to generate Unique Code
        $c_res = $conn->query("SELECT class_name FROM classes WHERE class_id = $cid");
        if ($c_res->num_rows > 0) {
            $c_row = $c_res->fetch_assoc();
            // Generate Unique Code: MATH-5Amanah
            // Remove spaces from class name for cleaner code
            $class_suffix = str_replace(' ', '', $c_row['class_name']);
            $unique_code = $base_code . "-" . $class_suffix;

            // Check if this specific combo exists
            $chk = $conn->query("SELECT subject_id FROM subjects WHERE subject_code = '$unique_code'");
            if ($chk->num_rows == 0) {
                // A. Insert Subject (No teacher_id here anymore)
                $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $name, $unique_code, $cid);

                if ($stmt->execute()) {
                    $new_subject_id = $conn->insert_id;
                    $count++;

                    // B. Insert Teachers into Junction Table
                    if (!empty($teacher_ids)) {
                        $stmt_t = $conn->prepare("INSERT INTO subject_teachers (subject_id, teacher_id) VALUES (?, ?)");
                        foreach ($teacher_ids as $tid) {
                            $stmt_t->bind_param("ii", $new_subject_id, $tid);
                            $stmt_t->execute();
                        }
                    }
                }
            } else {
                $errors[] = "Skipped $unique_code (Already exists)";
            }
        }
    }

    if ($count > 0) {
        $success = "Successfully created subjects for $count classes!";
        echo "<script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>";
    } else {
        $error = "No subjects created. " . implode(", ", $errors);
    }
}

// 4. STATISTICS
$stats_total = $conn->query("SELECT count(*) as c FROM subjects")->fetch_assoc()['c'];

// Count subjects that have NO entries in subject_teachers table
$stats_no_teacher = $conn->query("
    SELECT count(*) as c FROM subjects s 
    WHERE NOT EXISTS (SELECT 1 FROM subject_teachers st WHERE st.subject_id = s.subject_id)
")->fetch_assoc()['c'];

$stats_active = $conn->query("SELECT count(DISTINCT class_id) as c FROM subjects")->fetch_assoc()['c'];

// 5. FILTER LOGIC
$filter_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// UPDATED SQL: Joins subject_teachers and GROUP_CONCAT to get all names
$sql = "SELECT s.*, c.class_name, 
        GROUP_CONCAT(u.full_name SEPARATOR ', ') as teacher_names,
        (SELECT COUNT(*) FROM student_subject_enrollment WHERE subject_id = s.subject_id) as enrolled_count
        FROM subjects s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        LEFT JOIN subject_teachers st ON s.subject_id = st.subject_id
        LEFT JOIN users u ON st.teacher_id = u.user_id 
        WHERE 1=1";

if ($filter_class)
    $sql .= " AND s.class_id = $filter_class";
if ($search)
    $sql .= " AND (s.subject_name LIKE '%$search%' OR s.subject_code LIKE '%$search%')";

$sql .= " GROUP BY s.subject_id ORDER BY s.subject_name ASC, c.class_name ASC";
$subjects = $conn->query($sql);

// Fetch Dropdowns
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$teachers = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY full_name");
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

    /* Multi-select box style */
    .class-checkbox-list {
        max-height: 150px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px;
        background: #f8f9fa;
    }

    .stat-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        transition: 0.2s;
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
    }

    #addSubjectForm {
        border-top: 5px solid #FFD700;
    }

    .table-hover tbody tr:hover {
        background-color: #fcfcfc;
    }

    .avatar-xs {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #eee;
        margin-right: 5px;
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
                    <h2 class="fw-bold text-dark mb-0">Subject Management</h2>
                    <p class="text-secondary mb-0">Assign curriculum to multiple classes.</p>
                </div>
                <button onclick="toggleForm()" class="btn btn-warning fw-bold text-dark shadow-sm">
                    <i class="fas fa-plus-circle me-2"></i> Add Subjects
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
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="fas fa-trash-alt me-2"></i>
                    Subject deleted permanently.</div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3"><i
                                    class="fas fa-book"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $stats_total; ?></h3><span
                                    class="text-muted small">Total Subjects</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3"><i
                                    class="fas fa-user-slash"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $stats_no_teacher; ?></h3><span
                                    class="text-muted small">Unassigned Subjects</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3 h-100">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3"><i
                                    class="fas fa-layer-group"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?php echo $stats_active; ?></h3><span
                                    class="text-muted small">Active Classes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" id="addSubjectForm" style="display:none;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark m-0"><i class="fas fa-plus text-warning me-2"></i> Add Subjects to
                            Classes</h5>
                        <button type="button" class="btn-close" onclick="toggleForm()"></button>
                    </div>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Subject Name</label>
                                <input type="text" name="subject_name" class="form-control"
                                    placeholder="e.g. Mathematics" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Base Code (Prefix)</label>
                                <input type="text" name="subject_code" class="form-control" placeholder="e.g. MATH"
                                    required>
                                <div class="form-text small">Code will become: MATH-ClassName</div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted">Assign to Classes (Select
                                    Multiple)</label>
                                <div class="class-checkbox-list">
                                    <?php
                                    $classes->data_seek(0);
                                    while ($c = $classes->fetch_assoc()):
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="class_ids[]"
                                                value="<?php echo $c['class_id']; ?>" id="c_<?php echo $c['class_id']; ?>">
                                            <label class="form-check-label" for="c_<?php echo $c['class_id']; ?>">
                                                <?php echo $c['class_name']; ?> (Year <?php echo $c['year']; ?>)
                                            </label>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted">Assign Teachers (Hold Ctrl/Cmd to
                                    select multiple)</label>
                                <select name="teacher_ids[]" class="form-select" multiple size="4">
                                    <?php
                                    $teachers->data_seek(0);
                                    while ($t = $teachers->fetch_assoc()): ?>
                                        <option value="<?php echo $t['user_id']; ?>">
                                            <?php echo htmlspecialchars($t['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text small">Selected teachers will be assigned to ALL classes chosen
                                    above.</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light me-2" onclick="toggleForm()">Cancel</button>
                            <button type="submit" name="add_subject_bulk"
                                class="btn btn-warning fw-bold text-dark px-4">Create Subjects</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4">
                            <select name="class_id" class="form-select">
                                <option value="">Filter by Class</option>
                                <?php
                                $classes->data_seek(0);
                                while ($c = $classes->fetch_assoc()):
                                    $sel = ($filter_class == $c['class_id']) ? 'selected' : '';
                                    echo "<option value='{$c['class_id']}' $sel>{$c['class_name']}</option>";
                                endwhile;
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control"
                                    placeholder="Search subject name..." value="<?php echo $search; ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary fw-bold">Filter</button>
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
                                    <th class="ps-4">Subject</th>
                                    <th>Code</th>
                                    <th>Class Assigned</th>
                                    <th>Teachers</th>
                                    <th class="text-center">Enrolled</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($subjects->num_rows > 0): ?>
                                    <?php while ($row = $subjects->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo $row['subject_name']; ?></td>
                                            <td><span
                                                    class="badge bg-dark font-monospace"><?php echo $row['subject_code']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($row['class_name']): ?>
                                                    <a href="view_class.php?class_id=<?php echo $row['class_id']; ?>"
                                                        class="badge bg-warning text-dark text-decoration-none"><?php echo $row['class_name']; ?></a>
                                                <?php else: ?>
                                                    <span class="text-muted small">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['teacher_names']): ?>
                                                    <?php
                                                    // Convert string "Name1, Name2" into badges
                                                    $names = explode(', ', $row['teacher_names']);
                                                    foreach ($names as $n):
                                                        ?>
                                                        <span class="badge bg-light text-secondary border me-1 mb-1">
                                                            <i class="fas fa-user-tie me-1"></i> <?php echo $n; ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">No
                                                        Teacher</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-info-subtle text-info rounded-pill px-3"><?php echo $row['enrolled_count']; ?></span>
                                            </td>

                                            <td class="text-end pe-4">
                                                <a href="view_subject.php?subject_id=<?php echo $row['subject_id']; ?>"
                                                    class="btn btn-sm btn-outline-info me-1" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_subject.php?subject_id=<?php echo $row['subject_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                                                <a href="manage_subjects.php?delete_id=<?php echo $row['subject_id']; ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Delete this subject?');"><i
                                                        class="fas fa-trash"></i></a>
                                            </td>

                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">No subjects found.</td>
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
        var x = document.getElementById("addSubjectForm");
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