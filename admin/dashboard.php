<?php
include '../config/db.php';
include 'includes/header.php';

// Fetch Statistics
$s_count = $conn->query("SELECT count(*) as c FROM students")->fetch_assoc()['c'];
$t_count = $conn->query("SELECT count(*) as c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$c_count = $conn->query("SELECT count(*) as c FROM classes")->fetch_assoc()['c'];
$sub_count = $conn->query("SELECT count(*) as c FROM subjects")->fetch_assoc()['c'];
?>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <h1>Admin Dashboard</h1>
            <p>Overview of school performance and data</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
            <div class="card stat-card">
                <p class="stat-val"><?php echo $s_count; ?></p>
                <span class="stat-label">Students</span>
            </div>
            <div class="card stat-card">
                <p class="stat-val"><?php echo $t_count; ?></p>
                <span class="stat-label">Teachers</span>
            </div>
            <div class="card stat-card">
                <p class="stat-val"><?php echo $c_count; ?></p>
                <span class="stat-label">Classes</span>
            </div>
            <div class="card stat-card">
                <p class="stat-val"><?php echo $sub_count; ?></p>
                <span class="stat-label">Subjects</span>
            </div>
        </div>

        <div class="card">
            <h3>Recent System Users</h3>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Name</th><th>Role</th><th>Username</th><th>Created At</th></tr></thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                        while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><span style="padding:4px 8px; background:#eee; border-radius:4px; font-size:0.8rem;"><?php echo strtoupper(str_replace('_',' ',$row['role'])); ?></span></td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>