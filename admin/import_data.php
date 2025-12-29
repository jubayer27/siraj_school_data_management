<?php
// ENABLE ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$msg = "";
$msg_type = "";

// --- HELPER FUNCTIONS ---

// 1. Clean String
function clean($conn, $str)
{
    return $conn->real_escape_string(trim($str ?? ''));
}

// 2. Convert Date (DD/MM/YYYY -> YYYY-MM-DD)
function formatDate($dateStr)
{
    $dateStr = trim($dateStr);
    if (empty($dateStr) || $dateStr == '-' || $dateStr == 'TIADA') {
        return "NULL"; // Returns string "NULL" for SQL
    }
    // Replace / or . with - to help PHP recognize DD-MM-YYYY
    $dateStr = str_replace(['/', '.'], '-', $dateStr);

    $timestamp = strtotime($dateStr);
    if ($timestamp === false) {
        return "NULL";
    }
    return "'" . date('Y-m-d', $timestamp) . "'"; // Returns quoted date '2025-01-01'
}

// 2. HANDLE IMPORT
if (isset($_POST['import_data'])) {
    $type = $_POST['import_type'];
    $file = $_FILES['import_file']['tmp_name'];

    if (is_uploaded_file($file)) {
        $handle = fopen($file, "r");
        $count = 0;
        $row_num = 0;

        while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
            $row_num++;
            if ($row_num == 1)
                continue; // Skip Header
            if (empty(array_filter($data)))
                continue; // Skip Empty Rows

            // ====================================================
            // TYPE 1: STAFF / USERS
            // ====================================================
            if ($type == 'users') {
                if (count($data) < 7)
                    continue;

                $fname = clean($conn, $data[0]);
                $uname = clean($conn, $data[1]);
                $pass = password_hash(clean($conn, $data[2]), PASSWORD_DEFAULT);
                $role = clean($conn, $data[3]);
                $staffid = clean($conn, $data[4]);
                $ic = clean($conn, $data[5]);
                $phone = clean($conn, $data[6]);

                $chk = $conn->query("SELECT user_id FROM users WHERE username = '$uname'");
                if ($chk->num_rows == 0) {
                    $sql = "INSERT INTO users (full_name, username, password, role, teacher_id_no, ic_no, phone) 
                            VALUES ('$fname', '$uname', '$pass', '$role', '$staffid', '$ic', '$phone')";
                    if ($conn->query($sql))
                        $count++;
                }
            }

            // ====================================================
            // TYPE 2: CLASSES
            // ====================================================
            elseif ($type == 'classes') {
                if (count($data) < 3)
                    continue;

                $cname = clean($conn, $data[0]);
                $year = intval($data[1]);
                $staffid = clean($conn, $data[2]);

                $tid = "NULL";
                if ($staffid) {
                    $tq = $conn->query("SELECT user_id FROM users WHERE teacher_id_no = '$staffid' LIMIT 1");
                    if ($tq->num_rows > 0)
                        $tid = $tq->fetch_assoc()['user_id'];
                }

                $chk = $conn->query("SELECT class_id FROM classes WHERE class_name = '$cname' AND year = $year");
                if ($chk->num_rows == 0) {
                    $sql = "INSERT INTO classes (class_name, year, class_teacher_id) VALUES ('$cname', $year, $tid)";
                    if ($conn->query($sql))
                        $count++;
                }
            }

            // ====================================================
            // TYPE 3: SUBJECTS
            // ====================================================
            elseif ($type == 'subjects') {
                if (count($data) < 3)
                    continue;

                $sname = clean($conn, $data[0]);
                $code = clean($conn, $data[1]);
                $cname = clean($conn, $data[2]);
                $staffid = isset($data[3]) ? clean($conn, $data[3]) : '';

                $cid = "NULL";
                if ($cname) {
                    $cq = $conn->query("SELECT class_id FROM classes WHERE class_name = '$cname' LIMIT 1");
                    if ($cq->num_rows > 0)
                        $cid = $cq->fetch_assoc()['class_id'];
                }

                if ($cid != "NULL") {
                    $chk = $conn->query("SELECT subject_id FROM subjects WHERE subject_code = '$code' AND class_id = $cid");
                    if ($chk->num_rows == 0) {
                        $sql = "INSERT INTO subjects (subject_name, subject_code, class_id) VALUES ('$sname', '$code', $cid)";
                        if ($conn->query($sql)) {
                            $count++;
                            if ($staffid) {
                                $new_sub_id = $conn->insert_id;
                                $tq = $conn->query("SELECT user_id FROM users WHERE teacher_id_no = '$staffid'");
                                if ($tq->num_rows > 0) {
                                    $tid = $tq->fetch_assoc()['user_id'];
                                    $conn->query("INSERT INTO subject_teachers (subject_id, teacher_id) VALUES ($new_sub_id, $tid)");
                                }
                            }
                        }
                    }
                }
            }

            // ====================================================
            // TYPE 4: STUDENTS (FULL PROFILE)
            // ====================================================
            elseif ($type == 'students') {
                if (count($data) < 5)
                    continue;

                // 1. Identity
                $reg = clean($conn, $data[0]);
                $name = clean($conn, $data[1]);
                $ic = clean($conn, $data[2]);
                $gender = clean($conn, $data[3]);
                $cname = clean($conn, $data[4]);

                // 2. Personal (Use formatDate function)
                $dob = formatDate($data[5]); // FIXED: Converts Date
                $pob = clean($conn, $data[6]);
                $race = clean($conn, $data[7]);
                $rel = clean($conn, $data[8]);
                $nation = clean($conn, $data[9]);
                $phone = clean($conn, $data[10]);
                $addr = clean($conn, $data[11]);
                $enroll = formatDate($data[12]); // FIXED: Converts Date
                $prev = clean($conn, $data[13]);
                $bcert = clean($conn, $data[14]);

                // 3. Father
                $fname = clean($conn, $data[15]);
                $fic = clean($conn, $data[16]);
                $fphone = clean($conn, $data[17]);
                $fjob = clean($conn, $data[18]);
                $fsal = floatval(clean($conn, $data[19]));

                // 4. Mother
                $mname = clean($conn, $data[20]);
                $mic = clean($conn, $data[21]);
                $mphone = clean($conn, $data[22]);
                $mjob = clean($conn, $data[23]);
                $msal = floatval(clean($conn, $data[24]));

                // 5. Guardian
                $gname = clean($conn, $data[25]);
                $gic = clean($conn, $data[26]);
                $gphone = clean($conn, $data[27]);
                $gjob = clean($conn, $data[28]);
                $gsal = floatval(clean($conn, $data[29]));

                // 6. Status
                $marital = clean($conn, $data[30]);
                $orphan = clean($conn, $data[31]);
                $baitul = clean($conn, $data[32]);

                // 7. Co-Curriculum
                $house = clean($conn, $data[33]);
                $uniform = clean($conn, $data[34]);
                $upos = clean($conn, $data[35]);
                $club = clean($conn, $data[36]);
                $cpos = clean($conn, $data[37]);
                $sport = clean($conn, $data[38]);
                $spos = clean($conn, $data[39]);

                // Logic: Class ID
                $cid = "NULL";
                if ($cname) {
                    $cq = $conn->query("SELECT class_id FROM classes WHERE class_name = '$cname' LIMIT 1");
                    if ($cq->num_rows > 0)
                        $cid = $cq->fetch_assoc()['class_id'];
                }

                // Check & Insert
                $chk = $conn->query("SELECT student_id FROM students WHERE school_register_no = '$reg'");
                if ($chk->num_rows == 0) {
                    // Note: $dob and $enroll are already quoted by formatDate() or are "NULL"
                    $sql = "INSERT INTO students (
                        school_register_no, student_name, ic_no, gender, class_id, 
                        birthdate, birth_place, race, religion, nationality, phone, address, enrollment_date, previous_school, birth_cert_no,
                        father_name, father_ic, father_phone, father_job, father_salary,
                        mother_name, mother_ic, mother_phone, mother_job, mother_salary,
                        guardian_name, guardian_ic, guardian_phone, guardian_job, guardian_salary,
                        parents_marital_status, is_orphan, is_baitulmal_recipient,
                        sports_house, uniform_unit, uniform_position, club_association, club_position, sports_game, sports_position
                    ) VALUES (
                        '$reg', '$name', '$ic', '$gender', $cid,
                        $dob, '$pob', '$race', '$rel', '$nation', '$phone', '$addr', $enroll, '$prev', '$bcert',
                        '$fname', '$fic', '$fphone', '$fjob', $fsal,
                        '$mname', '$mic', '$mphone', '$mjob', $msal,
                        '$gname', '$gic', '$gphone', '$gjob', $gsal,
                        '$marital', '$orphan', '$baitul',
                        '$house', '$uniform', '$upos', '$club', '$cpos', '$sport', '$spos'
                    )";

                    if ($conn->query($sql))
                        $count++;
                    else
                        echo "<div class='alert alert-danger'>Error importing $name: " . $conn->error . "</div>";
                }
            }
        }
        fclose($handle);
        $msg = "Success! Imported $count records.";
        $msg_type = "success";
    } else {
        $msg = "Error: File upload failed.";
        $msg_type = "error";
    }
}
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

    .import-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .card-header-custom {
        background: #2c3e50;
        color: white;
        padding: 20px;
        border-radius: 12px 12px 0 0;
    }

    .code-box {
        background: #2d2d2d;
        color: #76ff03;
        padding: 15px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 0.8rem;
        overflow-x: auto;
        white-space: nowrap;
        margin-top: 10px;
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
            <h2 class="fw-bold text-dark mb-4">Advanced Bulk Import</h2>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo ($msg_type == 'success') ? 'success' : 'danger'; ?> mb-4">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card import-card h-100">
                        <div class="card-header-custom">
                            <h5 class="m-0 fw-bold"><i class="fas fa-file-csv me-2"></i> Import Wizard</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-4">
                                    <label class="fw-bold mb-2">1. Select Data Category</label>
                                    <select name="import_type" class="form-select" required
                                        onchange="updateGuide(this.value)">
                                        <option value="">-- Choose Category --</option>
                                        <option value="users">1. Staff / Users</option>
                                        <option value="classes">2. Classes</option>
                                        <option value="subjects">3. Subjects</option>
                                        <option value="students">4. Students (Full Profile)</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="fw-bold mb-2">2. Upload CSV File</label>
                                    <input type="file" name="import_file" class="form-control" accept=".csv" required>
                                </div>
                                <button type="submit" name="import_data"
                                    class="btn btn-primary fw-bold w-100 py-2">Start Import</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card import-card h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="fw-bold m-0 text-dark"><i class="fas fa-table me-2 text-info"></i> CSV Format
                                Guide</h5>
                        </div>
                        <div class="card-body p-4" id="guide-container">
                            <p class="text-muted">Select a category on the left to view the required columns.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Dynamic Guide Content
    const guides = {
        users: `
        <h6 class="fw-bold text-primary">Staff / Users (7 Cols)</h6>
        <div class="code-box">FullName, Username, Password, Role, StaffID, IC_No, Phone</div>
    `,
        classes: `
        <h6 class="fw-bold text-primary">Classes (3 Cols)</h6>
        <div class="code-box">ClassName, Year, TeacherStaffID</div>
    `,
        subjects: `
        <h6 class="fw-bold text-primary">Subjects (4 Cols)</h6>
        <div class="code-box">SubjectName, SubjectCode, ClassName, TeacherStaffID</div>
    `,
        students: `
        <h6 class="fw-bold text-primary">Students Full Profile (40 Cols)</h6>
        <p class="small text-muted mb-2">Ensure your CSV matches this exact order:</p>
        <div class="code-box">
[0]RegNo, [1]Name, [2]IC, [3]Gender, [4]Class, [5]DOB, [6]BirthPlace, [7]Race, [8]Rel, [9]Nation, [10]Phone, [11]Addr, [12]Enroll, [13]PrevSch, [14]BirthCert, [15]FName, [16]FIC, [17]FPhone, [18]FJob, [19]FSal, [20]MName, [21]MIC, [22]MPhone, [23]MJob, [24]MSal, [25]GName, [26]GIC, [27]GPhone, [28]GJob, [29]GSal, [30]Marital, [31]Orphan, [32]Baitul, [33]House, [34]Uniform, [35]UniPos, [36]Club, [37]ClubPos, [38]Sport, [39]SportPos
        </div>
    `
    };

    function updateGuide(val) {
        const box = document.getElementById('guide-container');
        box.innerHTML = guides[val] || '<p class="text-muted">Select a category.</p>';
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>