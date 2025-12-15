<?php
session_start();
include '../config/db.php';

$sid = $_GET['student_id'];

// Fetch Student
$stu = $conn->query("SELECT s.*, c.class_name, c.year FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE s.student_id = $sid")->fetch_assoc();

// Fetch Marks (Grouped by Exam Type or just All)
// Here we show 'Midterm' & 'Final' side by side logic or just a list. 
// A standard marksheet usually shows one exam term. Let's assume this is the FULL ACADEMIC TRANSCRIPT.
$marks_res = $conn->query("SELECT sub.subject_name, sub.subject_code, sm.exam_type, sm.mark_obtained, sm.grade 
                           FROM student_marks sm
                           JOIN student_subject_enrollment sse ON sm.enrollment_id = sse.enrollment_id
                           JOIN subjects sub ON sse.subject_id = sub.subject_id
                           WHERE sse.student_id = $sid ORDER BY sub.subject_id, sm.exam_type");

$grouped_marks = [];
while ($m = $marks_res->fetch_assoc()) {
    $grouped_marks[] = $m;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Marksheet - <?php echo $stu['student_name']; ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #555;
            padding: 20px;
        }

        .sheet-container {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            position: absolute;
            left: 15mm;
            top: 15mm;
        }

        .school-name {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 5px 0;
        }

        .school-address {
            font-size: 10pt;
            font-style: italic;
        }

        /* Student Info */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            font-size: 11pt;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        th {
            border: 1px solid #000;
            padding: 8px;
            background: #eee;
            text-align: left;
            font-size: 11pt;
        }

        td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 11pt;
        }

        .text-center {
            text-align: center;
        }

        /* Footer */
        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 80px;
        }

        .sign-box {
            text-align: center;
            width: 200px;
        }

        .line {
            border-top: 1px solid #000;
            margin-top: 40px;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }

            .sheet-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="no-print" style="text-align:center; margin-bottom:20px;">
        <button onclick="window.print()"
            style="padding:10px 20px; background:blue; color:white; border:none; cursor:pointer;">Print PDF</button>
    </div>

    <div class="sheet-container">
        <div class="header">
            <img src="../assets/logo.png" class="logo" alt="Logo" onerror="this.style.display='none'">

            <div class="school-name">Sekolah Integrasi Rendah Agama JAWI (SIRAJ)<br>Al Alusi</div>
            <div class="school-address">Jalan 4/27A, Wangsa Maju, 53300 Kuala Lumpur, Malaysia</div>
            <div style="margin-top:15px; font-weight:bold; font-size:14pt; text-decoration:underline;">OFFICIAL
                EXAMINATION TRANSCRIPT</div>
        </div>

        <div class="info-grid">
            <div>
                <div class="info-row"><span class="label">Name:</span> <?php echo $stu['student_name']; ?></div>
                <div class="info-row"><span class="label">Student ID:</span> <?php echo $stu['school_register_no']; ?>
                </div>
                <div class="info-row"><span class="label">IC No:</span> <?php echo $stu['ic_no']; ?></div>
            </div>
            <div>
                <div class="info-row"><span class="label">Class:</span> <?php echo $stu['class_name']; ?></div>
                <div class="info-row"><span class="label">Year:</span> <?php echo $stu['year']; ?></div>
                <div class="info-row"><span class="label">Date:</span> <?php echo date('d M Y'); ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Subject Code</th>
                    <th style="width: 45%;">Subject Name</th>
                    <th style="width: 20%;">Exam Type</th>
                    <th style="width: 10%;" class="text-center">Mark</th>
                    <th style="width: 10%;" class="text-center">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grouped_marks as $m): ?>
                    <tr>
                        <td><?php echo $m['subject_code']; ?></td>
                        <td><?php echo $m['subject_name']; ?></td>
                        <td><?php echo $m['exam_type']; ?></td>
                        <td class="text-center"><?php echo $m['mark_obtained']; ?></td>
                        <td class="text-center" style="font-weight:bold;"><?php echo $m['grade']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="font-size: 10pt; margin-bottom: 20px;">
            <strong>Grading System:</strong> A (80-100) | B (60-79) | C (40-59) | D (30-39) | F (0-29)
        </div>

        <div class="footer">
            <div class="sign-box">
                <div class="line"></div>
                <div>Class Teacher Signature</div>
            </div>
            <div class="sign-box">
                <div class="line"></div>
                <div>Headmaster Signature</div>
            </div>
        </div>
    </div>

</body>

</html>