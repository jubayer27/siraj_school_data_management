<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['subject_id'])) {
    echo "<script>window.location='manage_subjects.php';</script>";
    exit();
}

$sid = $_GET['subject_id'];
$success = "";
$error = "";

// 2. HANDLE UPDATE
if (isset($_POST['update_subject'])) {
    $name = $_POST['subject_name'];
    $code = $_POST['subject_code'];
    $cid = $_POST['class_id'];
    // Capture array of selected IDs
    $teacher_ids = isset($_POST['teacher_ids']) ? $_POST['teacher_ids'] : [];

    // A. Update Subject Details (Removed teacher_id from here)
    $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, subject_code=?, class_id=? WHERE subject_id=?");
    $stmt->bind_param("ssii", $name, $code, $cid, $sid);

    if ($stmt->execute()) {

        // B. Update Teachers (Delete Old -> Insert New)
        // 1. Remove existing mapping
        $conn->query("DELETE FROM subject_teachers WHERE subject_id = $sid");

        // 2. Insert new mapping
        if (!empty($teacher_ids)) {
            $stmt_t = $conn->prepare("INSERT INTO subject_teachers (subject_id, teacher_id) VALUES (?, ?)");
            foreach ($teacher_ids as $tid) {
                $stmt_t->bind_param("ii", $sid, $tid);
                $stmt_t->execute();
            }
        }

        $success = "Subject updated successfully!";
        // Auto-redirect back to view page after 1.5 seconds
        echo "<script>setTimeout(function(){ window.location='view_subject.php?subject_id=$sid'; }, 1500);</script>";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// 3. FETCH DATA
// Fetch Subject Basic Info
$sub = $conn->query("SELECT * FROM subjects WHERE subject_id = $sid")->fetch_assoc();
if (!$sub)
    die("Subject not found.");

// Fetch Currently Assigned Teachers (For Pre-selection)
$current_teacher_ids = [];
$assigned_q = $conn->query("SELECT teacher_id FROM subject_teachers WHERE subject_id = $sid");
while ($row = $assigned_q->fetch_assoc()) {
    $current_teacher_ids[] = $row['teacher_id'];
}

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

    /* Full Width Fix */
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
    .edit-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        max-width: 800px;
        margin: 0 auto;
    }

    .form-label {
        font-weight: 600;
        color: #555;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control,
    .form-select {
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #FFD700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.15);
    }

    /* Header Background */
    .header-bg {
        background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
        height: 180px;
        width: 100%;
        border-radius: 0 0 20px 20px;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 0;
    }

    .page-title {
        position: relative;
        z-index: 1;
        color: white;
        margin-bottom: 30px;
    }

    .breadcrumb-item a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
    }

    .breadcrumb-item.active {
        color: white;
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
        <div class="header-bg"></div>

        <div class="container-fluid position-relative">

            <div class="d-flex justify-content-between align-items-center page-title pt-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="dashboard.php">Admin</a></li>
                            <li class="breadcrumb-item"><a href="manage_subjects.php">Subjects</a></li>
                            <li class="breadcrumb-item active">Edit Subject</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold mb-0">Edit Subject Details</h2>
                </div>
                <a href="view_subject.php?subject_id=<?php echo $sid; ?>"
                    class="btn btn-light shadow-sm text-dark fw-bold">
                    <i class="fas fa-arrow-left me-2"></i> Back to View
                </a>
            </div>

            <div class="card edit-card">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h5 class="fw-bold text-dark m-0"><i class="fas fa-book-reader text-warning me-2"></i> Update
                        Curriculum</h5>
                </div>

                <div class="card-body p-4">

                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success d-flex align-items-center mb-4">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">Basic Information</h6>
                        <div class="row g-4 mb-4">
                            <div class="col-md-8">
                                <label class="form-label">Subject Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i
                                            class="fas fa-book text-secondary"></i></span>
                                    <input type="text" name="subject_name" class="form-control"
                                        value="<?php echo $sub['subject_name']; ?>" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Subject Code</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i
                                            class="fas fa-barcode text-secondary"></i></span>
                                    <input type="text" name="subject_code" class="form-control"
                                        value="<?php echo $sub['subject_code']; ?>" required>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2 pt-2">Allocations</h6>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Assign to Class</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i
                                            class="fas fa-layer-group text-secondary"></i></span>
                                    <select name="class_id" class="form-select" required>
                                        <option value="">-- Select Class --</option>
                                        <?php
                                        $classes->data_seek(0);
                                        while ($c = $classes->fetch_assoc()):
                                            $sel = ($c['class_id'] == $sub['class_id']) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo $c['class_id']; ?>" <?php echo $sel; ?>>
                                                <?php echo $c['class_name']; ?> (<?php echo $c['year']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Assign Teachers</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i
                                            class="fas fa-chalkboard-teacher text-secondary"></i></span>

                                    <select name="teacher_ids[]" class="form-select" multiple size="4">
                                        <?php
                                        $teachers->data_seek(0);
                                        while ($t = $teachers->fetch_assoc()):
                                            // Check if this teacher is in the assigned array
                                            $sel = in_array($t['user_id'], $current_teacher_ids) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo $t['user_id']; ?>" <?php echo $sel; ?>>
                                                <?php echo $t['full_name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-text small">Hold <strong>Ctrl</strong> (Windows) or
                                    <strong>Cmd</strong> (Mac) to select multiple.
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="view_subject.php?subject_id=<?php echo $sid; ?>"
                                class="btn btn-secondary px-4">Cancel</a>
                            <button type="submit" name="update_subject" class="btn btn-warning fw-bold px-4 text-dark">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>

</html>