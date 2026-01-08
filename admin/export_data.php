<?php
session_start();
include '../config/db.php';

// 1. SECURITY
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// ====================================================
// 2. EXPORT LOGIC (CSV DOWNLOAD)
// ====================================================
if (isset($_POST['export_csv'])) {
    $type = $_POST['export_type'];
    $filename = $type . "_export_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    // --- TYPE A: STUDENTS ---
    if ($type == 'students') {
        $headers = ['RegNo', 'Name', 'IC', 'Gender', 'Class_Name', 'DOB', 'BirthPlace', 'Race', 'Religion', 'Nationality', 'Phone', 'Address', 'EnrollDate', 'PrevSchool', 'FatherName', 'FatherPhone', 'MotherName', 'MotherPhone', 'IsOrphan', 'IsBaitulmal'];
        fputcsv($output, $headers);

        $sql = "SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.class_id ORDER BY c.class_name, s.student_name";
        $rows = $conn->query($sql);

        while ($r = $rows->fetch_assoc()) {
            fputcsv($output, [
                $r['school_register_no'],
                $r['student_name'],
                $r['ic_no'],
                $r['gender'],
                $r['class_name'],
                $r['birthdate'],
                $r['birth_place'],
                $r['race'],
                $r['religion'],
                $r['nationality'],
                $r['phone'],
                $r['address'],
                $r['enrollment_date'],
                $r['previous_school'],
                $r['father_name'],
                $r['father_phone'],
                $r['mother_name'],
                $r['mother_phone'],
                $r['is_orphan'],
                $r['is_baitulmal_recipient']
            ]);
        }
    }

    // --- TYPE B: STAFF / USERS ---
    elseif ($type == 'users') {
        // [CHANGED] Added 'Email' to headers
        fputcsv($output, ['FullName', 'Username', 'Role', 'StaffID', 'IC_No', 'Phone', 'Email', 'Status']);

        $rows = $conn->query("SELECT * FROM users ORDER BY role, full_name");
        while ($r = $rows->fetch_assoc()) {
            // [CHANGED] Added $r['email'] to output
            fputcsv($output, [$r['full_name'], $r['username'], $r['role'], $r['teacher_id_no'], $r['ic_no'], $r['phone'], $r['email'], 'Active']);
        }
    }

    // --- TYPE C: CLASSES ---
    elseif ($type == 'classes') {
        fputcsv($output, ['ClassName', 'Year', 'TeacherStaffID', 'TeacherName']);
        $sql = "SELECT c.*, u.teacher_id_no, u.full_name FROM classes c LEFT JOIN users u ON c.class_teacher_id = u.user_id ORDER BY c.year DESC, c.class_name";
        $rows = $conn->query($sql);
        while ($r = $rows->fetch_assoc()) {
            fputcsv($output, [$r['class_name'], $r['year'], $r['teacher_id_no'], $r['full_name']]);
        }
    }

    // --- TYPE D: SUBJECTS (UPDATED FOR MULTIPLE TEACHERS) ---
    elseif ($type == 'subjects') {
        fputcsv($output, ['SubjectName', 'Code', 'ClassName', 'AssignedTeachers']);

        // Use Subquery to fetch teachers via junction table
        $sql = "SELECT s.subject_name, s.subject_code, c.class_name,
                (SELECT GROUP_CONCAT(u.full_name SEPARATOR ', ') 
                 FROM subject_teachers st 
                 JOIN users u ON st.teacher_id = u.user_id 
                 WHERE st.subject_id = s.subject_id) as teacher_names
                FROM subjects s 
                LEFT JOIN classes c ON s.class_id = c.class_id 
                ORDER BY c.class_name, s.subject_name";

        $rows = $conn->query($sql);
        while ($r = $rows->fetch_assoc()) {
            fputcsv($output, [$r['subject_name'], $r['subject_code'], $r['class_name'], $r['teacher_names']]);
        }
    }

    fclose($output);
    exit();
}

include 'includes/header.php';
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

    .export-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        height: 100%;
    }

    .card-header-custom {
        background: #2c3e50;
        color: white;
        padding: 20px;
        border-radius: 12px 12px 0 0;
    }

    /* Print Styles */
    @media print {

        .sidebar,
        .export-controls,
        .page-header {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            width: 100% !important;
            padding: 0 !important;
        }

        .printable-area {
            display: block !important;
        }

        .table th {
            background: #eee !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
        }
    }

    .printable-area {
        display: none;
        margin-top: 20px;
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

            <div class="d-flex justify-content-between align-items-center mb-4 export-controls">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Export Data Center</h2>
                    <p class="text-secondary mb-0">Download database records or generate reports.</p>
                </div>
            </div>

            <div class="row export-controls">
                <div class="col-md-6 offset-md-3">
                    <div class="card export-card">
                        <div class="card-header-custom text-center">
                            <h4 class="m-0 fw-bold"><i class="fas fa-file-export me-2"></i> Select Export Type</h4>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="mb-4">
                                    <label class="fw-bold mb-2 text-uppercase text-muted small">1. Choose Data</label>
                                    <select name="export_type" class="form-select form-select-lg" id="dataType">
                                        <option value="students">Students (Full Profile)</option>
                                        <option value="users">Staff / Teachers</option>
                                        <option value="classes">Classes</option>
                                        <option value="subjects">Subjects</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="fw-bold mb-2 text-uppercase text-muted small">2. Choose Format</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button type="submit" name="export_csv"
                                                class="btn btn-success w-100 py-3 fw-bold">
                                                <i class="fas fa-file-excel fa-lg me-2"></i> Excel (CSV)
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" onclick="generatePrintView()"
                                                class="btn btn-secondary w-100 py-3 fw-bold">
                                                <i class="fas fa-file-pdf fa-lg me-2"></i> PDF / Print
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-light border small text-center text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>CSV</strong> is best for backups and editing in Excel.<br>
                                    <strong>PDF</strong> is best for printing official lists.
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div id="printArea" class="printable-area bg-white p-4">
                <div class="text-center mb-4 pb-3 border-bottom border-dark">
                    <h2 class="fw-bold text-uppercase">SIRAJ Al Alusi</h2>
                    <h5 class="text-muted" id="reportTitle">Master Data Report</h5>
                    <p class="small">Generated on: <?php echo date('d M Y, h:i A'); ?></p>
                </div>

                <div id="tableContainer"></div>

                <div class="text-center mt-5 no-print export-controls">
                    <button onclick="window.print()" class="btn btn-primary btn-lg"><i class="fas fa-print me-2"></i>
                        Print Now</button>
                    <button onclick="location.reload()" class="btn btn-light btn-lg ms-2">Back</button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // PREPARE DATA FOR PRINT VIEW (Using PHP to populate JS variables)
    const allData = {
        students: <?php
        $d = [];
        $q = $conn->query("SELECT school_register_no, student_name, ic_no, gender, class_name FROM students s LEFT JOIN classes c ON s.class_id=c.class_id ORDER BY class_name, student_name");
        while ($r = $q->fetch_assoc())
            $d[] = $r;
        echo json_encode($d);
        ?>,

        users: <?php
        $d = [];
        // [CHANGED] Select 'email' here for JS to use
        $q = $conn->query("SELECT full_name, role, teacher_id_no, phone, email FROM users ORDER BY role");
        while ($r = $q->fetch_assoc())
            $d[] = $r;
        echo json_encode($d);
        ?>,

        classes: <?php
        $d = [];
        $q = $conn->query("SELECT class_name, year, full_name FROM classes c LEFT JOIN users u ON c.class_teacher_id=u.user_id ORDER BY year DESC, class_name");
        while ($r = $q->fetch_assoc())
            $d[] = $r;
        echo json_encode($d);
        ?>,

        subjects: <?php
        // UPDATED: Using GROUP_CONCAT for multiple teachers in Print View
        $d = [];
        $q = $conn->query("SELECT s.subject_name, s.subject_code, c.class_name,
                           (SELECT GROUP_CONCAT(u.full_name SEPARATOR ', ') FROM subject_teachers st JOIN users u ON st.teacher_id=u.user_id WHERE st.subject_id=s.subject_id) as teacher_names
                           FROM subjects s LEFT JOIN classes c ON s.class_id=c.class_id ORDER BY c.class_name");
        while ($r = $q->fetch_assoc())
            $d[] = $r;
        echo json_encode($d);
        ?>
    };

    function generatePrintView() {
        const type = document.getElementById('dataType').value;
        const container = document.getElementById('tableContainer');
        const data = allData[type];

        // Hide controls, Show print area
        document.querySelectorAll('.export-controls').forEach(el => el.style.display = 'none');
        document.getElementById('printArea').style.display = 'block';

        // Set Title
        document.getElementById('reportTitle').innerText = type.toUpperCase() + ' MASTER LIST';

        // Build Table HTML
        let html = '<table class="table table-bordered table-striped border-dark"><thead><tr class="table-dark">';

        if (type === 'students') {
            html += '<th>Reg No</th><th>Name</th><th>IC No</th><th>Gender</th><th>Class</th></tr></thead><tbody>';
            data.forEach(row => {
                html += `<tr><td>${row.school_register_no}</td><td class="fw-bold">${row.student_name}</td><td>${row.ic_no}</td><td>${row.gender}</td><td>${row.class_name || 'Unassigned'}</td></tr>`;
            });
        } else if (type === 'users') {
            // [CHANGED] Added Email to Print Table Headers & Rows
            html += '<th>Name</th><th>Role</th><th>Staff ID</th><th>Phone</th><th>Email</th></tr></thead><tbody>';
            data.forEach(row => {
                html += `<tr><td class="fw-bold">${row.full_name}</td><td>${row.role}</td><td>${row.teacher_id_no || '-'}</td><td>${row.phone || '-'}</td><td>${row.email || '-'}</td></tr>`;
            });
        } else if (type === 'classes') {
            html += '<th>Class Name</th><th>Year</th><th>Teacher</th></tr></thead><tbody>';
            data.forEach(row => {
                html += `<tr><td class="fw-bold">${row.class_name}</td><td>${row.year}</td><td>${row.full_name || 'Unassigned'}</td></tr>`;
            });
        } else if (type === 'subjects') {
            html += '<th>Subject</th><th>Code</th><th>Class</th><th>Teachers</th></tr></thead><tbody>';
            data.forEach(row => {
                html += `<tr><td class="fw-bold">${row.subject_name}</td><td>${row.subject_code}</td><td>${row.class_name || '-'}</td><td>${row.teacher_names || '<span class="text-muted">Unassigned</span>'}</td></tr>`;
            });
        }

        html += '</tbody></table>';
        container.innerHTML = html;

        // Auto Trigger Print
        setTimeout(() => window.print(), 500);
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>