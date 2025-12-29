<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. AUTHENTICATION
if($_SESSION['role'] != 'class_teacher' && $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); 
    exit(); 
}

$teacher_id = $_SESSION['user_id'];

// 2. FETCH CLASS CONTEXT
$class_q = $conn->query("SELECT class_id, class_name FROM classes WHERE class_teacher_id = $teacher_id");
$my_class = $class_q->fetch_assoc();

$cid = $my_class ? $my_class['class_id'] : 0;
$class_name = $my_class ? $my_class['class_name'] : "No Class Assigned";

// 3. FETCH SUBJECT TEACHERS
$teachers = null;
if($cid){
    // UPDATED: Join subject_teachers to support Many-to-Many
    // Returns one row per teacher-subject pair.
    $sql = "SELECT s.subject_name, s.subject_code, u.full_name, u.phone, u.avatar, u.user_id 
            FROM subjects s 
            LEFT JOIN subject_teachers st ON s.subject_id = st.subject_id
            LEFT JOIN users u ON st.teacher_id = u.user_id 
            WHERE s.class_id = $cid 
            ORDER BY s.subject_name ASC, u.full_name ASC";
    $teachers = $conn->query($sql);
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important; margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important; display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Cards & Tables */
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .table-hover tbody tr:hover { background-color: #fffcf5; }

    /* Teacher Avatar */
    .avatar-md { 
        width: 45px; height: 45px; 
        object-fit: cover; border-radius: 50%; 
        border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
    }
    
    /* Subject Icon */
    .subject-icon {
        width: 40px; height: 40px;
        background: #fff8e1; color: #DAA520;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; margin-right: 15px;
    }

    /* Search Input */
    .search-box { position: relative; max-width: 300px; }
    .search-box input { padding-left: 35px; border-radius: 20px; border: 1px solid #ddd; }
    .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; }

    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Subject Teachers</h2>
                    <p class="text-secondary mb-0">Colleagues teaching <strong><?php echo $class_name; ?></strong></p>
                </div>
                
                <?php if($cid): ?>
                <div class="d-flex gap-2">
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                        <i class="fas fa-book me-2 text-warning"></i> <?php echo $teachers->num_rows; ?> Records
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <?php if(!$cid): ?>
                <div class="alert alert-warning border-0 shadow-sm">
                    <i class="fas fa-exclamation-triangle me-2"></i> You do not have a class assigned.
                </div>
            <?php else: ?>

            <div class="card">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <h5 class="fw-bold mb-0">Staff Directory</h5>
                        <div class="search-box w-100">
                            <i class="fas fa-search"></i>
                            <input type="text" id="teacherSearch" class="form-control" placeholder="Search subject or teacher...">
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="teacherTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 text-secondary text-uppercase small">Subject Information</th>
                                    <th class="text-secondary text-uppercase small">Assigned Teacher</th>
                                    <th class="text-secondary text-uppercase small">Contact</th>
                                    <th class="text-end pe-4 text-secondary text-uppercase small">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($teachers && $teachers->num_rows > 0): ?>
                                    <?php while($row = $teachers->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="subject-icon">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo $row['subject_name']; ?></div>
                                                    <small class="text-muted font-monospace"><?php echo $row['subject_code']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($row['full_name']): ?>
                                                <div class="d-flex align-items-center">
                                                    <?php $avatar = $row['avatar'] ? "../uploads/".$row['avatar'] : "https://ui-avatars.com/api/?name=".$row['full_name']."&background=random"; ?>
                                                    <img src="<?php echo $avatar; ?>" class="avatar-md me-3">
                                                    <span class="fw-bold text-secondary"><?php echo $row['full_name']; ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($row['phone']): ?>
                                                <span class="text-dark font-monospace"><?php echo $row['phone']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if($row['phone']): ?>
                                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $row['phone']); ?>" target="_blank" class="btn btn-sm btn-success text-white">
                                                    <i class="fab fa-whatsapp me-1"></i> Message
                                                </a>
                                            <?php endif; ?>
                                            </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No subjects assigned to this class yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Simple Filter Script
document.getElementById('teacherSearch').addEventListener('keyup', function() {
    let searchValue = this.value.toLowerCase();
    let rows = document.querySelectorAll('#teacherTable tbody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>