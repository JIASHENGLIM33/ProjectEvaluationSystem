<?php
session_start();
include '../db.php';

// ✅ Only admin can access
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// ✅ Search filter
$filter = "";
if (isset($_GET['search']) && $_GET['search'] !== "") {
    $search = $_GET['search'];
    $filter = "WHERE username LIKE '%$search%' 
               OR email LIKE '%$search%' 
               OR role LIKE '%$search%'";
}

// ✅ fetch users
$users = mysqli_query($conn, "SELECT * FROM users $filter ORDER BY id DESC");

// ✅ delete user
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="manage_users.php" class="active">👨‍🎓 Manage Users</a>
    <a href="manage_projects.php">📁 Manage Projects</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<div class="content">
    <h1>Manage Users</h1>

    <!-- ✅ Search Box -->
    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="🔍 Search user..."
               value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
    </form>

    <table class="styled-table">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Action</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($users)): ?>
        <tr>
            <td><?= $row["id"]; ?></td>
            <td><?= $row["username"]; ?></td>
            <td><?php echo isset($row['email']) ? $row['email'] : "-"; ?></td>
            <td><?= ucfirst($row["role"]); ?></td>
            <td>
                <a href="manage_users.php?delete=<?= $row['id']; ?>"
                   onclick="return confirm('Confirm delete user?')"
                   class="btn-danger">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>

    </table>
</div>

</body>
</html>
