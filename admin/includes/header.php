<?php
// Ensure no output has been sent before this
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); // Adjust path if your login is in a different folder
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIRAJ School Management</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* --- GLOBAL VARIABLES --- */
        :root {
            --primary-gold: #FFD700;
            --gold-grad: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
            --sidebar-width: 260px;
            /* Must match the width in your sidebar.php */
            --bg-color: #f4f7f6;
            --text-color: #2c3e50;
        }

        /* --- RESET & BODY --- */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        /* --- MAIN CONTENT CONTAINER --- 
           This is the most important class. It pushes your content 
           to the right so it isn't hidden behind the fixed sidebar.
        */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* --- STANDARD CARD STYLE --- */
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            border: 1px solid #eef0f2;
        }

        /* --- PAGE TITLES --- */
        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .page-header p {
            color: #7f8c8d;
            margin: 0;
        }

        /* --- BUTTONS --- */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: 0.2s;
        }

        .btn-primary {
            background: var(--gold-grad);
            color: #333;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 215, 0, 0.3);
        }

        /* --- FORMS --- */
        input[type="text"],
        input[type="password"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0 20px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }

        input:focus,
        select:focus {
            border-color: var(--primary-gold);
            outline: none;
        }

        /* --- TABLES --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 2px solid var(--primary-gold);
            color: #555;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>

<body>