<?php
// ENABLE ERROR REPORTING FOR DEBUGGING
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
    $dateStr = trim($dateStr ?? '');
    // Check for empty or invalid placeholder dates
    if (empty($dateStr) || $dateStr == '-' || strtoupper($dateStr) == 'TIADA' || strtoupper($dateStr) == 'NULL') {
        return "NULL"; // Returns SQL NULL keyword
    }
    // Replace / or . with - to help PHP recognize DD-MM-YYYY
    $dateStr = str_replace(['/', '.'], '-', $dateStr);

    $timestamp = strtotime($dateStr);
    if ($timestamp === false) {
        return "NULL";
    }
    // Returns quoted date string for SQL, e.g., '2025-01-01'
    return "'" . date('Y-m-d', $timestamp) . "'";
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
            // TYPE A: STUDENTS (FULL PROFILE - 40 Columns)
            // ====================================================
            if ($type == 'students') {
                if (count($data) < 5)
                    continue; // Minimal check

                // --- 1. Identity & Class ---
                $reg_no = clean($conn, $data[0]);
                $name = clean($conn, $data[1]);
                $ic = clean($conn, $data[2]);
                $gender = clean($conn, $data[3]);
                $class_name = clean($conn, $data[4]); // Linker

                // --- 2. Personal Details (Use formatDate for dates) ---
                $dob = formatDate($data[5]); // FIXED: Converts DD/MM/YYYY to YYYY-MM-DD
                $pob = clean($conn, $data[6]);
                $race = clean($conn, $data[7]);
                $religion = clean($conn, $data[8]);
                $nation = clean($conn, $data[9]);
                $phone = clean($conn, $data[10]);
                $address = clean($conn, $data[11]);
                $enroll_date = formatDate($data[12]); // FIXED: Converts DD/MM/YYYY to YYYY-MM-DD
                $prev_sch = clean($conn, $data[13]);
                $birth_cert = clean($conn, $data[14]);

                // --- 3. Family (Father) ---
                $f_name = clean($conn, $data[15]);
                $f_ic = clean($conn, $data[16]);
                $f_phone = clean($conn, $data[17]);
                $f_job = clean($conn, $data[18]);
                $f_sal = floatval($data[19]);

                // --- 4. Family (Mother) ---
                $m_name = clean($conn, $data[20]);
                $m_ic = clean($conn, $data[21]);
                $m_phone = clean($conn, $data[22]);
                $m_job = clean($conn, $data[23]);
                $m_sal = floatval($data[24]);

                // --- 5. Family (Guardian) ---
                $g_name = clean($conn, $data[25]);
                $g_ic = clean($conn, $data[26]);
                $g_phone = clean($conn, $data[27]);
                $g_job = clean($conn, $data[28]);
                $g_sal = floatval($data[29]);

                // --- 6. Status ---
                $marital = clean($conn, $data[30]);
                $orphan = clean($conn, $data[31]);
                $baitulmal = clean($conn, $data[32]);

                // --- 7. Co-Curriculum ---
                $house = clean($conn, $data[33]);
                $uniform = clean($conn, $data[34]);
                $u_pos = clean($conn, $data[35]);
                $club = clean($conn, $data[36]);
                $c_pos = clean($conn, $data[37]);
                $sport = clean($conn, $data[38]);
                $s_pos = clean($conn, $data[39]);

                // LOGIC: Find Class ID
                $cid = "NULL";
                if ($class_name) {
                    $cq = $conn->query("SELECT class_id FROM classes WHERE class_name = '$class_name'");
                    if ($cq->num_rows > 0)
                        $cid = $cq->fetch_assoc()['class_id'];
                }

                // LOGIC: Insert if Reg No doesn't exist
                $chk = $conn->query("SELECT student_id FROM students WHERE school_register_no = '$reg_no'");
                if ($chk->num_rows == 0) {
                    // NOTE: $dob and $enroll_date are NOT quoted here because formatDate() adds the quotes
                    $sql = "INSERT INTO students (
                        school_register_no, student_name, ic_no, gender, class_id, 
                        birthdate, birth_place, race, religion, nationality, phone, address, enrollment_date, previous_school, birth_cert_no,
                        father_name, father_ic, father_phone, father_job, father_salary,
                        mother_name, mother_ic, mother_phone, mother_job, mother_salary,
                        guardian_name, guardian_ic, guardian_phone, guardian_job, guardian_salary,
                        parents_marital_status, is_orphan, is_baitulmal_recipient,
                        sports_house, uniform_unit, uniform_position, club_association, club_position, sports_game, sports_position
                    ) VALUES (
                        '$reg_no', '$name', '$ic', '$gender', $cid,
                        $dob, '$pob', '$race', '$religion', '$nation', '$phone', '$address', $enroll_date, '$prev_sch', '$birth_cert',
                        '$f_name', '$f_ic', '$f_phone', '$f_job', $f_sal,
                        '$m_name', '$m_ic', '$m_phone', '$m_job', $m_sal,
                        '$g_name', '$g_ic', '$g_phone', '$g_job', $g_sal,
                        '$marital', '$orphan', '$baitulmal',
                        '$house', '$uniform', '$u_pos', '$club', '$c_pos', '$sport', '$s_pos'
                    )";
                    if ($conn->query($sql))
                        $count++;
                    else
                        echo "<div class='alert alert-danger'>Error importing $name: " . $conn->error . "</div>";
                }
            }

            // ====================================================
            // TYPE B: USERS / STAFF (Full Details)
            // ====================================================
            elseif ($type == 'users') {
                if (count($data) < 7)
                    continue;

                $fname = clean($conn, $data[0]);
                $uname = clean($conn, $data[1]);
                $pass = password_hash(clean($conn, $data[2]), PASSWORD_DEFAULT);
                $role = strtolower(clean($conn, $data[3]));
                $staffid = clean($conn, $data[4]);
                $ic = clean($conn, $data[5]);
                $phone = clean($conn, $data[6]);

                $chk = $conn->query("SELECT user_id FROM users WHERE username = '$uname'");
                if ($chk->num_rows == 0) {
                    $conn->query("INSERT INTO users (full_name, username, password, role, teacher_id_no, ic_no, phone) 
                                  VALUES ('$fname', '$uname', '$pass', '$role', '$staffid', '$ic', '$phone')");
                    $count++;
                }
            }

            // ====================================================
            // TYPE C: CLASSES (With Teacher Linking)
            // ====================================================
            elseif ($type == 'classes') {
                if (count($data) < 3)
                    continue;

                $name = clean($conn, $data[0]);
                $year = intval($data[1]);
                $teacher_staff_id = clean($conn, $data[2]);

                $tid = "NULL";
                if ($teacher_staff_id) {
                    $tq = $conn->query("SELECT user_id FROM users WHERE teacher_id_no = '$teacher_staff_id'");
                    if ($tq->num_rows > 0)
                        $tid = $tq->fetch_assoc()['user_id'];
                }

                $chk = $conn->query("SELECT class_id FROM classes WHERE class_name = '$name' AND year = $year");
                if ($chk->num_rows == 0) {
                    $conn->query("INSERT INTO classes (class_name, year, class_teacher_id) VALUES ('$name', $year, $tid)");
                    $count++;
                }
            }

            // ====================================================
            // TYPE D: SUBJECTS (UPDATED FOR MULTIPLE TEACHERS)
            // ====================================================
            elseif ($type == 'subjects') {
                if (count($data) < 3)
                    continue;

                $name = clean($conn, $data[0]);
                $code = clean($conn, $data[1]);
                $cname = clean($conn, $data[2]);
                $tsid = isset($data[3]) ? clean($conn, $data[3]) : '';

                $cid = "NULL";
                if ($cname) {
                    $cq = $conn->query("SELECT class_id FROM classes WHERE class_name = '$cname'");
                    if ($cq->num_rows > 0)
                        $cid = $cq->fetch_assoc()['class_id'];
                }

                $chk = $conn->query("SELECT subject_id FROM subjects WHERE subject_code = '$code'");

                if ($chk->num_rows == 0) {
                    // A. Insert Subject
                    if ($cid != "NULL") {
                        $sql = "INSERT INTO subjects (subject_name, subject_code, class_id) VALUES ('$name', '$code', $cid)";
                    } else {
                        $sql = "INSERT INTO subjects (subject_name, subject_code) VALUES ('$name', '$code')";
                    }

                    if ($conn->query($sql)) {
                        $new_sub_id = $conn->insert_id;
                        $count++;

                        // B. Resolve Teacher ID & Insert to Junction Table
                        if ($tsid) {
                            $tq = $conn->query("SELECT user_id FROM users WHERE teacher_id_no = '$tsid'");
                            if ($tq->num_rows > 0) {
                                $tid = $tq->fetch_assoc()['user_id'];
                                $conn->query("INSERT INTO subject_teachers (subject_id, teacher_id) VALUES ($new_sub_id, $tid)");
                            }
                        }
                    }
                }
            }
        }
        fclose($handle);
        $msg = "Success! Imported $count records.";
        $msg_type = "success";
    } else {
        $msg = "Error: File is empty or invalid.";
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

    /* Import Card */
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

    /* Code Box */
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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Advanced Bulk Import</h2>
                    <p class="text-secondary mb-0">Upload complete datasets via CSV.</p>
                </div>
            </div>

            <?php if ($msg): ?>
                <div
                    class="alert alert-<?php echo ($msg_type == 'success') ? 'success' : 'danger'; ?> d-flex align-items-center mb-4">
                    <i
                        class="fas fa-<?php echo ($msg_type == 'success') ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-5">
                    <div class="card import-card h-100">
                        <div class="card-header-custom">
                            <h5 class="m-0 fw-bold"><i class="fas fa-cloud-upload-alt me-2"></i> Import Wizard</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" enctype="multipart/form-data">

                                <div class="mb-4">
                                    <label class="fw-bold mb-2">1. Select Data Category</label>
                                    <select name="import_type" class="form-select" required
                                        onchange="updateGuide(this.value)">
                                        <option value="">-- Choose Category --</option>
                                        <option value="users">Staff / Users (Import First)</option>
                                        <option value="classes">Classes (Import Second)</option>
                                        <option value="subjects">Subjects (Import Third)</option>
                                        <option value="students">Students (Import Last)</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="fw-bold mb-2">2. Upload CSV File</label>
                                    <input type="file" name="import_file" class="form-control" accept=".csv" required>
                                    <div class="form-text">File must be .csv format (Comma Delimited).</div>
                                </div>

                                <button type="submit" name="import_data" class="btn btn-primary fw-bold w-100 py-2">
                                    Start Import Process
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card import-card h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="fw-bold m-0 text-dark"><i class="fas fa-info-circle me-2 text-info"></i> CSV
                                Format Guide</h5>
                        </div>
                        <div class="card-body p-4" id="guide-container">
                            <p class="text-muted">Select a category on the left to view the required CSV columns.</p>
                            <div class="alert alert-warning border-0 small">
                                <strong>Tip:</strong> Follow the Import Order (Staff -> Classes -> Subjects -> Students)
                                to ensure all data links correctly.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    const guides = {
        users: `
        <h6 class="fw-bold text-primary">Staff / Users CSV Columns (7 cols)</h6>
        <p class="small text-muted">Use this to create admin, teachers, and staff accounts.</p>
        <div class="code-box">
FullName, Username, Password, Role, StaffID, IC_No, Phone
        </div>
        <p class="small mt-2"><strong>Role Options:</strong> admin, class_teacher, subject_teacher</p>
    `,
        classes: `
        <h6 class="fw-bold text-primary">Classes CSV Columns (3 cols)</h6>
        <p class="small text-muted">Links to teachers using their Staff ID.</p>
        <div class="code-box">
ClassName, Year, Teacher_Staff_ID
        </div>
        <p class="small mt-2">Example: 5 Amanah, 2025, T-001</p>
    `,
        subjects: `
        <h6 class="fw-bold text-primary">Subjects CSV Columns (4 cols)</h6>
        <p class="small text-muted">Links to Class Name and Teacher Staff ID.</p>
        <div class="code-box">
SubjectName, SubjectCode, ClassName, Teacher_Staff_ID
        </div>
        <p class="small mt-2">Example: Math, MTH-5A, 5 Amanah, T-001</p>
    `,
        students: `
        <h6 class="fw-bold text-primary">Students Full Profile (40 cols)</h6>
        <p class="small text-muted">Contains Personal, Family, and Co-Q details.</p>
        <div class="code-box">
[0]RegNo, [1]Name, [2]IC, [3]Gender, [4]Class_Name, [5]DOB, [6]BirthPlace, [7]Race, [8]Religion, [9]Nationality, [10]Phone, [11]Address, [12]EnrollDate, [13]PrevSchool, [14]BirthCert, [15]FatherName, [16]FatherIC, [17]FatherPhone, [18]FatherJob, [19]FatherSalary, [20]MotherName, [21]MotherIC, [22]MotherPhone, [23]MotherJob, [24]MotherSalary, [25]GuardName, [26]GuardIC, [27]GuardPhone, [28]GuardJob, [29]GuardSalary, [30]MaritalStatus, [31]IsOrphan, [32]IsBaitulmal, [33]SportsHouse, [34]Uniform, [35]UniPos, [36]Club, [37]ClubPos, [38]Sport, [39]SportPos
        </div>
        <p class="small mt-2"><strong>Dates:</strong> Use YYYY-MM-DD format (e.g., 20/01/2020)</p>
    `
    };

    function updateGuide(val) {
        const container = document.getElementById('guide-container');
        if (guides[val]) {
            container.innerHTML = guides[val];
        } else {
            container.innerHTML = '<p class="text-muted">Select a category on the left to view the required CSV columns.</p>';
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>