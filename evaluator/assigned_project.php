<?php
/*************************************************
 * evaluator/assigned_project.php
 * LIST ASSIGNED PROJECTS (NO project_id REQUIRED)
 *************************************************/

require_once __DIR__ . "/../config/auth_check.php";
allow_role("evaluator");

require_once __DIR__ . "/../config/config.php";

$evaluatorId = $_SESSION["id"];

/* =========================================================
   Fetch assigned projects
========================================================= */
$stmt = $conn->prepare("
    SELECT 
        p.project_id,
        p.title,
        p.category,
        p.status,
        p.created_at,
        s.name AS student_name
    FROM assignment a
    INNER JOIN project p ON a.project_id = p.project_id
    INNER JOIN student s ON p.student_id = s.student_id
    WHERE a.evaluator_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $evaluatorId);
$stmt->execute();
$projects = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assigned Projects</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">My Assigned Projects</h1>
        <a href="dashboard.php" class="text-blue-600 underline">
            ← Back to Dashboard
        </a>
    </div>

    <?php if ($projects->num_rows === 0): ?>
        <div class="text-gray-600">
            No projects assigned yet.
        </div>
    <?php else: ?>

    <table class="w-full border-collapse text-sm">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-3">Project Title</th>
                <th class="p-3">Student</th>
                <th class="p-3">Category</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-center">Action</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($p = $projects->fetch_assoc()): ?>
            <tr class="border-b">
                <td class="p-3 font-medium">
                    <?= htmlspecialchars($p["title"]) ?>
                </td>
                <td class="p-3">
                    <?= htmlspecialchars($p["student_name"]) ?>
                </td>
                <td class="p-3">
                    <?= htmlspecialchars($p["category"]) ?>
                </td>
                <td class="p-3">
                    <span class="px-2 py-1 rounded text-xs
                        <?= $p["status"] === "Completed"
                            ? "bg-green-100 text-green-700"
                            : "bg-yellow-100 text-yellow-700" ?>">
                        <?= $p["status"] ?>
                    </span>
                </td>
                <td class="p-3 text-center">
                    <?php if ($p["status"] === "Completed"): ?>
                        <span class="text-gray-400">Evaluated</span>
                    <?php else: ?>
                        <a href="evaluate_project.php?project_id=<?= $p["project_id"] ?>"
                           class="px-4 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Evaluate
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php endif; ?>

</div>

</body>
</html>
