<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 统计数据
$totalUsers      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"));
$totalProjects   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM projects"));
$totalEvaluations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM evaluations"));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="manage_users.php">👨‍🎓 Manage Users</a>
    <a href="manage_projects.php">📁 Manage Projects</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<div class="content">
    <h1>Welcome, Admin</h1>
    <p>System Overview</p>

    <div class="stats">
        <div class="stat-card">
            <h2><?php echo $totalUsers['total']; ?></h2>
            <p>Total Users</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $totalProjects['total']; ?></h2>
            <p>Total Projects</p>
        </div>

        <div class="stat-card">
            <h2><?php echo $totalEvaluations['total']; ?></h2>
            <p>Total Evaluations</p>
        </div>
    </div>

</div>

</body>
</html>
