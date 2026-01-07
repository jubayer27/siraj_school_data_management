<?php
session_start();
include '../config/db.php';

// 1. SECURITY
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'class_teacher')) {
    die("Access Denied");
}

// 2. GET INPUTS
if (!isset($_GET['student_id']) || !isset($_GET['exam_id'])) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h3>Error: Missing Information</h3>
            <p>Student ID or Exam ID not provided.</p>
            <button onclick='window.history.back()'>Go Back</button>
         </div>");
}

$student_id = intval($_GET['student_id']);
$exam_id = intval($_GET['exam_id']);

// 3. FETCH STUDENT INFO
$stu_sql = "SELECT s.*, c.class_name 
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.class_id 
            WHERE s.student_id = $student_id";
$student = $conn->query($stu_sql)->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

// 4. FETCH EXAM INFO (Get Name from ID)
$exam_sql = "SELECT * FROM exam_types WHERE exam_id = $exam_id";
$exam = $conn->query($exam_sql)->fetch_assoc();

if (!$exam) {
    die("Exam not found.");
}

$current_exam_name = $conn->real_escape_string($exam['exam_name']);
$global_max = floatval($exam['max_marks']);

// 5. FETCH MARKS (FIXED QUERY with correct Joins)
// student_marks(enrollment_id) -> student_subject_enrollment(student_id, subject_id) -> subjects
$marks_sql = "SELECT 
                sm.mark_obtained, 
                sm.max_mark, 
                sm.grade,
                sub.subject_name, 
                sub.subject_code
              FROM student_marks sm
              JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id
              JOIN subjects sub ON sse.subject_id = sub.subject_id
              WHERE sse.student_id = $student_id 
              AND sm.exam_type = '$current_exam_name'
              ORDER BY sub.subject_name ASC";

$marks_res = $conn->query($marks_sql);

// 6. DYNAMIC GRADING FUNCTION
function getGrade($obtained, $total)
{
    if ($total <= 0)
        return ['F', 'Tiada Markah'];

    // Calculate Percentage
    $pct = ($obtained / $total) * 100;

    // Standard Malaysia School Grading
    if ($pct >= 85)
        return ['A', 'Cemerlang'];
    if ($pct >= 70)
        return ['B', 'Kepujian'];
    if ($pct >= 60)
        return ['C', 'Baik'];
    if ($pct >= 50)
        return ['D', 'Memuaskan'];
    if ($pct >= 40)
        return ['E', 'Mencapai Tahap Minimum'];
    return ['F', 'Belum Mencapai Tahap Minimum'];
}

$total_obtained = 0;
$total_max_accumulated = 0;
$count_subjects = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Marksheet - <?php echo $student['student_name']; ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10mm auto;
            border: 1px solid #d3d3d3;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* HEADER */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
            height: auto;
        }

        .school-name {
            font-size: 1.5rem;
            font-weight: bold;
            text-transform: uppercase;
            margin: 5px 0;
        }

        .school-info {
            font-size: 0.9rem;
        }

        /* STUDENT INFO */
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 120px;
        }

        /* MARKS TABLE */
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .marks-table th,
        .marks-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        .marks-table th {
            background-color: #f8f9fa;
        }

        .subject-col {
            text-align: left !important;
        }

        /* SUMMARY */
        .summary-box {
            float: right;
            width: 45%;
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .summary-row.total {
            font-weight: bold;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }

        /* FOOTER */
        .footer {
            position: absolute;
            bottom: 30mm;
            width: calc(100% - 40mm);
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }

        .sig-box {
            text-align: center;
            width: 200px;
        }

        .line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 30px;
        }

        /* NO PRINT ELEMENTS */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .page {
                margin: 0;
                border: none;
                box-shadow: none;
                width: 100%;
                height: auto;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 0;
            }
        }

        .btn-back {
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .btn-print {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>

<body>

    <a href="javascript:history.back()" class="btn-back no-print">← Back</a>
    <button onclick="window.print()" class="btn-print no-print">🖨️ Print Marksheet</button>

    <div class="page">
        <div class="header">
            <img src="../assets/logo.png" alt="Logo" class="logo" onerror="this.style.display='none'">
            <div class="school-name">SEKOLAH RENDAH ISLAM AL-SIRAJ</div>
            <div class="school-info">No 123, Jalan Sekolah, 54200 Kuala Lumpur | Tel: 03-12345678</div>
            <h3 style="margin-top: 15px; text-decoration: underline;">SLIP KEPUTUSAN PEPERIKSAAN</h3>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">NAMA:</td>
                <td><?php echo strtoupper($student['student_name']); ?></td>
                <td class="label">TAHUN/KELAS:</td>
                <td><?php echo strtoupper($student['class_name']); ?></td>
            </tr>
            <tr>
                <td class="label">NO. MYKID:</td>
                <td><?php echo $student['ic_no']; ?></td>
                <td class="label">PEPERIKSAAN:</td>
                <td><?php echo strtoupper($exam['exam_name']); ?></td>
            </tr>
            <tr>
                <td class="label">NO. PENDAFTARAN:</td>
                <td><?php echo $student['school_register_no']; ?></td>
                <td class="label">TARIKH:</td>
                <td><?php echo date('d/m/Y'); ?></td>
            </tr>
        </table>

        <table class="marks-table">
            <thead>
                <tr>
                    <th width="10%">BIL</th>
                    <th class="subject-col">MATA PELAJARAN</th>
                    <th width="15%">MARKAH</th>
                    <th width="15%">MAX</th>
                    <th width="15%">GRED</th>
                    <th width="20%">CATATAN</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                if ($marks_res->num_rows > 0) {
                    while ($row = $marks_res->fetch_assoc()) {
                        // Use saved max_mark if available, else global setting
                        $max_mark = ($row['max_mark'] > 0) ? floatval($row['max_mark']) : $global_max;
                        $mark = floatval($row['mark_obtained']);

                        // Totals for average calculation
                        $total_obtained += $mark;
                        $total_max_accumulated += $max_mark;
                        $count_subjects++;

                        // Get Dynamic Grade
                        $gradeData = getGrade($mark, $max_mark);
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td class="subject-col">
                                <?php echo $row['subject_name']; ?>
                                <br><small style="color:#777; font-size:0.8em;"><?php echo $row['subject_code']; ?></small>
                            </td>
                            <td><?php echo $mark; ?></td>
                            <td style="color:#666; font-size:0.9em;">/ <?php echo $max_mark; ?></td>
                            <td><strong><?php echo $gradeData[0]; ?></strong></td>
                            <td style="font-size:0.85em;"><?php echo $gradeData[1]; ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='6' style='padding:20px;'>Tiada rekod markah dijumpai untuk peperiksaan ini.</td></tr>";
                }

                // Average Calculation (Percentage Based)
                $average_pct = 0;
                if ($total_max_accumulated > 0) {
                    $average_pct = ($total_obtained / $total_max_accumulated) * 100;
                }
                ?>
            </tbody>
        </table>

        <div style="width: 100%; overflow: hidden;">
            <div class="summary-box">
                <div class="summary-row">
                    <span>JUMLAH MARKAH:</span>
                    <span><?php echo $total_obtained; ?> / <?php echo $total_max_accumulated; ?></span>
                </div>
                <div class="summary-row">
                    <span>BILANGAN SUBJEK:</span>
                    <span><?php echo $count_subjects; ?></span>
                </div>
                <div class="summary-row total">
                    <span>PURATA KESELURUHAN:</span>
                    <span><?php echo number_format($average_pct, 2); ?>%</span>
                </div>
                <div class="summary-row total">
                    <span>PENCAPAIAN:</span>
                    <span><?php echo getGrade($average_pct, 100)[1]; ?></span>
                </div>
            </div>
        </div>

        <div style="font-size: 0.8rem; margin-bottom: 20px; color:#555;">
            <strong>SKALA GRED (%):</strong> A (85-100), B (70-84), C (60-69), D (50-59), E (40-49), F (0-39)
        </div>

        <div class="footer">
            <div class="signatures">
                <div class="sig-box">
                    <div class="line"></div>
                    <div>GURU KELAS</div>
                    <small>(Tandatangan)</small>
                </div>
                <div class="sig-box">
                    <div class="line"></div>
                    <div>GURU BESAR</div>
                    <small>(Tandatangan & Cop Rasmi)</small>
                </div>
            </div>
            <div style="text-align: center; margin-top: 20px; font-size: 0.8rem;">
                * Ini adalah cetakan komputer. Tandatangan diperlukan untuk pengesahan.
            </div>
        </div>

    </div>

</body>

</html>