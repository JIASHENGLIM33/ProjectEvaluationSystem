<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("admin");
require_once __DIR__ . "/../config/config.php";

/* =========================
   System Stats
========================= */
$totalStudents = $conn->query("SELECT COUNT(*) c FROM student")->fetch_assoc()["c"];
$totalEvaluators = $conn->query("SELECT COUNT(*) c FROM evaluator")->fetch_assoc()["c"];
$totalProjects = $conn->query("SELECT COUNT(*) c FROM project")->fetch_assoc()["c"];
$completedProjects = $conn->query("
    SELECT COUNT(*) c FROM project WHERE status='Completed'
")->fetch_assoc()["c"];

/* =========================
   Recent Projects + Evaluation
========================= */
$projects = $conn->query("
    SELECT 
        p.project_id,
        p.title,
        p.status,
        s.name AS student_name,
        e.name AS evaluator_name,
        ev.evaluation_id
    FROM project p
    JOIN student s ON p.student_id = s.student_id
    LEFT JOIN assignment a ON p.project_id = a.project_id
    LEFT JOIN evaluator e ON a.evaluator_id = e.evaluator_id
    LEFT JOIN evaluation ev 
        ON ev.project_id = p.project_id
       AND ev.evaluator_id = a.evaluator_id
    ORDER BY p.created_at DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

/* =========================
   Evaluator Workload Overview
========================= */
$evaluators = $conn->query("
    SELECT 
        ev.evaluator_id,
        ev.name,
        COUNT(DISTINCT a.project_id) AS total_assigned,
        SUM(CASE WHEN p.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN p.status != 'Completed' THEN 1 ELSE 0 END) AS pending,
        ROUND(AVG(e.score), 1) AS avg_score
    FROM evaluator ev
    LEFT JOIN assignment a ON ev.evaluator_id = a.evaluator_id
    LEFT JOIN project p ON a.project_id = p.project_id
    LEFT JOIN evaluation e ON p.project_id = e.project_id
    GROUP BY ev.evaluator_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
<div class="flex">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow h-screen fixed px-6 py-6">
    <h1 class="text-xl font-semibold mb-6 text-blue-600">PEMS Admin</h1>

    <nav class="space-y-2">
        <a href="dashboard.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg">Dashboard</a>
        <a href="assign_project.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Assign Evaluator</a>
        <a href="manage_students.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Manage Students</a>
        <a href="manage_evaluators.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Manage Evaluators</a>
        <a href="manage_projects.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Manage Projects</a>
    </nav>

    <a href="../logout.php" class="absolute bottom-6 left-6 text-red-600">Logout</a>
</aside>

<!-- Main -->
<main class="flex-1 ml-64 p-10">

<h1 class="text-3xl font-semibold mb-6">Admin Dashboard</h1>

<!-- =========================
     System Stats
========================= -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
    <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500">
        <p class="text-gray-600">Students</p>
        <p class="text-3xl font-semibold"><?= $totalStudents ?></p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-500">
        <p class="text-gray-600">Evaluators</p>
        <p class="text-3xl font-semibold"><?= $totalEvaluators ?></p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow border-l-4 border-yellow-500">
        <p class="text-gray-600">Projects</p>
        <p class="text-3xl font-semibold"><?= $totalProjects ?></p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow border-l-4 border-purple-500">
        <p class="text-gray-600">Completed</p>
        <p class="text-3xl font-semibold"><?= $completedProjects ?></p>
    </div>
</div>

<!-- =========================
     Evaluator Workload
========================= -->
<div class="bg-white rounded-xl shadow p-6 mb-10">
<h2 class="text-xl font-semibold mb-4">Evaluator Workload Overview</h2>

<table class="w-full text-left">
<thead>
<tr class="border-b text-gray-600">
    <th>Evaluator</th>
    <th>Assigned</th>
    <th>Completed</th>
    <th>Pending</th>
    <th>Avg Score</th>
    <th>Load</th>
</tr>
</thead>

<tbody>
<?php while ($ev = $evaluators->fetch_assoc()):
    $load = $ev["pending"] >= 5 ? "High" : ($ev["pending"] >= 3 ? "Medium" : "Low");
?>
<tr class="border-b">
    <td class="py-3 font-medium"><?= htmlspecialchars($ev["name"]) ?></td>
    <td><?= $ev["total_assigned"] ?></td>
    <td><?= $ev["completed"] ?></td>
    <td><?= $ev["pending"] ?></td>
    <td><?= $ev["avg_score"] ?? "N/A" ?></td>
    <td>
        <span class="px-3 py-1 rounded text-sm
        <?= $load === "High" ? "bg-red-100 text-red-700" :
           ($load === "Medium" ? "bg-yellow-100 text-yellow-700" :
           "bg-green-100 text-green-700") ?>">
            <?= $load ?>
        </span>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>


<!-- =========================
     Recent Projects
========================= -->
<div class="bg-white rounded-xl shadow p-6">
<h2 class="text-xl font-semibold mb-4">Recent Projects</h2>

<table class="w-full text-left">
<thead>
<tr class="border-b text-gray-600">
    <th>Project</th>
    <th>Student</th>
    <th>Evaluator</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach ($projects as $p): ?>
<tr class="border-b">
    <td class="py-3"><?= htmlspecialchars($p["title"]) ?></td>
    <td><?= htmlspecialchars($p["student_name"]) ?></td>
    <td><?= $p["evaluator_name"] ?? "Not Assigned" ?></td>
    <td>
        <span class="px-3 py-1 rounded-full text-sm
            <?= $p["status"] === "Completed"
                ? "bg-green-100 text-green-700"
                : ($p["status"] === "Under Review"
                    ? "bg-blue-100 text-blue-700"
                    : "bg-yellow-100 text-yellow-700"); ?>">
            <?= $p["status"] ?>
        </span>
    </td>
    <td>
        <?php if ($p["evaluation_id"]): ?>
            <a href="view_result.php?project_id=<?= $p["project_id"] ?>"
               class="text-blue-600 underline">
                View Result
            </a>
        <?php else: ?>
            <span class="text-gray-400 text-sm">N/A</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</main>
</div>
</body>
</html>
