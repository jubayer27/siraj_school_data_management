<?php
session_start();
include '../config/db.php';

// 1. SECURITY
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access Denied");
}

// 2. GET INPUTS
if (!isset($_GET['student_id']) || !isset($_GET['exam_id'])) {
    die("<div style='text-align:center; padding:50px;'>Error: Missing Student ID or Exam ID.<br><a href='manage_all_marks.php'>Go Back</a></div>");
}

$student_id = intval($_GET['student_id']);
$exam_id = intval($_GET['exam_id']);

// 3. FETCH STUDENT & EXAM DETAILS
$stu_sql = "SELECT s.*, c.class_name 
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.class_id 
            WHERE s.student_id = $student_id";
$student = $conn->query($stu_sql)->fetch_assoc();

$exam_sql = "SELECT * FROM exam_types WHERE exam_id = $exam_id";
$exam = $conn->query($exam_sql)->fetch_assoc();

if (!$student || !$exam) {
    die("Data not found.");
}

// 4. FETCH MARKS
$marks_sql = "SELECT m.marks, s.subject_name, s.subject_code
              FROM student_marks m
              JOIN subjects s ON m.subject_id = s.subject_id
              WHERE m.student_id = $student_id AND m.exam_id = $exam_id";
$marks_res = $conn->query($marks_sql);

// 5. GRADING HELPER FUNCTION (Standard Malaysia School Grading)
function getGrade($mark)
{
    if ($mark >= 85)
        return ['A', 'Cemerlang'];
    if ($mark >= 70)
        return ['B', 'Kepujian'];
    if ($mark >= 60)
        return ['C', 'Baik'];
    if ($mark >= 50)
        return ['D', 'Memuaskan'];
    if ($mark >= 40)
        return ['E', 'Mencapai Tahap Minimum'];
    return ['F', 'Belum Mencapai Tahap Minimum'];
}

$total_marks = 0;
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
            width: 40%;
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
    </style>
</head>

<body>

    <a href="manage_all_marks.php" class="btn-back no-print">← Back</a>
    <button onclick="window.print()" class="btn-print no-print">🖨️ Print Marksheet</button>

    <div class="page">
        <div class="header">
            <img src="../assets/siraj-logo.png" alt="Logo" class="logo" onerror="this.style.display='none'">
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
                    <th width="15%">GRED</th>
                    <th width="25%">CATATAN</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                if ($marks_res->num_rows > 0) {
                    while ($row = $marks_res->fetch_assoc()) {
                        $mark = floatval($row['marks']);
                        $total_marks += $mark;
                        $count_subjects++;
                        $gradeData = getGrade($mark);
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td class="subject-col"><?php echo $row['subject_name']; ?></td>
                            <td><?php echo $mark; ?></td>
                            <td><?php echo $gradeData[0]; ?></td>
                            <td><?php echo $gradeData[1]; ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='5'>Tiada rekod markah.</td></tr>";
                }

                // Calculation
                $average = $count_subjects > 0 ? number_format($total_marks / $count_subjects, 2) : 0;
                ?>
            </tbody>
        </table>

        <div style="width: 100%; overflow: hidden;">
            <div class="summary-box">
                <div class="summary-row">
                    <span>JUMLAH MARKAH:</span>
                    <span><?php echo $total_marks; ?></span>
                </div>
                <div class="summary-row">
                    <span>BILANGAN SUBJEK:</span>
                    <span><?php echo $count_subjects; ?></span>
                </div>
                <div class="summary-row total">
                    <span>PURATA:</span>
                    <span><?php echo $average; ?>%</span>
                </div>
            </div>
        </div>

        <div style="font-size: 0.8rem; margin-bottom: 20px;">
            <strong>SKALA GRED:</strong> A (85-100), B (70-84), C (60-69), D (50-59), E (40-49), F (0-39)
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