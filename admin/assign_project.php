<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("admin");

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../ai_matcher.php";


$projects = $conn->query("
    SELECT project_id, title, description, category
    FROM project
    WHERE project_id NOT IN (
        SELECT project_id FROM assignment
    )
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Evaluator Assignment</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<h1 class="text-3xl font-bold mb-6">AI Evaluator Assignment</h1>

<a href="manage_projects.php"
   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
    ← Back to Manage Projects
    
</a>



<form action="auto_assign_all.php" method="POST" class="mb-8">
    <button class="px-5 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
        Auto Assign ALL Projects
    </button>
</form>

<?php if ($projects->num_rows === 0): ?>
    <div class="bg-white p-6 rounded shadow text-gray-600">
        All projects have been assigned.
    </div>
<?php endif; ?>

<?php while ($p = $projects->fetch_assoc()): ?>

<?php

$projectText = trim(
    $p["title"] . " " .
    $p["description"] . " " .
    $p["category"]
);
$projectVec = create_embedding($projectText);


$evs = $conn->query("
    SELECT 
        ev.evaluator_id,
        ev.name,
        ev.expertise,
        COUNT(a.assignment_id) AS workload
    FROM evaluator ev
    LEFT JOIN assignment a
        ON ev.evaluator_id = a.evaluator_id
    GROUP BY ev.evaluator_id
    ORDER BY workload ASC
");

$rank = [];

while ($ev = $evs->fetch_assoc()) {

    $skillText = $ev["expertise"] ?? "";
    $skillVec  = create_embedding($skillText);
    $aiScore   = cosine_similarity($projectVec, $skillVec); // 0 ~ 1

$expertiseRaw  = strtolower($skillText ?? '');
$expertiseList = array_map('trim', explode(',', $expertiseRaw));


$categorySafe = strtolower($p['category'] ?? '');

$ruleBonus = in_array($categorySafe, $expertiseList, true)
    ? 0.2
    : 0;

    $penalty = $ev["workload"] * 0.05;

    $finalScore = max($aiScore + $ruleBonus - $penalty, 0);

    $rank[] = [
        "id"    => $ev["evaluator_id"],
        "name"  => $ev["name"],
        "score" => round($finalScore * 100, 1),
        "load"  => $ev["workload"]
    ];
}


usort($rank, fn($a, $b) => $b["score"] <=> $a["score"]);
?>

<div class="bg-white shadow rounded-xl p-6 mb-8">

    <h2 class="text-xl font-semibold mb-1">
        <?= htmlspecialchars($p["title"]) ?>
    </h2>

    <p class="text-sm text-gray-500 mb-4">
        Category: <?= htmlspecialchars($p["category"]) ?>
    </p>

    <table class="w-full text-sm">
        <thead>
        <tr class="border-b text-gray-600">
            <th class="py-2 text-left">Evaluator</th>
            <th>Match Score</th>
            <th>Workload</th>
            <th>Action</th>
        </tr>
        </thead>

        <tbody>
        <?php foreach (array_slice($rank, 0, 5) as $r): ?>
        <tr class="border-b">
            <td class="py-2 font-medium">
                <?= htmlspecialchars($r["name"]) ?>
            </td>

            <td>
                <div class="w-full bg-gray-200 rounded">
                    <div class="bg-blue-600 text-white text-xs text-center rounded"
                         style="width: <?= $r["score"] ?>%">
                        <?= $r["score"] ?>%
                    </div>
                </div>
            </td>

            <td class="text-center">
                <?= $r["load"] ?>
            </td>

            <td class="text-center">

                <form method="POST" action="auto_assign.php">
                    <input type="hidden" name="project_id" value="<?= $p["project_id"] ?>">
                    <input type="hidden" name="evaluator_id" value="<?= $r["id"] ?>">

                    <button class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                        Assign
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>

<?php endwhile; ?>

</body>
</html>
