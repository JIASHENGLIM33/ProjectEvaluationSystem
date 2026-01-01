<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("student");

require_once __DIR__ . "/../config/config.php";

$studentId   = $_SESSION["id"];
$studentName = $_SESSION["name"];


$stmt = $conn->prepare("
    SELECT project_id, title, description, status, created_at
    FROM project
    WHERE student_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

<div class="flex">


    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow h-screen fixed left-0 top-0 px-6 py-6">
        <h1 class="text-xl font-semibold mb-6">Student Panel</h1>
        <p class="text-gray-500 mb-8">Project Evaluation System</p>

        <nav class="space-y-2">
            <a href="dashboard.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                Dashboard
            </a>
            <a href="submit_project.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                Submit Project
            </a>
            <a href="status.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg">
                Project Status
            </a>
            <a href="view_feedback.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
                View Feedback
            </a>
        </nav>

        <a href="../logout.php" class="absolute bottom-6 left-6 text-gray-500 hover:text-black">
            Sign Out
        </a>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 p-10">
        <!-- Top Back Button -->
<div class="flex items-center mb-6">
    <a href="dashboard.php"
       class="flex items-center gap-2 text-blue-600 hover:underline">
        ← Back to Dashboard
    </a>
</div>


        <h1 class="text-3xl font-semibold text-gray-900">Project Status</h1>
        <p class="text-gray-600 mb-8">
            Monitor all your submitted projects and their evaluation progress.
        </p>

        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <div class="p-6 bg-white rounded-xl shadow">
                <p class="text-gray-500">Total Projects</p>
                <p class="text-3xl mt-2"><?= count($projects) ?></p>
            </div>

            <div class="p-6 bg-white rounded-xl shadow">
                <p class="text-gray-500">Under Review</p>
                <p class="text-3xl mt-2">
                    <?= count(array_filter($projects, fn($p) => $p['status'] === 'Under Review')) ?>
                </p>
            </div>

            <div class="p-6 bg-white rounded-xl shadow">
                <p class="text-gray-500">Completed</p>
                <p class="text-3xl mt-2">
                    <?= count(array_filter($projects, fn($p) => $p['status'] === 'Completed')) ?>
                </p>
            </div>

        </div>

        <!-- Project Table -->
        <div class="bg-white shadow rounded-xl p-6">

            <h2 class="text-lg font-semibold mb-4">
                Submitted Projects
            </h2>

            <table class="w-full text-left">
                <thead class="border-b text-gray-600">
                    <tr>
                        <th class="py-2">Project Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="5" class="py-6 text-center text-gray-500">
                            No projects submitted yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
                        <tr class="border-b">
                            <td class="py-3 font-medium">
                                <?= htmlspecialchars($p['title']) ?>
                            </td>

                            <td class="text-gray-600">
                                <?= htmlspecialchars(substr($p['description'], 0, 60)) ?>...
                            </td>

                            <td>
                                <span class="px-3 py-1 rounded-full text-sm
                                <?= $p['status'] === 'Completed'
                                    ? 'bg-green-100 text-green-700'
                                    : ($p['status'] === 'Under Review'
                                        ? 'bg-blue-100 text-blue-700'
                                        : 'bg-yellow-100 text-yellow-700'); ?>">
                                    <?= $p['status'] ?>
                                </span>
                            </td>

                            <td>
                                <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                            </td>

                            <td>
                                <a href="view_feedback.php?id=<?= $p['project_id'] ?>"
                                   class="text-blue-600 underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

        </div>

    </main>
</div>

</body>
</html>
