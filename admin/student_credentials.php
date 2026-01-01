<?php
require_once "../config/auth_check.php";
allow_role("admin");
require_once "../config/config.php";

$list = $conn->query("
    SELECT student_name, email, raw_password, created_at
    FROM student_credentials
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Student Account List</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">

<h1 class="text-2xl font-bold mb-4">Student Login Credentials</h1>

<table class="w-full text-left border">
    <thead class="bg-gray-100">
        <tr>
            <th class="p-2">Name</th>
            <th>Email</th>
            <th>Password</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $s): ?>
        <tr class="border-t">
            <td class="p-2"><?= htmlspecialchars($s["student_name"]) ?></td>
            <td><?= htmlspecialchars($s["email"]) ?></td>
            <td class="font-mono text-blue-600"><?= $s["raw_password"] ?></td>
            <td><?= $s["created_at"] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</div>

</body>
</html>
