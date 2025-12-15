<?php
session_start();
include '../config/db.php';
include 'includes/header.php';

// 1. SECURITY & ID CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['student_id'])) {
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

if (!$student)
    die("Student record not found.");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        background-color: #f4f6f9;
        overflow-x: hidden;
    }

    /* Layout Fix */
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

    /* Profile Sidebar */
    .profile-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .profile-header-bg {
        height: 120px;
        background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
    }

    .avatar-wrapper {
        margin-top: -60px;
        text-align: center;
    }

    .avatar-xl {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        object-fit: cover;
    }

    /* Info Sections */
    .info-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        margin-bottom: 20px;
    }

    .card-header-custom {
        background: white;
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-weight: 700;
        color: #DAA520;
        display: flex;
        align-items: center;
    }

    .label-text {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #888;
        font-weight: 600;
        display: block;
        margin-bottom: 3px;
    }

    .value-text {
        font-size: 0.95rem;
        font-weight: 500;
        color: #333;
    }

    /* Status Badges */
    .status-badge {
        padding: 8px 15px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        margin-bottom: 10px;
    }

    .status-badge.active {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-badge.inactive {
        background: #ffebee;
        color: #c62828;
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
                    <h2 class="fw-bold text-dark mb-0">Student Profile</h2>
                    <p class="text-secondary mb-0">Register No:
                        <strong><?php echo $student['school_register_no']; ?></strong>
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <a href="manage_students.php" class="btn btn-light shadow-sm border">
                        <i class="fas fa-arrow-left me-2"></i> Back
                    </a>
                    <a href="edit_student.php?student_id=<?php echo $sid; ?>" class="btn btn-warning fw-bold shadow-sm">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="card profile-card mb-4">
                        <div class="profile-header-bg"></div>
                        <div class="card-body pt-0 text-center">
                            <div class="avatar-wrapper">
                                <?php $photo = $student['photo'] ? "../uploads/" . $student['photo'] : "https://ui-avatars.com/api/?name=" . $student['student_name'] . "&background=random&size=150"; ?>
                                <img src="<?php echo $photo; ?>" class="avatar-xl">
                            </div>
                            <h4 class="mt-3 mb-1 fw-bold"><?php echo $student['student_name']; ?></h4>
                            <p class="text-muted mb-3">
                                <?php echo $student['ic_no'] ? $student['ic_no'] : 'No IC Number'; ?>
                            </p>

                            <?php if ($student['class_name']): ?>
                                <span class="badge bg-warning text-dark border px-3 py-2 rounded-pill fs-6 mb-3">
                                    <?php echo $student['class_name']; ?> (<?php echo $student['year']; ?>)
                                </span>
                            <?php else: ?>
                                <span
                                    class="badge bg-secondary text-white px-3 py-2 rounded-pill fs-6 mb-3">Unassigned</span>
                            <?php endif; ?>

                            <hr>

                            <div class="text-start px-3">
                                <div class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-venus-mars text-muted me-3 fs-5"
                                        style="width:20px; text-align:center;"></i>
                                    <div><span class="label-text">Gender</span> <span
                                            class="value-text"><?php echo $student['gender']; ?></span></div>
                                </div>
                                <div class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-birthday-cake text-muted me-3 fs-5"
                                        style="width:20px; text-align:center;"></i>
                                    <div><span class="label-text">Date of Birth</span> <span
                                            class="value-text"><?php echo $student['birthdate'] ? date('d M Y', strtotime($student['birthdate'])) : '-'; ?></span>
                                    </div>
                                </div>
                                <div class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-phone text-muted me-3 fs-5"
                                        style="width:20px; text-align:center;"></i>
                                    <div><span class="label-text">Contact</span> <span
                                            class="value-text"><?php echo $student['phone'] ? $student['phone'] : '-'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card info-card">
                        <div class="card-header-custom"><i class="fas fa-flag me-2"></i> Status Indicators</div>
                        <div class="card-body p-3">
                            <div
                                class="status-badge <?php echo ($student['is_orphan'] == 'Yes') ? 'active' : 'inactive'; ?>">
                                <span><i class="fas fa-child me-2"></i> Orphan Status</span>
                                <strong><?php echo $student['is_orphan']; ?></strong>
                            </div>
                            <div
                                class="status-badge <?php echo ($student['is_baitulmal_recipient'] == 'Yes') ? 'active' : 'inactive'; ?>">
                                <span><i class="fas fa-hand-holding-heart me-2"></i> Baitulmal Recipient</span>
                                <strong><?php echo $student['is_baitulmal_recipient']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">

                    <div class="card info-card">
                        <div class="card-header-custom"><i class="fas fa-user-circle me-2"></i> Personal Details</div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <span class="label-text">Full Name</span>
                                    <span class="value-text"><?php echo $student['student_name']; ?></span>
                                </div>
                                <div class="col-md-6">
                                    <span class="label-text">MyKid / Passport</span>
                                    <span class="value-text"><?php echo $student['ic_no']; ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="label-text">Birth Cert No</span>
                                    <span class="value-text"><?php echo $student['birth_cert_no']; ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="label-text">Place of Birth</span>
                                    <span class="value-text"><?php echo $student['birth_place']; ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="label-text">Race / Religion</span>
                                    <span class="value-text"><?php echo $student['race']; ?> /
                                        <?php echo $student['religion']; ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="label-text">Nationality</span>
                                    <span class="value-text"><?php echo $student['nationality']; ?></span>
                                </div>
                                <div class="col-md-4">
                                    <span class="label-text">Enrollment Date</span>
                                    <span class="value-text"><?php echo $student['enrollment_date']; ?></span>
                                </div>
                                <div class="col-12">
                                    <span class="label-text">Home Address</span>
                                    <span class="value-text"><?php echo $student['address']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card info-card">
                        <div class="card-header-custom"><i class="fas fa-users me-2"></i> Family Background</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Relation</th>
                                            <th>Name</th>
                                            <th>IC No</th>
                                            <th>Phone</th>
                                            <th>Job</th>
                                            <th>Income</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="ps-4 fw-bold text-primary">Father</td>
                                            <td><?php echo $student['father_name']; ?></td>
                                            <td><?php echo $student['father_ic']; ?></td>
                                            <td><?php echo $student['father_phone']; ?></td>
                                            <td><?php echo $student['father_job']; ?></td>
                                            <td><?php echo $student['father_salary']; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-4 fw-bold text-danger">Mother</td>
                                            <td><?php echo $student['mother_name']; ?></td>
                                            <td><?php echo $student['mother_ic']; ?></td>
                                            <td><?php echo $student['mother_phone']; ?></td>
                                            <td><?php echo $student['mother_job']; ?></td>
                                            <td><?php echo $student['mother_salary']; ?></td>
                                        </tr>
                                        <?php if ($student['guardian_name']): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-secondary">Guardian</td>
                                                <td><?php echo $student['guardian_name']; ?></td>
                                                <td><?php echo $student['guardian_ic']; ?></td>
                                                <td><?php echo $student['guardian_phone']; ?></td>
                                                <td><?php echo $student['guardian_job']; ?></td>
                                                <td><?php echo $student['guardian_salary']; ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-3 bg-light border-top">
                                <small class="fw-bold text-muted text-uppercase">Parents Marital Status:</small>
                                <span
                                    class="fw-bold text-dark ms-2"><?php echo $student['parents_marital_status']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card info-card">
                        <div class="card-header-custom"><i class="fas fa-running me-2"></i> Co-Curriculum Activities
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-light h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-tshirt text-primary me-2 fs-5"></i>
                                            <h6 class="fw-bold m-0">Uniform Unit</h6>
                                        </div>
                                        <div class="fw-bold text-dark"><?php echo $student['uniform_unit']; ?></div>
                                        <small class="text-muted"><?php echo $student['uniform_position']; ?></small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-light h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-users text-success me-2 fs-5"></i>
                                            <h6 class="fw-bold m-0">Club / Society</h6>
                                        </div>
                                        <div class="fw-bold text-dark"><?php echo $student['club_association']; ?></div>
                                        <small class="text-muted"><?php echo $student['club_position']; ?></small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-light h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-volleyball-ball text-warning me-2 fs-5"></i>
                                            <h6 class="fw-bold m-0">Sports & Games</h6>
                                        </div>
                                        <div class="fw-bold text-dark"><?php echo $student['sports_game']; ?></div>
                                        <small class="text-muted"><?php echo $student['sports_position']; ?></small>
                                        <div class="mt-2 pt-2 border-top">
                                            <small class="text-muted text-uppercase fw-bold">House:</small>
                                            <span class="fw-bold text-uppercase"
                                                style="color:<?php echo strtolower($student['sports_house']); ?>"><?php echo $student['sports_house']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>