<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. CHECK ID
if(!isset($_GET['student_id'])){
    echo "<script>window.location='manage_students.php';</script>";
    exit();
}
$sid = $_GET['student_id'];

// 2. FETCH STUDENT DATA
$sql = "SELECT s.*, c.class_name, c.year 
        FROM students s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        WHERE s.student_id = $sid";
$result = $conn->query($sql);
$student = $result->fetch_assoc();

if(!$student) die("Student record not found.");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Student Profile</h1>
                <p>Viewing Record: <strong><?php echo $student['school_register_no']; ?></strong></p>
            </div>
            <a href="manage_students.php" class="btn btn-secondary" style="background:#e0e0e0; color:#333;">
                <i class="fas fa-arrow-left"></i> Back to Directory
            </a>
        </div>

        <div class="profile-layout">
            
            <div class="profile-sidebar">
                <div class="card center-content">
                    <div class="avatar-container">
                        <?php 
                            $photo = $student['photo'] ? "../uploads/".$student['photo'] : "https://ui-avatars.com/api/?name=".$student['student_name']."&background=f0f0f0&color=333&size=150";
                        ?>
                        <img src="<?php echo $photo; ?>" alt="Student Photo">
                    </div>
                    
                    <h2 class="profile-name"><?php echo $student['student_name']; ?></h2>
                    
                    <div style="margin-bottom:15px;">
                        <?php if($student['class_name']): ?>
                            <span class="badge-class"><?php echo $student['class_name']; ?> (<?php echo $student['year']; ?>)</span>
                        <?php else: ?>
                            <span class="badge-none">Unassigned</span>
                        <?php endif; ?>
                    </div>

                    <div class="profile-meta">
                        <div class="meta-row">
                            <i class="fas fa-id-card"></i> <span><?php echo $student['ic_no'] ? $student['ic_no'] : 'N/A'; ?></span>
                        </div>
                        <div class="meta-row">
                            <i class="fas fa-venus-mars"></i> <span><?php echo $student['gender']; ?></span>
                        </div>
                        <div class="meta-row">
                            <i class="fas fa-phone"></i> <span><?php echo $student['phone'] ? $student['phone'] : '-'; ?></span>
                        </div>
                        <div class="meta-row">
                            <i class="fas fa-birthday-cake"></i> <span><?php echo $student['birthdate'] ? date('d M Y', strtotime($student['birthdate'])) : '-'; ?></span>
                        </div>
                    </div>

                    <a href="edit_student.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-user-edit"></i> Edit Full Profile
                    </a>
                </div>

                <div class="card">
                    <div class="section-header"><h3>Status Flags</h3></div>
                    <div class="status-item">
                        <span>Orphan Status:</span>
                        <strong><?php echo $student['is_orphan'] == 'Yes' ? '<span style="color:red">Yes</span>' : 'No'; ?></strong>
                    </div>
                    <div class="status-item">
                        <span>Baitulmal:</span>
                        <strong><?php echo $student['is_baitulmal_recipient'] == 'Yes' ? '<span style="color:green">Recipient</span>' : 'No'; ?></strong>
                    </div>
                </div>
            </div>

            <div class="profile-main">
                
                <div class="card">
                    <div class="section-header">
                        <h3><i class="fas fa-user-circle" style="color:#DAA520;"></i> Personal Information</h3>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item"><label>Full Name</label><div><?php echo $student['student_name']; ?></div></div>
                        <div class="detail-item"><label>MyKid / IC / Passport</label><div><?php echo $student['ic_no']; ?></div></div>
                        <div class="detail-item"><label>Birth Cert No</label><div><?php echo $student['birth_cert_no']; ?></div></div>
                        <div class="detail-item"><label>Place of Birth</label><div><?php echo $student['birth_place']; ?></div></div>
                        <div class="detail-item"><label>Race</label><div><?php echo $student['race']; ?></div></div>
                        <div class="detail-item"><label>Religion</label><div><?php echo $student['religion']; ?></div></div>
                        <div class="detail-item"><label>Nationality</label><div><?php echo $student['nationality']; ?></div></div>
                        <div class="detail-item"><label>Enrollment Date</label><div><?php echo $student['enrollment_date']; ?></div></div>
                        <div class="detail-item full-width"><label>Home Address</label><div><?php echo $student['address']; ?></div></div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-header">
                        <h3><i class="fas fa-users" style="color:#DAA520;"></i> Family Background</h3>
                    </div>
                    
                    <h4 style="margin:10px 0; color:#555;">Father's Information</h4>
                    <div class="details-grid three-col" style="margin-bottom:20px; border-bottom:1px dashed #eee; padding-bottom:10px;">
                        <div class="detail-item"><label>Name</label><div><?php echo $student['father_name']; ?></div></div>
                        <div class="detail-item"><label>IC No</label><div><?php echo $student['father_ic']; ?></div></div>
                        <div class="detail-item"><label>Phone</label><div><?php echo $student['father_phone']; ?></div></div>
                        <div class="detail-item"><label>Occupation</label><div><?php echo $student['father_job']; ?></div></div>
                        <div class="detail-item"><label>Income</label><div><?php echo $student['father_salary']; ?></div></div>
                    </div>

                    <h4 style="margin:10px 0; color:#555;">Mother's Information</h4>
                    <div class="details-grid three-col" style="margin-bottom:20px; border-bottom:1px dashed #eee; padding-bottom:10px;">
                        <div class="detail-item"><label>Name</label><div><?php echo $student['mother_name']; ?></div></div>
                        <div class="detail-item"><label>IC No</label><div><?php echo $student['mother_ic']; ?></div></div>
                        <div class="detail-item"><label>Phone</label><div><?php echo $student['mother_phone']; ?></div></div>
                        <div class="detail-item"><label>Occupation</label><div><?php echo $student['mother_job']; ?></div></div>
                        <div class="detail-item"><label>Income</label><div><?php echo $student['mother_salary']; ?></div></div>
                    </div>

                    <h4 style="margin:10px 0; color:#555;">Guardian's Information</h4>
                    <div class="details-grid three-col">
                        <div class="detail-item"><label>Name</label><div><?php echo $student['guardian_name'] ? $student['guardian_name'] : '-'; ?></div></div>
                        <div class="detail-item"><label>Phone</label><div><?php echo $student['guardian_phone'] ? $student['guardian_phone'] : '-'; ?></div></div>
                        <div class="detail-item"><label>Relation</label><div><?php echo $student['parents_marital_status']; ?> (Parent Status)</div></div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-header">
                        <h3><i class="fas fa-running" style="color:#DAA520;"></i> Co-Curriculum</h3>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Uniform Unit</label>
                            <div style="font-weight:600;"><?php echo $student['uniform_unit']; ?></div>
                            <small class="text-muted"><?php echo $student['uniform_position']; ?></small>
                        </div>
                        <div class="detail-item">
                            <label>Club / Association</label>
                            <div style="font-weight:600;"><?php echo $student['club_association']; ?></div>
                            <small class="text-muted"><?php echo $student['club_position']; ?></small>
                        </div>
                        <div class="detail-item">
                            <label>Sports & Games</label>
                            <div style="font-weight:600;"><?php echo $student['sports_game']; ?></div>
                            <small class="text-muted"><?php echo $student['sports_position']; ?></small>
                        </div>
                        <div class="detail-item">
                            <label>Sports House</label>
                            <div style="font-weight:bold; color:#e67e22;"><?php echo $student['sports_house']; ?></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    /* Layout */
    .profile-layout { display: grid; grid-template-columns: 320px 1fr; gap: 25px; align-items: start; }
    @media (max-width: 900px) { .profile-layout { grid-template-columns: 1fr; } }

    /* Sidebar Styling */
    .center-content { text-align: center; }
    .avatar-container { width: 140px; height: 140px; margin: 0 auto 20px; border-radius: 50%; padding: 5px; border: 1px solid #eee; }
    .avatar-container img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
    .profile-name { margin: 10px 0 5px; font-size: 1.5rem; color: #333; }
    .badge-class { background: #FFD700; color: #fff; padding: 4px 12px; border-radius: 15px; font-size: 0.9rem; font-weight: bold; }
    .badge-none { background: #eee; color: #777; padding: 4px 12px; border-radius: 15px; font-size: 0.8rem; }
    
    .profile-meta { text-align: left; margin: 25px 0; border-top: 1px solid #f0f0f0; padding-top: 20px; }
    .meta-row { display: flex; align-items: center; margin-bottom: 12px; color: #555; font-size: 0.95rem; }
    .meta-row i { width: 25px; color: #ccc; text-align: center; margin-right: 10px; }
    .btn-block { display: block; width: 100%; text-align: center; box-sizing: border-box; }

    /* Main Content Styling */
    .section-header { border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; }
    .section-header h3 { margin: 0; font-size: 1.2rem; color: #444; }
    
    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .details-grid.three-col { grid-template-columns: 1fr 1fr 1fr; }
    .full-width { grid-column: 1 / -1; }
    
    .detail-item label { display: block; font-size: 0.85rem; color: #999; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px; }
    .detail-item div { font-size: 1rem; color: #333; font-weight: 500; }
    .text-muted { color: #888; font-size: 0.85rem; }

    .status-item { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 10px 0; }
    .status-item:last-child { border-bottom: none; }
    
    @media (max-width: 700px) { .details-grid, .details-grid.three-col { grid-template-columns: 1fr; } }
</style>
</body>
</html>