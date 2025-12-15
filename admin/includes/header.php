<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- 1. GLOBAL RESET (Essential for Full Width) --- */
        * {
            box-sizing: border-box;
        }

        :root {
            --primary-gold: #FFD700;
            --secondary-gold: #FDB931;
            --gold-grad: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
            --text-dark: #2c3e50;
            --text-light: #6c757d;
            --bg-body: #f4f7f6;
            --sidebar-w: 260px;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-body);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            min-height: 100vh;
            /* Removed display: flex to prevent layout conflict with fixed sidebar */
        }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-w);
            background: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-right: 1px solid #e1e4e8;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .brand {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .brand h3 {
            margin: 0;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: var(--gold-grad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 900;
        }

        .menu {
            padding: 20px 0;
            flex: 1;
            list-style: none;
            margin: 0;
        }

        .menu li a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            border-left: 4px solid transparent;
        }

        .menu li a:hover,
        .menu li a.active {
            background: #fffcf0;
            color: #b8860b;
            border-left-color: var(--primary-gold);
        }

        .menu li a i {
            width: 25px;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        /* --- Main Content (The Fix) --- */
        .main-content {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
            /* Forces it to take remaining width */
            padding: 30px;
            min-height: 100vh;
            display: block;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0 0 5px 0;
        }

        .page-header p {
            color: var(--text-light);
            margin: 0;
        }

        /* --- Cards --- */
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            border: 1px solid #f0f0f0;
            margin-bottom: 25px;
            transition: transform 0.2s;
            width: 100%;
            /* Ensures cards stretch */
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        /* --- Tables --- */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #fafafa;
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--primary-gold);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #fffcf5;
        }

        /* --- Buttons --- */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--gold-grad);
            color: white;
            box-shadow: 0 3px 10px rgba(253, 185, 49, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(253, 185, 49, 0.5);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-danger {
            background: #ff5252;
            color: white;
        }

        /* --- Forms --- */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
            width: 100%;
        }

        .form-group {
            width: 100%;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-gold);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        /* Stats Widgets */
        .stat-card {
            border-left: 5px solid var(--primary-gold);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-val {
            font-size: 2.5rem;
            font-weight: 700;
            color: #b8860b;
            margin: 0;
        }

        .stat-label {
            color: #888;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Helpers */
        .d-flex {
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }

        .flex-1 {
            flex: 1;
        }
    </style>
</head>

<body>