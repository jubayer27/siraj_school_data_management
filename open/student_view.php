<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['role'];
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : 0;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'view'; 

// Permission Check: Only Admin and Class Teacher can edit
$can_edit = ($role == 'admin' || $role == 'class_teacher');
if($mode == 'edit' && !$can_edit) $mode = 'view';

// --- HANDLE UPDATE (ALL FIELDS) ---
if(isset($_POST['update_student']) && $can_edit){
    // 1. Personal
    $student_name = $_POST['student_name'];
    $ic_no = $_POST['ic_no'];
    $birth_cert_no = $_POST['birth_cert_no'];
    $birthdate = $_POST['birthdate'];
    $birth_place = $_POST['birth_place'];
    $gender = $_POST['gender'];
    $race = $_POST['race'];
    $religion = $_POST['religion'];
    $nationality = $_POST['nationality'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $previous_school = $_POST['previous_school'];
    $enrollment_date = $_POST['enrollment_date'];
    $school_register_no = $_POST['school_register_no'];
    
    // 2. Family - Father
    $father_name = $_POST['father_name'];
    $father_ic = $_POST['father_ic'];
    $father_phone = $_POST['father_phone'];
    $father_job = $_POST['father_job'];
    $father_salary = $_POST['father_salary'];
    
    // 3. Family - Mother
    $mother_name = $_POST['mother_name'];
    $mother_ic = $_POST['mother_ic'];
    $mother_phone = $_POST['mother_phone'];
    $mother_job = $_POST['mother_job'];
    $mother_salary = $_POST['mother_salary'];
    
    // 4. Family - Guardian
    $guardian_name = $_POST['guardian_name'];
    $guardian_ic = $_POST['guardian_ic'];
    $guardian_phone = $_POST['guardian_phone'];
    $guardian_job = $_POST['guardian_job'];
    $guardian_salary = $_POST['guardian_salary'];
    
    // 5. Status
    $parents_marital_status = $_POST['parents_marital_status'];
    $is_orphan = $_POST['is_orphan'];
    $is_baitulmal_recipient = $_POST['is_baitulmal_recipient'];
    
    // 6. Co-Curriculum
    $uniform_unit = $_POST['uniform_unit'];
    $uniform_position = $_POST['uniform_position'];
    $club_association = $_POST['club_association'];
    $club_position = $_POST['club_position'];
    $sports_game = $_POST['sports_game'];
    $sports_position = $_POST['sports_position'];
    $sports_house = $_POST['sports_house'];

    $sql = "UPDATE students SET 
            student_name=?, ic_no=?, birth_cert_no=?, birthdate=?, birth_place=?, gender=?, race=?, religion=?, nationality=?, phone=?, address=?, previous_school=?, enrollment_date=?, school_register_no=?,
            father_name=?, father_ic=?, father_phone=?, father_job=?, father_salary=?,
            mother_name=?, mother_ic=?, mother_phone=?, mother_job=?, mother_salary=?,
            guardian_name=?, guardian_ic=?, guardian_phone=?, guardian_job=?, guardian_salary=?,
            parents_marital_status=?, is_orphan=?, is_baitulmal_recipient=?,
            uniform_unit=?, uniform_position=?, club_association=?, club_position=?, sports_game=?, sports_position=?, sports_house=?
            WHERE student_id=?";
            
    $stmt = $conn->prepare($sql);
    
    // This is a long bind string for 39 variables + 1 ID (all strings 's' except salary 'd' logic, but 's' works for inputs mostly. Let's use 's' for simplicity except ID)
    // Actually, bind_param types: s=string, d=double, i=int. 
    // We have 39 fields. Let's assume all strings for safety in this demo, except ID.
    $types = str_repeat("s", 39) . "i"; 
    
    $stmt->bind_param($types, 
        $student_name, $ic_no, $birth_cert_no, $birthdate, $birth_place, $gender, $race, $religion, $nationality, $phone, $address, $previous_school, $enrollment_date, $school_register_no,
        $father_name, $father_ic, $father_phone, $father_job, $father_salary,
        $mother_name, $mother_ic, $mother_phone, $mother_job, $mother_salary,
        $guardian_name, $guardian_ic, $guardian_phone, $guardian_job, $guardian_salary,
        $parents_marital_status, $is_orphan, $is_baitulmal_recipient,
        $uniform_unit, $uniform_position, $club_association, $club_position, $sports_game, $sports_position, $sports_house,
        $student_id
    );
    
    if($stmt->execute()){
        $success_msg = "All student data updated successfully!";
        $mode = 'view'; 
    } else {
        $error_msg = "Error updating: " . $stmt->error;
    }
}

// --- FETCH DATA ---
$sql = "SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student) die("Student not found.");

// --- LOAD HEADER ---
if($role == 'admin') include '../admin/includes/header.php';
else include '../includes/header.php';
?>

<div class="wrapper">
    <?php 
        if($role == 'admin') include '../admin/includes/sidebar.php';
        elseif($role == 'class_teacher') include '../includes/sidebar_classteacher.php';
        else include '../includes/sidebar.php';
    ?>

    <div class="main-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1>Full Student Profile</h1>
            <?php if($can_edit && $mode == 'view'): ?>
                <a href="student_view.php?student_id=<?php echo $student_id; ?>&mode=edit" class="btn btn-primary">Edit All Info</a>
            <?php elseif($mode == 'edit'): ?>
                <a href="student_view.php?student_id=<?php echo $student_id; ?>&mode=view" class="btn btn-danger">Cancel Edit</a>
            <?php endif; ?>
        </div>

        <?php if(isset($success_msg)) echo "<div style='padding:15px; background:#d4edda; color:#155724; border-radius:5px; margin-bottom:20px;'>$success_msg</div>"; ?>
        <?php if(isset($error_msg)) echo "<div style='padding:15px; background:#f8d7da; color:#721c24; border-radius:5px; margin-bottom:20px;'>$error_msg</div>"; ?>

        <form method="POST">
            <div style="display:flex; gap:30px; align-items:flex-start;">
                
                <div class="card" style="width: 250px; text-align:center; position:sticky; top:20px;">
                    <?php $photo = $student['photo'] ? "../uploads/".$student['photo'] : "https://via.placeholder.com/200?text=No+Photo"; ?>
                    <img src="<?php echo $photo; ?>" style="width:100%; border-radius:10px; border:1px solid #ddd;">
                    <h3 style="margin:15px 0 5px;"><?php echo $student['student_name']; ?></h3>
                    <p style="color:#666; margin:0;"><?php echo $student['school_register_no']; ?></p>
                    <div style="background:#FFD200; color:#000; padding:5px; margin-top:10px; border-radius:4px; font-weight:bold;">
                        <?php echo $student['class_name'] ? $student['class_name'] : 'No Class'; ?>
                    </div>
                </div>

                <div style="flex:1;">
                    
                    <div class="card">
                        <h3 class="section-title">1. Personal Information</h3>
                        <div class="grid-container">
                            <?php 
                            renderField("Full Name", "student_name", $student['student_name'], $mode);
                            renderField("IC No", "ic_no", $student['ic_no'], $mode);
                            renderField("Birth Cert No", "birth_cert_no", $student['birth_cert_no'], $mode);
                            renderField("Date of Birth", "birthdate", $student['birthdate'], $mode, "date");
                            renderField("Place of Birth", "birth_place", $student['birth_place'], $mode);
                            renderField("Gender", "gender", $student['gender'], $mode, "select", ["Male","Female"]);
                            renderField("Race", "race", $student['race'], $mode);
                            renderField("Religion", "religion", $student['religion'], $mode);
                            renderField("Nationality", "nationality", $student['nationality'], $mode);
                            renderField("Phone (Student)", "phone", $student['phone'], $mode);
                            renderField("Previous School", "previous_school", $student['previous_school'], $mode);
                            renderField("Enrollment Date", "enrollment_date", $student['enrollment_date'], $mode, "date");
                            renderField("Register No", "school_register_no", $student['school_register_no'], $mode);
                            ?>
                            <div style="grid-column: span 3;">
                                <label>Address</label>
                                <?php if($mode=='edit'): ?>
                                    <textarea name="address" rows="2"><?php echo $student['address']; ?></textarea>
                                <?php else: ?>
                                    <div class="view-field"><?php echo $student['address']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="section-title">2. Family Information</h3>
                        
                        <h4 style="margin-top:0; color:#F7971E;">Father Details</h4>
                        <div class="grid-container">
                            <?php 
                            renderField("Father Name", "father_name", $student['father_name'], $mode);
                            renderField("Father IC", "father_ic", $student['father_ic'], $mode);
                            renderField("Father Phone", "father_phone", $student['father_phone'], $mode);
                            renderField("Job", "father_job", $student['father_job'], $mode);
                            renderField("Salary", "father_salary", $student['father_salary'], $mode, "number");
                            ?>
                        </div>

                        <h4 style="margin-top:20px; color:#F7971E;">Mother Details</h4>
                        <div class="grid-container">
                            <?php 
                            renderField("Mother Name", "mother_name", $student['mother_name'], $mode);
                            renderField("Mother IC", "mother_ic", $student['mother_ic'], $mode);
                            renderField("Mother Phone", "mother_phone", $student['mother_phone'], $mode);
                            renderField("Job", "mother_job", $student['mother_job'], $mode);
                            renderField("Salary", "mother_salary", $student['mother_salary'], $mode, "number");
                            ?>
                        </div>

                        <h4 style="margin-top:20px; color:#F7971E;">Guardian Details (If applicable)</h4>
                        <div class="grid-container">
                            <?php 
                            renderField("Guardian Name", "guardian_name", $student['guardian_name'], $mode);
                            renderField("Guardian IC", "guardian_ic", $student['guardian_ic'], $mode);
                            renderField("Guardian Phone", "guardian_phone", $student['guardian_phone'], $mode);
                            renderField("Job", "guardian_job", $student['guardian_job'], $mode);
                            renderField("Salary", "guardian_salary", $student['guardian_salary'], $mode, "number");
                            ?>
                        </div>

                        <h4 style="margin-top:20px; color:#F7971E;">Family Status</h4>
                        <div class="grid-container">
                            <?php 
                            renderField("Parents Marital Status", "parents_marital_status", $student['parents_marital_status'], $mode, "select", ["Married","Divorced","Widowed","Separated"]);
                            renderField("Is Orphan?", "is_orphan", $student['is_orphan'], $mode, "select", ["No","Yes"]);
                            renderField("Baitulmal Recipient?", "is_baitulmal_recipient", $student['is_baitulmal_recipient'], $mode, "select", ["No","Yes"]);
                            ?>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="section-title">3. Co-Curriculum</h3>
                        <div class="grid-container">
                            <?php 
                            renderField("Uniform Unit", "uniform_unit", $student['uniform_unit'], $mode);
                            renderField("Uniform Position", "uniform_position", $student['uniform_position'], $mode);
                            renderField("Club/Association", "club_association", $student['club_association'], $mode);
                            renderField("Club Position", "club_position", $student['club_position'], $mode);
                            renderField("Sports Game", "sports_game", $student['sports_game'], $mode);
                            renderField("Sports Position", "sports_position", $student['sports_position'], $mode);
                            renderField("Sports House", "sports_house", $student['sports_house'], $mode, "select", ["Red", "Blue", "Green", "Yellow"]);
                            ?>
                        </div>
                    </div>

                    <?php if($mode == 'edit'): ?>
                        <div style="text-align:right; margin-bottom:50px;">
                            <button type="submit" name="update_student" class="btn btn-success" style="padding:15px 30px; font-size:1.1em;">Save All Changes</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .grid-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    .section-title {
        border-bottom: 2px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 20px;
        color: #333;
    }
    .view-field {
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #eee;
        border-radius: 4px;
        color: #555;
        min-height: 20px;
    }
    label { font-size: 0.85em; color: #777; font-weight:bold; display:block; margin-bottom:5px; }
    textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius:4px; }
</style>

<?php
// HELPER FUNCTION TO RENDER FIELDS CLEANLY
function renderField($label, $name, $value, $mode, $type="text", $options=[]) {
    echo "<div>";
    echo "<label>$label</label>";
    
    if($mode == 'edit'){
        if($type == 'select'){
            echo "<select name='$name'>";
            echo "<option value=''>- Select -</option>";
            foreach($options as $opt){
                $sel = ($value == $opt) ? 'selected' : '';
                echo "<option value='$opt' $sel>$opt</option>";
            }
            echo "</select>";
        } else {
            echo "<input type='$type' name='$name' value='$value'>";
        }
    } else {
        $display = $value ? $value : '-';
        echo "<div class='view-field'>$display</div>";
    }
    echo "</div>";
}
?>
</body>
</html>