<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("evaluator");

require_once __DIR__ . "/../config/config.php";

$evaluatorId   = $_SESSION["id"];
$evaluatorName = $_SESSION["name"];

/* =========================
   Evaluator Statistics
========================= */

// 已评估项目数
$totalEvaluated = $conn->query("
    SELECT COUNT(*) AS c
    FROM evaluation
    WHERE evaluator_id = $evaluatorId
")->fetch_assoc()["c"];

// 平均分
$avgScore = $conn->query("
    SELECT ROUND(AVG(score), 1) AS avg
    FROM evaluation
    WHERE evaluator_id = $evaluatorId
")->fetch_assoc()["avg"];

// 待评估项目数
$pending = $conn->query("
    SELECT COUNT(*) AS c
    FROM assignment a
    JOIN project p ON a.project_id = p.project_id
    WHERE a.evaluator_id = $evaluatorId
      AND p.status != 'Completed'
")->fetch_assoc()["c"];

/* =========================
   Evaluation History
========================= */
$history = $conn->query("
    SELECT 
        p.title,
        s.name AS student_name,
        e.score,
        e.created_at
    FROM evaluation e
    JOIN project p ON e.project_id = p.project_id
    JOIN student s ON p.student_id = s.student_id
    WHERE e.evaluator_id = $evaluatorId
    ORDER BY e.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

/* =========================
   Assigned Projects
========================= */
$stmt = $conn->prepare("
    SELECT 
        p.project_id,
        p.title,
        p.status,
        p.student_id
    FROM assignment a
    JOIN project p ON a.project_id = p.project_id
    WHERE a.evaluator_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $evaluatorId);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   Statistics
========================= */
$totalAssigned = count($projects);
$underReview = 0;
$completed   = 0;

foreach ($projects as $p) {
    if ($p["status"] === "Under Review") $underReview++;
    if ($p["status"] === "Completed")   $completed++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Evaluator Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

<div class="flex">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow h-screen fixed p-6">
    <h1 class="text-xl font-semibold mb-6">Evaluator Panel</h1>

    <nav class="space-y-2">

        <a href="dashboard.php"
           class="block px-4 py-2 rounded-lg
           <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php'
                ? 'bg-blue-600 text-white'
                : 'hover:bg-gray-200' ?>">
            Dashboard
        </a>

        <a href="assigned_project.php"
           class="block px-4 py-2 rounded-lg hover:bg-gray-200">
            Assigned Projects
        </a>


    </nav>

    <a href="../logout.php"
       class="absolute bottom-6 left-6 text-red-600 hover:underline">
        Logout
    </a>
</aside>


<!-- Main -->
<main class="ml-64 flex-1 p-10">

    <h1 class="text-3xl font-semibold mb-2">
        Welcome, <?= htmlspecialchars($evaluatorName) ?> 👋
    </h1>
    <p class="text-gray-600 mb-8">Here are the projects assigned to you.</p>

    <!-- Top Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

        <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500">
            <p class="text-gray-600">Projects Evaluated</p>
            <p class="text-3xl font-semibold"><?= $totalEvaluated ?></p>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-500">
            <p class="text-gray-600">Average Score</p>
            <p class="text-3xl font-semibold">
                <?= $avgScore !== null ? $avgScore : "N/A" ?>
            </p>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border-l-4 border-yellow-500">
            <p class="text-gray-600">Pending Reviews</p>
            <p class="text-3xl font-semibold"><?= $pending ?></p>
        </div>

    </div>



    <!-- Assignment Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="p-6 bg-white rounded-xl shadow">
            <p>Total Assigned</p>
            <p class="text-3xl font-bold"><?= $totalAssigned ?></p>
        </div>

        <div class="p-6 bg-white rounded-xl shadow">
            <p>Under Review</p>
            <p class="text-3xl font-bold"><?= $underReview ?></p>
        </div>

        <div class="p-6 bg-white rounded-xl shadow">
            <p>Completed</p>
            <p class="text-3xl font-bold"><?= $completed ?></p>
        </div>
    </div>

    <!-- Assigned Projects Table -->
    <div class="bg-white rounded-xl shadow p-6 mb-10">
        <h2 class="text-xl font-semibold mb-4">Assigned Projects</h2>

        <table class="w-full text-left">
            <thead>
            <tr class="border-b text-gray-600">
                <th class="py-2">Title</th>
                <th>Status</th>
                <th>Student ID</th>
                <th>Action</th>
            </tr>
            </thead>

            <tbody>
            <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="4" class="py-6 text-center text-gray-500">
                        No projects assigned.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($projects as $p): ?>
                <tr class="border-b">
                    <td class="py-3"><?= htmlspecialchars($p["title"]) ?></td>
                    <td><?= $p["status"] ?></td>
                    <td><?= $p["student_id"] ?></td>
                    <td>
                        <?php if ($p["status"] !== "Completed"): ?>
                            <a href="evaluate_project.php?project_id=<?= $p["project_id"] ?>"
                               class="text-blue-600 underline">
                                Evaluate
                            </a>
                        <?php else: ?>
                            <span class="text-green-600">Completed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Evaluation History -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Evaluation History</h2>

        <?php if (empty($history)): ?>
            <p class="text-gray-500">No evaluations completed yet.</p>
        <?php else: ?>

        <table class="w-full text-left">
            <thead>
            <tr class="border-b text-gray-600">
                <th class="py-2">Project</th>
                <th>Student</th>
                <th>Score</th>
                <th>Evaluated At</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($history as $h): ?>
                <tr class="border-b">
                    <td class="py-3"><?= htmlspecialchars($h["title"]) ?></td>
                    <td><?= htmlspecialchars($h["student_name"]) ?></td>
                    <td>
                        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700">
                            <?= $h["score"] ?>
                        </span>
                    </td>
                    <td><?= date("d/m/Y", strtotime($h["created_at"])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php endif; ?>
    </div>

</main>
</div>

</body>
</html>
