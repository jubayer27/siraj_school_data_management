<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if($_SESSION['role'] != 'admin') { header("Location: ../index.php"); exit(); }
if(!isset($_GET['student_id'])){ echo "<script>window.location='manage_students.php';</script>"; exit(); }

$sid = $_GET['student_id'];

// 2. HANDLE UPDATE SUBMISSION
if(isset($_POST['update_student'])){
    
    // --- A. HANDLE PHOTO UPLOAD (Separate Query) ---
    if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
        $target_dir = "../uploads/";
        if(!is_dir($target_dir)) mkdir($target_dir); // Safety create
        
        $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid("stu_") . "." . $ext;
        
        if(move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $new_filename)){
            $conn->query("UPDATE students SET photo='$new_filename' WHERE student_id=$sid");
        }
    }

    // --- B. COLLECT TEXT INPUTS ---
    // Personal
    $name = $_POST['student_name'];
    $reg_no = $_POST['school_register_no'];
    $class_id = $_POST['class_id'];
    $enroll_date = $_POST['enrollment_date'];
    $prev_school = $_POST['previous_school'];
    
    $ic = $_POST['ic_no'];
    $gender = $_POST['gender'];
    $dob = $_POST['birthdate'];
    $birth_place = $_POST['birth_place'];
    $race = $_POST['race'];
    $religion = $_POST['religion'];
    $nationality = $_POST['nationality'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $birth_cert = $_POST['birth_cert_no'];

    // Family (Father)
    $f_name = $_POST['father_name']; 
    $f_ic = $_POST['father_ic']; 
    $f_phone = $_POST['father_phone']; 
    $f_job = $_POST['father_job']; 
    $f_sal = !empty($_POST['father_salary']) ? $_POST['father_salary'] : 0.00;
    
    // Family (Mother)
    $m_name = $_POST['mother_name']; 
    $m_ic = $_POST['mother_ic']; 
    $m_phone = $_POST['mother_phone']; 
    $m_job = $_POST['mother_job']; 
    $m_sal = !empty($_POST['mother_salary']) ? $_POST['mother_salary'] : 0.00;
    
    // Family (Guardian)
    $g_name = $_POST['guardian_name']; 
    $g_ic = $_POST['guardian_ic']; 
    $g_phone = $_POST['guardian_phone']; 
    $g_job = $_POST['guardian_job']; 
    $g_sal = !empty($_POST['guardian_salary']) ? $_POST['guardian_salary'] : 0.00;
    
    // Status
    $marital = $_POST['parents_marital_status'];
    $orphan = $_POST['is_orphan'];
    $baitulmal = $_POST['is_baitulmal_recipient'];
    
    // Co-Q
    $uniform = $_POST['uniform_unit']; $u_pos = $_POST['uniform_position'];
    $club = $_POST['club_association']; $c_pos = $_POST['club_position'];
    $sport = $_POST['sports_game']; $s_pos = $_POST['sports_position'];
    $house = $_POST['sports_house'];

    // --- C. EXECUTE MAIN UPDATE (40 Fields + 1 ID) ---
    $sql = "UPDATE students SET 
            student_name=?, school_register_no=?, class_id=?, enrollment_date=?, previous_school=?,
            ic_no=?, gender=?, birthdate=?, birth_place=?, race=?, religion=?, nationality=?, phone=?, address=?, birth_cert_no=?,
            father_name=?, father_ic=?, father_phone=?, father_job=?, father_salary=?,
            mother_name=?, mother_ic=?, mother_phone=?, mother_job=?, mother_salary=?,
            guardian_name=?, guardian_ic=?, guardian_phone=?, guardian_job=?, guardian_salary=?,
            parents_marital_status=?, is_orphan=?, is_baitulmal_recipient=?,
            uniform_unit=?, uniform_position=?, club_association=?, club_position=?, sports_game=?, sports_position=?, sports_house=?
            WHERE student_id=?";
            
    $stmt = $conn->prepare($sql);
    
    if(!$stmt) die("Prepare Failed: " . $conn->error);

    // Type String Definition: s=string, i=int, d=double
    // Personal (15): ssissssssssssss
    // Families (15): ssssd ssssd ssssd
    // Status (3): sss
    // CoQ (7): sssssss
    // ID (1): i
    $types = "ssissssssssssss" . "ssssd" . "ssssd" . "ssssd" . "sss" . "sssssss" . "i";

    $stmt->bind_param($types, 
        $name, $reg_no, $class_id, $enroll_date, $prev_school,
        $ic, $gender, $dob, $birth_place, $race, $religion, $nationality, $phone, $address, $birth_cert,
        $f_name, $f_ic, $f_phone, $f_job, $f_sal,
        $m_name, $m_ic, $m_phone, $m_job, $m_sal,
        $g_name, $g_ic, $g_phone, $g_job, $g_sal,
        $marital, $orphan, $baitulmal,
        $uniform, $u_pos, $club, $c_pos, $sport, $s_pos, $house,
        $sid
    );

    if($stmt->execute()){
        $success = "Student profile updated successfully!";
        // Refresh page to show new data
        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 1000);</script>";
    } else {
        $error = "Error updating record: " . $stmt->error;
    }
}

// 3. FETCH DATA
$student = $conn->query("SELECT * FROM students WHERE student_id = $sid")->fetch_assoc();
if(!$student) die("Student not found.");

$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
?>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Edit Student Profile</h1>
                <p>Updating record for: <strong><?php echo $student['student_name']; ?></strong></p>
            </div>
            <a href="view_student.php?student_id=<?php echo $sid; ?>" class="btn btn-secondary" style="background:#e0e0e0; color:#333;">Cancel</a>
        </div>

        <?php if(isset($success)) echo "<div class='alert-box success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert-box error'>$error</div>"; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="layout-grid">
                
                <div class="col-left">
                    <div class="card center-content">
                        <div class="avatar-upload">
                            <div class="avatar-preview">
                                <?php $photo = $student['photo'] ? "../uploads/".$student['photo'] : "https://ui-avatars.com/api/?name=".$student['student_name']."&background=f0f0f0&color=333"; ?>
                                <img id="imagePreview" src="<?php echo $photo; ?>">
                            </div>
                            <div class="avatar-edit">
                                <input type='file' name="photo" id="imageUpload" accept=".png, .jpg, .jpeg" />
                                <label for="imageUpload"><i class="fas fa-camera"></i></label>
                            </div>
                        </div>
                        <h3 style="margin:10px 0;"><?php echo $student['student_name']; ?></h3>
                        
                        <div class="form-group" style="text-align:left; margin-top:20px;">
                            <label>Class Assignment</label>
                            <select name="class_id" style="width:100%; padding:10px;">
                                <option value="">-- No Class --</option>
                                <?php 
                                $classes->data_seek(0);
                                while($c = $classes->fetch_assoc()): 
                                    $sel = ($c['class_id'] == $student['class_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $c['class_id']; ?>" <?php echo $sel; ?>>
                                        <?php echo $c['class_name']; ?> (<?php echo $c['year']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group" style="text-align:left;">
                            <label>Status Flags</label>
                            <div style="display:flex; gap:10px; margin-top:5px;">
                                <select name="is_orphan" style="flex:1;">
                                    <option value="No">Not Orphan</option>
                                    <option value="Yes" <?php if($student['is_orphan']=='Yes') echo 'selected'; ?>>Orphan</option>
                                </select>
                                <select name="is_baitulmal_recipient" style="flex:1;">
                                    <option value="No">No Aid</option>
                                    <option value="Yes" <?php if($student['is_baitulmal_recipient']=='Yes') echo 'selected'; ?>>Baitulmal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-right">
                    <div class="card">
                        <div class="section-title">1. Essential Information</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Student Name</label>
                                <input type="text" name="student_name" value="<?php echo $student['student_name']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Register No</label>
                                <input type="text" name="school_register_no" value="<?php echo $student['school_register_no']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>IC No / Passport</label>
                                <input type="text" name="ic_no" value="<?php echo $student['ic_no']; ?>">
                            </div>
                             <div class="form-group">
                                <label>Birth Cert No</label>
                                <input type="text" name="birth_cert_no" value="<?php echo $student['birth_cert_no']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender">
                                    <option value="Male" <?php if($student['gender']=='Male') echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if($student['gender']=='Female') echo 'selected'; ?>>Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="birthdate" value="<?php echo $student['birthdate']; ?>">
                            </div>
                             <div class="form-group">
                                <label>Place of Birth</label>
                                <input type="text" name="birth_place" value="<?php echo $student['birth_place']; ?>">
                            </div>
                             <div class="form-group">
                                <label>Enrollment Date</label>
                                <input type="date" name="enrollment_date" value="<?php echo $student['enrollment_date']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Race</label>
                                <input type="text" name="race" value="<?php echo $student['race']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Religion</label>
                                <input type="text" name="religion" value="<?php echo $student['religion']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Nationality</label>
                                <input type="text" name="nationality" value="<?php echo $student['nationality']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" value="<?php echo $student['phone']; ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo $student['address']; ?>">
                            </div>
                             <div class="form-group full-width">
                                <label>Previous School</label>
                                <input type="text" name="previous_school" value="<?php echo $student['previous_school']; ?>">
                            </div>
                        </div>

                        <div class="section-title" style="margin-top:30px;">2. Family Information</div>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Parents Marital Status</label>
                                <select name="parents_marital_status" style="width:100%; max-width:200px;">
                                    <option value="Married" <?php if($student['parents_marital_status']=='Married') echo 'selected'; ?>>Married</option>
                                    <option value="Divorced" <?php if($student['parents_marital_status']=='Divorced') echo 'selected'; ?>>Divorced</option>
                                    <option value="Widowed" <?php if($student['parents_marital_status']=='Widowed') echo 'selected'; ?>>Widowed</option>
                                </select>
                            </div>
                        </div>

                        <h4 style="color:#DAA520; border-bottom:1px dashed #ddd; margin-bottom:10px;">Father</h4>
                        <div class="form-grid three-col">
                            <div class="form-group"><label>Name</label><input type="text" name="father_name" value="<?php echo $student['father_name']; ?>"></div>
                            <div class="form-group"><label>IC No</label><input type="text" name="father_ic" value="<?php echo $student['father_ic']; ?>"></div>
                            <div class="form-group"><label>Phone</label><input type="text" name="father_phone" value="<?php echo $student['father_phone']; ?>"></div>
                            <div class="form-group"><label>Job</label><input type="text" name="father_job" value="<?php echo $student['father_job']; ?>"></div>
                            <div class="form-group"><label>Income</label><input type="number" step="0.01" name="father_salary" value="<?php echo $student['father_salary']; ?>"></div>
                        </div>

                        <h4 style="color:#DAA520; border-bottom:1px dashed #ddd; margin-bottom:10px; margin-top:15px;">Mother</h4>
                        <div class="form-grid three-col">
                            <div class="form-group"><label>Name</label><input type="text" name="mother_name" value="<?php echo $student['mother_name']; ?>"></div>
                            <div class="form-group"><label>IC No</label><input type="text" name="mother_ic" value="<?php echo $student['mother_ic']; ?>"></div>
                            <div class="form-group"><label>Phone</label><input type="text" name="mother_phone" value="<?php echo $student['mother_phone']; ?>"></div>
                            <div class="form-group"><label>Job</label><input type="text" name="mother_job" value="<?php echo $student['mother_job']; ?>"></div>
                            <div class="form-group"><label>Income</label><input type="number" step="0.01" name="mother_salary" value="<?php echo $student['mother_salary']; ?>"></div>
                        </div>
                        
                        <h4 style="color:#DAA520; border-bottom:1px dashed #ddd; margin-bottom:10px; margin-top:15px;">Guardian (Optional)</h4>
                        <div class="form-grid three-col">
                            <div class="form-group"><label>Name</label><input type="text" name="guardian_name" value="<?php echo $student['guardian_name']; ?>"></div>
                            <div class="form-group"><label>IC No</label><input type="text" name="guardian_ic" value="<?php echo $student['guardian_ic']; ?>"></div>
                            <div class="form-group"><label>Phone</label><input type="text" name="guardian_phone" value="<?php echo $student['guardian_phone']; ?>"></div>
                            <div class="form-group"><label>Job</label><input type="text" name="guardian_job" value="<?php echo $student['guardian_job']; ?>"></div>
                            <div class="form-group"><label>Income</label><input type="number" step="0.01" name="guardian_salary" value="<?php echo $student['guardian_salary']; ?>"></div>
                        </div>

                        <div class="section-title" style="margin-top:30px;">3. Co-Curriculum</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Sports House</label>
                                <select name="sports_house">
                                    <option value="">- Select -</option>
                                    <option value="Red" <?php if($student['sports_house']=='Red') echo 'selected'; ?>>Red</option>
                                    <option value="Blue" <?php if($student['sports_house']=='Blue') echo 'selected'; ?>>Blue</option>
                                    <option value="Green" <?php if($student['sports_house']=='Green') echo 'selected'; ?>>Green</option>
                                    <option value="Yellow" <?php if($student['sports_house']=='Yellow') echo 'selected'; ?>>Yellow</option>
                                </select>
                            </div>
                            <div class="form-group"></div> <div class="form-group">
                                <label>Uniform Body</label>
                                <input type="text" name="uniform_unit" value="<?php echo $student['uniform_unit']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Position</label>
                                <input type="text" name="uniform_position" value="<?php echo $student['uniform_position']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Club / Association</label>
                                <input type="text" name="club_association" value="<?php echo $student['club_association']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Position</label>
                                <input type="text" name="club_position" value="<?php echo $student['club_position']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Sports / Games</label>
                                <input type="text" name="sports_game" value="<?php echo $student['sports_game']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Position</label>
                                <input type="text" name="sports_position" value="<?php echo $student['sports_position']; ?>">
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top:40px; text-align:right;">
                            <button type="submit" name="update_student" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save All Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('imageUpload').onchange = function (evt) {
    var tgt = evt.target || window.event.srcElement,
        files = tgt.files;
    if (FileReader && files && files.length) {
        var fr = new FileReader();
        fr.onload = function () { document.getElementById('imagePreview').src = fr.result; }
        fr.readAsDataURL(files[0]);
    }
}
</script>

<style>
    .layout-grid { display: grid; grid-template-columns: 300px 1fr; gap: 25px; align-items: start; }
    @media (max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }

    /* Avatar Upload */
    .avatar-upload { position: relative; max-width: 150px; margin: 10px auto; }
    .avatar-preview { width: 150px; height: 150px; border-radius: 50%; border: 4px solid #f8f8f8; box-shadow: 0px 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
    .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-edit { position: absolute; right: 0; bottom: 0; }
    .avatar-edit input { display: none; }
    .avatar-edit label { display: inline-block; width: 34px; height: 34px; border-radius: 100%; background: #DAA520; color: white; border: 2px solid #fff; box-shadow: 0px 2px 4px 0px rgba(0,0,0,0.2); cursor: pointer; text-align: center; line-height: 34px; }
    
    .section-title { font-size: 1.1rem; color: #DAA520; font-weight: 600; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; margin-top: 10px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .three-col { grid-template-columns: 1fr 1fr 1fr; }
    .full-width { grid-column: 1 / -1; }
    
    .btn-lg { padding: 12px 30px; font-size: 1rem; }
    .alert-box { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    .alert-box.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .alert-box.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    
    @media (max-width: 700px) { .form-grid, .three-col { grid-template-columns: 1fr; } }
</style>
</body>
</html>