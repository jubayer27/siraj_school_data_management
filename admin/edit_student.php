<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); exit();
}

if(!isset($_GET['student_id'])){
    echo "<script>window.location='manage_students.php';</script>";
    exit();
}

$sid = $_GET['student_id'];
$success = "";
$error = "";

// 2. HANDLE UPDATE SUBMISSION
if(isset($_POST['update_student'])){
    
    // --- A. HANDLE PHOTO UPLOAD ---
    if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
        $target_dir = "../uploads/";
        if(!is_dir($target_dir)) mkdir($target_dir);
        
        $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid("stu_") . "." . $ext;
        
        if(move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $new_filename)){
            $conn->query("UPDATE students SET photo='$new_filename' WHERE student_id=$sid");
        }
    }

    // --- B. COLLECT DATA ---
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

    // Parents
    $f_name = $_POST['father_name']; $f_ic = $_POST['father_ic']; $f_phone = $_POST['father_phone']; 
    $f_job = $_POST['father_job']; $f_sal = !empty($_POST['father_salary']) ? $_POST['father_salary'] : 0.00;
    
    $m_name = $_POST['mother_name']; $m_ic = $_POST['mother_ic']; $m_phone = $_POST['mother_phone']; 
    $m_job = $_POST['mother_job']; $m_sal = !empty($_POST['mother_salary']) ? $_POST['mother_salary'] : 0.00;
    
    $g_name = $_POST['guardian_name']; $g_ic = $_POST['guardian_ic']; $g_phone = $_POST['guardian_phone']; 
    $g_job = $_POST['guardian_job']; $g_sal = !empty($_POST['guardian_salary']) ? $_POST['guardian_salary'] : 0.00;
    
    $marital = $_POST['parents_marital_status'];
    $orphan = $_POST['is_orphan'];
    $baitulmal = $_POST['is_baitulmal_recipient'];
    
    // Co-Q
    $uniform = $_POST['uniform_unit']; $u_pos = $_POST['uniform_position'];
    $club = $_POST['club_association']; $c_pos = $_POST['club_position'];
    $sport = $_POST['sports_game']; $s_pos = $_POST['sports_position'];
    $house = $_POST['sports_house'];

    // --- C. EXECUTE UPDATE ---
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
    
    // Types: 15s + 15s + 3s + 7s + 1i
    // Correct bind types (d for double/salary)
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
        $success = "Profile updated successfully!";
        echo "<script>setTimeout(function(){ window.location.href = window.location.href; }, 1500);</script>";
    } else {
        $error = "Error updating: " . $stmt->error;
    }
}

// 3. FETCH DATA
$student = $conn->query("SELECT * FROM students WHERE student_id = $sid")->fetch_assoc();
if(!$student) die("Student not found.");
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { background-color: #f4f6f9; overflow-x: hidden; }
    
    /* Layout Fix */
    .main-content {
        position: absolute; top: 0; right: 0;
        width: calc(100% - 260px) !important;
        margin-left: 260px !important;
        min-height: 100vh; padding: 0 !important;
        display: block !important;
    }
    .container-fluid { padding: 30px !important; }

    /* Custom Cards */
    .edit-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 20px; }
    .card-header-custom { background: white; padding: 15px 20px; border-bottom: 1px solid #eee; font-weight: 700; color: #DAA520; }
    
    /* Avatar Upload */
    .avatar-upload { position: relative; max-width: 150px; margin: 0 auto 20px; }
    .avatar-preview { width: 150px; height: 150px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); overflow: hidden; }
    .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-edit { position: absolute; right: 0; bottom: 10px; }
    .avatar-edit input { display: none; }
    .avatar-edit label { width: 34px; height: 34px; background: #DAA520; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }

    /* Forms */
    .form-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: #777; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 6px; padding: 10px 12px; font-size: 0.95rem; border: 1px solid #dee2e6; }
    .form-control:focus { border-color: #FFD700; box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1); }
    
    @media (max-width: 992px) { .main-content { width: 100% !important; margin-left: 0 !important; } }
</style>

<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Edit Profile</h2>
                    <p class="text-secondary mb-0">Updating: <strong><?php echo $student['student_name']; ?></strong></p>
                </div>
                <a href="view_student.php?student_id=<?php echo $sid; ?>" class="btn btn-light border shadow-sm">
                    <i class="fas fa-times me-2"></i> Cancel
                </a>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success d-flex align-items-center"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    
                    <div class="col-lg-3">
                        <div class="card edit-card text-center p-4">
                            <div class="avatar-upload">
                                <div class="avatar-preview">
                                    <?php $photo = $student['photo'] ? "../uploads/".$student['photo'] : "https://ui-avatars.com/api/?name=".$student['student_name']."&background=random"; ?>
                                    <img id="imagePreview" src="<?php echo $photo; ?>">
                                </div>
                                <div class="avatar-edit">
                                    <input type='file' name="photo" id="imageUpload" accept=".png, .jpg, .jpeg" />
                                    <label for="imageUpload"><i class="fas fa-camera"></i></label>
                                </div>
                            </div>
                            <h5 class="fw-bold"><?php echo $student['student_name']; ?></h5>
                            <p class="text-muted small"><?php echo $student['school_register_no']; ?></p>
                            
                            <hr>
                            
                            <div class="text-start">
                                <label class="form-label">Class Assignment</label>
                                <select name="class_id" class="form-select mb-3">
                                    <option value="">-- Unassigned --</option>
                                    <?php 
                                    $classes->data_seek(0);
                                    while($c = $classes->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $c['class_id']; ?>" <?php echo ($c['class_id'] == $student['class_id']) ? 'selected' : ''; ?>>
                                            <?php echo $c['class_name']; ?> (<?php echo $c['year']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>

                                <label class="form-label">Orphan Status</label>
                                <select name="is_orphan" class="form-select mb-3">
                                    <option value="No">No</option>
                                    <option value="Yes" <?php echo ($student['is_orphan']=='Yes')?'selected':''; ?>>Yes</option>
                                </select>

                                <label class="form-label">Baitulmal Aid</label>
                                <select name="is_baitulmal_recipient" class="form-select">
                                    <option value="No">No</option>
                                    <option value="Yes" <?php echo ($student['is_baitulmal_recipient']=='Yes')?'selected':''; ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-9">
                        
                        <div class="card edit-card">
                            <div class="card-header-custom"><i class="fas fa-user me-2"></i> 1. Personal Information</div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="student_name" class="form-control" value="<?php echo $student['student_name']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">School Reg No</label>
                                        <input type="text" name="school_register_no" class="form-control" value="<?php echo $student['school_register_no']; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">IC No / Passport</label>
                                        <input type="text" name="ic_no" class="form-control" value="<?php echo $student['ic_no']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Birth Cert No</label>
                                        <input type="text" name="birth_cert_no" class="form-control" value="<?php echo $student['birth_cert_no']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select name="gender" class="form-select">
                                            <option value="Male" <?php echo ($student['gender']=='Male')?'selected':''; ?>>Male</option>
                                            <option value="Female" <?php echo ($student['gender']=='Female')?'selected':''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="birthdate" class="form-control" value="<?php echo $student['birthdate']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Place of Birth</label>
                                        <input type="text" name="birth_place" class="form-control" value="<?php echo $student['birth_place']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Enrollment Date</label>
                                        <input type="date" name="enrollment_date" class="form-control" value="<?php echo $student['enrollment_date']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Race</label>
                                        <input type="text" name="race" class="form-control" value="<?php echo $student['race']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Religion</label>
                                        <input type="text" name="religion" class="form-control" value="<?php echo $student['religion']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nationality</label>
                                        <input type="text" name="nationality" class="form-control" value="<?php echo $student['nationality']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact No</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo $student['phone']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Previous School</label>
                                        <input type="text" name="previous_school" class="form-control" value="<?php echo $student['previous_school']; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Home Address</label>
                                        <input type="text" name="address" class="form-control" value="<?php echo $student['address']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card edit-card">
                            <div class="card-header-custom"><i class="fas fa-users me-2"></i> 2. Family Information</div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label">Parents Marital Status</label>
                                    <select name="parents_marital_status" class="form-select w-auto">
                                        <option value="Married" <?php if($student['parents_marital_status']=='Married') echo 'selected'; ?>>Married</option>
                                        <option value="Divorced" <?php if($student['parents_marital_status']=='Divorced') echo 'selected'; ?>>Divorced</option>
                                        <option value="Widowed" <?php if($student['parents_marital_status']=='Widowed') echo 'selected'; ?>>Widowed</option>
                                    </select>
                                </div>
                                
                                <h6 class="text-primary fw-bold mt-3 mb-3 border-bottom pb-2">Father's Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="father_name" class="form-control" value="<?php echo $student['father_name']; ?>"></div>
                                    <div class="col-md-4"><label class="form-label">IC No</label><input type="text" name="father_ic" class="form-control" value="<?php echo $student['father_ic']; ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="father_phone" class="form-control" value="<?php echo $student['father_phone']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Occupation</label><input type="text" name="father_job" class="form-control" value="<?php echo $student['father_job']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Income (RM)</label><input type="number" step="0.01" name="father_salary" class="form-control" value="<?php echo $student['father_salary']; ?>"></div>
                                </div>

                                <h6 class="text-danger fw-bold mt-4 mb-3 border-bottom pb-2">Mother's Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="mother_name" class="form-control" value="<?php echo $student['mother_name']; ?>"></div>
                                    <div class="col-md-4"><label class="form-label">IC No</label><input type="text" name="mother_ic" class="form-control" value="<?php echo $student['mother_ic']; ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="mother_phone" class="form-control" value="<?php echo $student['mother_phone']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Occupation</label><input type="text" name="mother_job" class="form-control" value="<?php echo $student['mother_job']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Income (RM)</label><input type="number" step="0.01" name="mother_salary" class="form-control" value="<?php echo $student['mother_salary']; ?>"></div>
                                </div>
                                
                                <h6 class="text-secondary fw-bold mt-4 mb-3 border-bottom pb-2">Guardian Details (Optional)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="guardian_name" class="form-control" value="<?php echo $student['guardian_name']; ?>"></div>
                                    <div class="col-md-4"><label class="form-label">IC No</label><input type="text" name="guardian_ic" class="form-control" value="<?php echo $student['guardian_ic']; ?>"></div>
                                    <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="guardian_phone" class="form-control" value="<?php echo $student['guardian_phone']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Occupation</label><input type="text" name="guardian_job" class="form-control" value="<?php echo $student['guardian_job']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Income (RM)</label><input type="number" step="0.01" name="guardian_salary" class="form-control" value="<?php echo $student['guardian_salary']; ?>"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card edit-card">
                            <div class="card-header-custom"><i class="fas fa-medal me-2"></i> 3. Co-Curriculum</div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Sports House</label>
                                        <select name="sports_house" class="form-select w-auto">
                                            <option value="">- Select -</option>
                                            <option value="Red" <?php if($student['sports_house']=='Red') echo 'selected'; ?>>Red</option>
                                            <option value="Blue" <?php if($student['sports_house']=='Blue') echo 'selected'; ?>>Blue</option>
                                            <option value="Green" <?php if($student['sports_house']=='Green') echo 'selected'; ?>>Green</option>
                                            <option value="Yellow" <?php if($student['sports_house']=='Yellow') echo 'selected'; ?>>Yellow</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6"><label class="form-label">Uniform Body</label><input type="text" name="uniform_unit" class="form-control" value="<?php echo $student['uniform_unit']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Position</label><input type="text" name="uniform_position" class="form-control" value="<?php echo $student['uniform_position']; ?>"></div>
                                    
                                    <div class="col-md-6"><label class="form-label">Club / Association</label><input type="text" name="club_association" class="form-control" value="<?php echo $student['club_association']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Position</label><input type="text" name="club_position" class="form-control" value="<?php echo $student['club_position']; ?>"></div>
                                    
                                    <div class="col-md-6"><label class="form-label">Sports / Game</label><input type="text" name="sports_game" class="form-control" value="<?php echo $student['sports_game']; ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Position</label><input type="text" name="sports_position" class="form-control" value="<?php echo $student['sports_position']; ?>"></div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mb-5">
                            <button type="submit" name="update_student" class="btn btn-warning btn-lg fw-bold px-5">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    
                    </div>
                </div>
            </form>
            
        </div>
    </div>
</div>

<script>
// Image Preview Script
document.getElementById('imageUpload').onchange = function (evt) {
    var tgt = evt.target || window.event.srcElement, files = tgt.files;
    if (FileReader && files && files.length) {
        var fr = new FileReader();
        fr.onload = function () { document.getElementById('imagePreview').src = fr.result; }
        fr.readAsDataURL(files[0]);
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>