<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. CHECK ID
if(!isset($_GET['subject_id'])){
    echo "<script>window.location='manage_subjects.php';</script>";
    exit();
}
$sid = $_GET['subject_id'];

// 2. FETCH SUBJECT DETAILS
$sql = "SELECT s.*, c.class_name, c.year, u.full_name as teacher_name, u.phone, u.avatar 
        FROM subjects s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        LEFT JOIN users u ON s.teacher_id = u.user_id 
        WHERE s.subject_id = $sid";
$sub = $conn->query($sql)->fetch_assoc();

if(!$sub) die("Subject not found.");

// 3. FETCH ENROLLED STUDENTS
$stu_sql = "SELECT st.*, sse.enrollment_date 
            FROM student_subject_enrollment sse 
            JOIN students st ON sse.student_id = st.student_id 
            WHERE sse.subject_id = $sid 
            ORDER BY st.student_name ASC";
$students = $conn->query($stu_sql);
$enrolled_count = $students->num_rows;
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Subject Profile</h1>
                <p>Viewing: <strong><?php echo $sub['subject_name']; ?></strong> (<?php echo $sub['subject_code']; ?>)</p>
            </div>
            <a href="manage_subjects.php" class="btn btn-secondary" style="background:#e0e0e0; color:#333;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="profile-layout">
            
            <div class="profile-sidebar">
                <div class="card center-content">
                    <div style="font-size:3rem; color:#DAA520; margin-bottom:10px;">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h2 class="profile-name"><?php echo $sub['subject_name']; ?></h2>
                    <span class="code-badge"><?php echo $sub['subject_code']; ?></span>

                    <div class="profile-meta">
                        <div class="meta-row">
                            <i class="fas fa-chalkboard"></i>
                            <span>Class: <strong><?php echo $sub['class_name']; ?></strong></span>
                        </div>
                        <div class="meta-row">
                            <i class="fas fa-calendar"></i>
                            <span>Year: <?php echo $sub['year']; ?></span>
                        </div>
                        <div class="meta-row">
                            <i class="fas fa-user-graduate"></i>
                            <span>Enrolled: <strong><?php echo $enrolled_count; ?></strong></span>
                        </div>
                    </div>

                    <a href="edit_subject.php?subject_id=<?php echo $sub['subject_id']; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-edit"></i> Edit Subject
                    </a>
                </div>

                <div class="card">
                    <div class="section-header">
                        <h3>Assigned Teacher</h3>
                    </div>
                    <?php if($sub['teacher_name']): ?>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <?php 
                                $avatar = $sub['avatar'] ? "../uploads/".$sub['avatar'] : "https://ui-avatars.com/api/?name=".$sub['teacher_name']."&background=f0f0f0&color=333";
                            ?>
                            <img src="<?php echo $avatar; ?>" style="width:50px; height:50px; border-radius:50%; object-fit:cover;">
                            <div>
                                <div style="font-weight:bold; color:#333;"><?php echo $sub['teacher_name']; ?></div>
                                <div style="font-size:0.85rem; color:#888;"><i class="fas fa-phone"></i> <?php echo $sub['phone'] ? $sub['phone'] : 'N/A'; ?></div>
                            </div>
                        </div>
                        <div style="margin-top:15px; text-align:center;">
                            <a href="view_user.php?user_id=<?php echo $sub['teacher_id']; ?>" class="btn-link">View Teacher Profile</a>
                        </div>
                    <?php else: ?>
                        <p style="color:#999; font-style:italic;">No teacher assigned.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-main">
                
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:15px;">
                        <h3 style="margin:0;"><i class="fas fa-users" style="color:#DAA520;"></i> Student Roster</h3>
                        <a href="../subjectTeacher/manage_marks.php?subject_id=<?php echo $sid; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-star-half-alt"></i> Manage Marks
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reg No</th>
                                    <th>Student Name</th>
                                    <th>Gender</th>
                                    <th>Enrolled Date</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($students->num_rows > 0): ?>
                                    <?php while($stu = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td><span style="background:#f9f9f9; padding:3px 8px; border-radius:4px; font-size:0.85rem;"><?php echo $stu['school_register_no']; ?></span></td>
                                        <td style="font-weight:600; color:#333;"><?php echo $stu['student_name']; ?></td>
                                        <td><?php echo $stu['gender']; ?></td>
                                        <td style="color:#888; font-size:0.9rem;"><?php echo date('d M Y', strtotime($stu['enrollment_date'])); ?></td>
                                        <td style="text-align:right;">
                                            <a href="../public/student_view.php?student_id=<?php echo $stu['student_id']; ?>" class="btn-link">View Profile</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="empty-table">No students currently enrolled in this subject.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    /* Layout Logic (Matches view_user) */
    .profile-layout { display: grid; grid-template-columns: 300px 1fr; gap: 25px; align-items: start; }
    @media (max-width: 900px) { .profile-layout { grid-template-columns: 1fr; } }

    .center-content { text-align: center; }
    .profile-name { margin: 10px 0 5px; font-size: 1.4rem; color: #333; }
    .code-badge { background: #333; color: #fff; padding: 4px 10px; border-radius: 4px; font-family: monospace; font-size: 0.9rem; letter-spacing: 1px; }

    .profile-meta { text-align: left; margin: 20px 0; border-top: 1px solid #f0f0f0; padding-top: 15px; }
    .meta-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem; color: #555; }
    .meta-row i { color: #ccc; width: 20px; }
    
    .section-header { border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px; }
    .section-header h3 { margin: 0; font-size: 1.1rem; color: #555; }
    
    .btn-block { display: block; width: 100%; text-align: center; box-sizing: border-box; }
    .btn-link { color: #3498db; text-decoration: none; font-size: 0.85rem; font-weight: 500; }
    .empty-table { text-align: center; padding: 40px; color: #999; font-style: italic; }
</style>
</body>
</html>