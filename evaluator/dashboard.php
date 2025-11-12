<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$evaluator_id = $_SESSION["user_id"];

// Get projects assigned to evaluator
$sql = "SELECT p.project_id, p.project_title, p.student_id, p.status, p.submitted_date 
        FROM projects p
        WHERE p.evaluator_id = '$evaluator_id'";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Evaluator Dashboard | Project Evaluation System</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #f4f7ff;
}
.sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 240px;
    height: 100vh;
    background: #1b2b41;
    color: #fff;
    padding-top: 20px;
}
.sidebar h2 {
    text-align: center;
    margin-bottom: 20px;
}
.sidebar a {
    display: block;
    padding: 14px 20px;
    margin: 8px 12px;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: 0.3s;
}
.sidebar a:hover,
.sidebar a.active {
    background: #00bcd4;
}
.logout-btn {
    background: #e63946;
    margin-top: 150px;
}
.main-content {
    margin-left: 260px;
    padding: 25px;
}
.stats-container {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    flex: 1;
    padding: 18px;
    border-radius: 8px;
    color: white;
}
.blue { background: #0077b6; }
.orange { background: #ff8600; }
.green { background: #2a9d8f; }
.stat-card h3 { margin: 0; font-size: 18px; }
.stat-card .number { font-size: 30px; font-weight: bold; }

.project-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
table th, table td {
    border-bottom: 1px solid #edf0f5;
    padding: 12px;
}
table th {
    background: #eef2ff;
    text-align: left;
}
.status-under\ review { color: #ff8800; font-weight: bold; }
.status-completed { color: #2a9d8f; font-weight: bold; }

.btn-evaluate {
    background: #0077b6;
    padding: 8px 14px;
    color: white;
    border-radius: 6px;
    text-decoration: none;
}
.btn-evaluate:hover {
    background: #005f86;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Evaluator Panel</h2>
    <a href="dashboard.php" class="active">📋 Assigned Projects</a>
    <a href="../logout.php" class="logout-btn">Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Welcome, Evaluator <?php echo $evaluator_id; ?> ✅</h2>
    <p>Below are projects assigned to you for evaluation.</p>

    <!-- Dashboard Stats -->
    <div class="stats-container">
        <div class="stat-card blue">
            <h3>Total Assigned</h3>
            <p class="number">
                <?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM projects WHERE evaluator_id='$evaluator_id'")); ?>
            </p>
        </div>

        <div class="stat-card orange">
            <h3>Pending Evaluation</h3>
            <p class="number">
                <?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM projects WHERE evaluator_id='$evaluator_id' AND status='Under Review'")); ?>
            </p>
        </div>

        <div class="stat-card green">
            <h3>Completed Evaluation</h3>
            <p class="number">
                <?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM projects WHERE evaluator_id='$evaluator_id' AND status='Completed'")); ?>
            </p>
        </div>
    </div>

    <!-- Project List Table -->
    <div class="project-section">
        <h3>📁 Assigned Project List</h3>

        <table>
            <tr>
                <th>Project Title</th>
                <th>Student ID</th>
                <th>Status</th>
                <th>Submitted Date</th>
                <th>Action</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= $row["project_title"]; ?></td>
                <td><?= $row["student_id"]; ?></td>
                <td class="status-<?= strtolower($row["status"]); ?>"><?= $row["status"]; ?></td>
                <td><?= $row["submitted_date"]; ?></td>
                <td><a href="evaluate_project.php?id=<?= $row['project_id']; ?>" class="btn-evaluate">Evaluate</a></td>
            </tr>
            <?php } ?>

        </table>
    </div>

</div>

</body>
</html>
