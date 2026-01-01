<?php
require_once "../config/config.php";
require_once "../config/auth_check.php";

allow_role("admin");

// 读取所有学生
$students = $conn->query("
    SELECT student_id, name, email, program, intake, created_at
    FROM student
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students | PEMS</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-6xl mx-auto mt-10 p-6 bg-white shadow rounded-xl">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Student Management</h1>
        <p class="text-sm text-gray-600">
            View and remove student accounts (self-registered users)
        </p>
    </div>

    <!-- Student Table -->
    <div class="overflow-x-auto">
        <table class="w-full border border-gray-200 rounded-lg">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-left">Program</th>
                    <th class="p-3 text-left">Intake</th>
                    <th class="p-3 text-left">Registered At</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($students->num_rows === 0): ?>
                <tr>
                    <td colspan="6" class="p-6 text-center text-gray-500">
                        No student accounts found.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($s = $students->fetch_assoc()): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($s['email']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($s['program'] ?? '') ?></td>
                        <td class="p-3"><?= htmlspecialchars($s['intake'] ?? '') ?></td>

                        <td class="p-3 text-sm text-gray-600">
                            <?= date("d M Y", strtotime($s['created_at'])) ?>
                        </td>

                        <td class="p-3">
                            <a href="delete_student.php?id=<?= $s['student_id'] ?>"
                               onclick="return confirm(
                                   'Are you sure you want to delete this student?\n\n' +
                                   'All related projects and evaluations may also be affected.'
                               )"
                               class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Back -->
    <div class="mt-6">
        <a href="dashboard.php" class="text-blue-600 underline">
            ← Back to Admin Dashboard
        </a>
    </div>

</div>

</body>
</html>
