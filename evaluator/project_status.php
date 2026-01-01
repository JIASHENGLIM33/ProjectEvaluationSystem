<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("evaluator");

require_once __DIR__ . "/../config/config.php";

$evaluatorId   = $_SESSION["id"];
$evaluatorName = $_SESSION["name"];

/* =========================
   获取该 evaluator 的项目
========================= */
$stmt = $conn->prepare("
    SELECT 
        p.project_id,
        p.title,
        p.description,
        p.status,
        p.student_id,
        p.created_at,
        e.score
    FROM assignment a
    JOIN project p ON a.project_id = p.project_id
    LEFT JOIN evaluation e 
        ON e.project_id = p.project_id 
       AND e.evaluator_id = a.evaluator_id
    WHERE a.evaluator_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $evaluatorId);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   统计
========================= */
$total      = count($projects);
$pending    = count(array_filter($projects, fn($p) => $p["status"] === "Pending"));
$underReview= count(array_filter($projects, fn($p) => $p["status"] === "Under Review"));
$completed  = count(array_filter($projects, fn($p) => $p["status"] === "Completed"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Evaluator | Project Status</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
<div class="flex">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow h-screen fixed px-6 py-6">
    <h1 class="text-xl font-semibold mb-6">Evaluator Panel</h1>

    <nav class="space-y-2">
        <a href="dashboard.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
            Dashboard
        </a>
        <a href="project_status.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg">
            Project Status
        </a>
        <a href="provide_feedback.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">
            Provide Feedback
        </a>
    </nav>

    <a href="../logout.php" class="absolute bottom-6 left-6 text-red-600">
        Logout
    </a>
</aside>

<!-- Main -->
<main class="ml-64 flex-1 p-10">

<h1 class="text-3xl font-semibold mb-2">
    Project Status
</h1>
<p class="text-gray-600 mb-8">
    Projects assigned to you
</p>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded shadow">
        <p>Total</p>
        <p class="text-3xl"><?= $total ?></p>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <p>Pending</p>
        <p class="text-3xl"><?= $pending ?></p>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <p>Under Review</p>
        <p class="text-3xl"><?= $underReview ?></p>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <p>Completed</p>
        <p class="text-3xl"><?= $completed ?></p>
    </div>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow p-6">
<table class="w-full text-left">
<thead class="border-b text-gray-600">
<tr>
    <th class="py-2">Title</th>
    <th>Status</th>
    <th>Score</th>
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
    <td><?= $p["score"] ?? "-" ?></td>
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

</main>
</div>
</body>
</html>
