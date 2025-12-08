<?php
include '../config/db.php';
include 'includes/header.php';

// 1. CHECK CLASS ID
if(!isset($_GET['class_id'])){
    echo "<script>window.location='manage_classes.php';</script>";
    exit();
}
$cid = $_GET['class_id'];

// 2. FETCH CLASS DETAILS
$c_query = $conn->query("SELECT c.*, u.full_name as teacher_name, u.phone 
                         FROM classes c 
                         LEFT JOIN users u ON c.class_teacher_id = u.user_id 
                         WHERE c.class_id = $cid");
$class = $c_query->fetch_assoc();

if(!$class) die("Class not found.");

// 3. FETCH STATISTICS (Boys vs Girls)
$total_stu = $conn->query("SELECT count(*) as c FROM students WHERE class_id = $cid")->fetch_assoc()['c'];
$male_stu = $conn->query("SELECT count(*) as c FROM students WHERE class_id = $cid AND gender = 'Male'")->fetch_assoc()['c'];
$female_stu = $conn->query("SELECT count(*) as c FROM students WHERE class_id = $cid AND gender = 'Female'")->fetch_assoc()['c'];

// 4. FETCH SUBJECTS & TEACHERS
$subjects = $conn->query("SELECT s.*, u.full_name as subject_teacher 
                          FROM subjects s 
                          LEFT JOIN users u ON s.teacher_id = u.user_id 
                          WHERE s.class_id = $cid");

// 5. FETCH STUDENTS LIST
$students = $conn->query("SELECT * FROM students WHERE class_id = $cid ORDER BY student_name ASC");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Class Profile: <?php echo $class['class_name']; ?></h1>
                <p>Academic Year: <strong><?php echo $class['year']; ?></strong></p>
            </div>
            <a href="manage_classes.php" class="btn btn-secondary" style="background:#ddd; color:#333;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:20px; margin-bottom:25px;">
            <div class="card" style="border-left: 5px solid #FFD700; display:flex; align-items:center;">
                <div style="font-size:3rem; color:#FFD700; margin-right:20px;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h3 style="margin:0; color:#333;">Class Teacher</h3>
                    <p style="margin:5px 0; font-size:1.2rem; font-weight:bold; color:#444;">
                        <?php echo $class['teacher_name'] ? $class['teacher_name'] : "Unassigned"; ?>
                    </p>
                    <span style="color:#888; font-size:0.9rem;"><i class="fas fa-phone"></i> <?php echo $class['phone'] ? $class['phone'] : "N/A"; ?></span>
                </div>
            </div>

            <div class="card" style="text-align:center;">
                <h4 style="margin:0 0 10px 0; color:#555;">Total Students</h4>
                <h1 style="margin:0; color:#DAA520; font-size:2.5rem;"><?php echo $total_stu; ?></h1>
            </div>

            <div class="card" style="display:flex; flex-direction:column; justify-content:center; gap:10px;">
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #eee; padding-bottom:5px;">
                    <span><i class="fas fa-male" style="color:#3498db;"></i> Boys</span>
                    <strong><?php echo $male_stu; ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span><i class="fas fa-female" style="color:#e91e63;"></i> Girls</span>
                    <strong><?php echo $female_stu; ?></strong>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:25px;">
            
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3>Student Directory</h3>
                    <a href="manage_students.php?class_filter=<?php echo $cid; ?>" class="btn btn-primary btn-sm">Manage</a>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Reg No</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($students->num_rows > 0): ?>
                                <?php while($stu = $students->fetch_assoc()): ?>
                                <tr>
                                    <td><span style="background:#f9f9f9; padding:2px 6px; border-radius:4px; font-size:0.85rem;"><?php echo $stu['school_register_no']; ?></span></td>
                                    <td style="font-weight:600;"><?php echo $stu['student_name']; ?></td>
                                    <td><?php echo $stu['gender']; ?></td>
                                    <td style="text-align:right;">
                                        <a href="../public/student_view.php?student_id=<?php echo $stu['student_id']; ?>" class="btn btn-primary btn-sm" style="padding:2px 8px; font-size:0.75rem;">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; color:#999;">No students enrolled.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:25px;">
                
                <div class="card">
                    <h3>Subjects & Teachers</h3>
                    <ul style="list-style:none; padding:0; margin:0;">
                        <?php if($subjects->num_rows > 0): ?>
                            <?php while($sub = $subjects->fetch_assoc()): ?>
                            <li style="padding:10px 0; border-bottom:1px dashed #eee; display:flex; justify-content:space-between;">
                                <div>
                                    <span style="font-weight:600; display:block;"><?php echo $sub['subject_name']; ?></span>
                                    <span style="font-size:0.8rem; color:#888;"><?php echo $sub['subject_code']; ?></span>
                                </div>
                                <div style="text-align:right;">
                                    <span style="font-size:0.9rem; color:#DAA520; display:block;">
                                        <?php echo $sub['subject_teacher'] ? $sub['subject_teacher'] : "<span style='color:red'>No Teacher</span>"; ?>
                                    </span>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li style="color:#999;">No subjects assigned yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card">
                    <h3>Weekly Schedule</h3>
                    <div style="display:grid; grid-template-columns: 50px 1fr; gap:10px; font-size:0.9rem;">
                        <div style="color:#888; text-align:right; border-right:2px solid #eee; padding-right:10px;">
                            <div style="margin-bottom:15px;">08:00</div>
                            <div style="margin-bottom:15px;">09:00</div>
                            <div style="margin-bottom:15px;">10:00</div>
                            <div style="margin-bottom:15px;">11:00</div>
                        </div>
                        <div>
                            <div style="background:#fffcf0; border-left:3px solid #FFD700; padding:2px 8px; margin-bottom:8px;">
                                <strong>Mon:</strong> Mathematics
                            </div>
                            <div style="background:#f0f7ff; border-left:3px solid #3498db; padding:2px 8px; margin-bottom:8px;">
                                <strong>Tue:</strong> English
                            </div>
                            <div style="background:#fff0f3; border-left:3px solid #e91e63; padding:2px 8px; margin-bottom:8px;">
                                <strong>Wed:</strong> Science
                            </div>
                            <div style="color:#999; font-style:italic;">...Schedule configurable in settings</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</body>
</html>