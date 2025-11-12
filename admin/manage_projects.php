<?php
session_start();
include '../db.php';

// ✅ Only admin can access
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// ✅ Assign evaluator
if (isset($_POST["assignEvaluator"])) {
    $projectID = $_POST["project_id"];
    $evaluator = $_POST["assigned_evaluator"];

    mysqli_query($conn, "UPDATE projects SET assigned_evaluator = '$evaluator', status='Under Review' WHERE id='$projectID'");
    header("Location: manage_projects.php");
    exit();
}

// ✅ Delete project
if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    mysqli_query($conn, "DELETE FROM projects WHERE id='$id'");
    header("Location: manage_projects.php");
    exit();
}

// ✅ Search filter
$filter = "";
if (isset($_GET['search']) && $_GET['search'] !== "") {
    $search = $_GET['search'];
    $filter = "WHERE p.project_title LIKE '%$search%' 
               OR u.username LIKE '%$search%' 
               OR p.status LIKE '%$search%'";
}

// ✅ Fetch projects
$projects = mysqli_query($conn, "
    SELECT p.*, u.username AS student_name,
          (SELECT username FROM users WHERE id = p.assigned_evaluator) AS evaluator_name
    FROM projects p
    JOIN users u ON p.student_id = u.id
    $filter
");

// ✅ Fetch evaluators
$evaluators = mysqli_query($conn, "SELECT * FROM users WHERE role = 'evaluator'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Projects</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="manage_users.php">👨‍🎓 Manage Users</a>
    <a href="manage_projects.php" class="active">📁 Manage Projects</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<div class="content">
    <h1>Manage Projects</h1>

    <!-- ✅ Search Box -->
    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="🔍 Search project..."
               value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
    </form>

    <table class="styled-table">
        <tr>
            <th>ID</th>
            <th>Project Title</th>
            <th>Student</th>
            <th>Assigned Evaluator</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($projects)): ?>
        <tr>
            <td><?= $row["id"]; ?></td>
            <td><?= $row["project_title"]; ?></td>
            <td><?= $row["student_name"]; ?></td>

            <!-- assign evaluator dropdown -->
            <td>
                <form method="POST">
                    <input type="hidden" name="project_id" value="<?= $row["id"]; ?>">

                    <select name="assigned_evaluator" required>
                        <option value="">--Select--</option>

                        <?php mysqli_data_seek($evaluators, 0); ?>
                        <?php while ($eva = mysqli_fetch_assoc($evaluators)): ?>
                        <option value="<?= $eva["id"] ?>" 
                            <?= ($row["assigned_evaluator"] == $eva["id"]) ? "selected" : "" ?>>
                            <?= $eva["username"]; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" name="assignEvaluator">Assign</button>
                </form>
            </td>

            <td><?= $row["status"]; ?></td>

            <td>
                <a href="manage_projects.php?delete=<?= $row['id']; ?>"
                   onclick="return confirm('Confirm delete project?')"
                   class="btn-danger">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>

    </table>
</div>

</body>
</html>
