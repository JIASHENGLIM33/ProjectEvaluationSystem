<?php
/*************************************************
 * admin/view_result.php
 * View Final Evaluation Result (Weighted)
 *************************************************/

require_once __DIR__ . "/../config/auth_check.php";
allow_role("admin");

require_once __DIR__ . "/../config/config.php";

/* =========================
   Fuzzy Logic
========================= */
function calculateFuzzyScore(float $score): int {
    if ($score >= 85) return 95;
    if ($score >= 70) return 85;
    if ($score >= 55) return 70;
    if ($score >= 40) return 55;
    return 40;
}

/* =========================
   Get project_id
========================= */
$projectId = intval($_GET["project_id"] ?? 0);
if ($projectId <= 0) {
    die("Invalid project ID.");
}

/* =========================
   Fetch project info
========================= */
$stmt = $conn->prepare("
    SELECT 
        p.project_id,
        p.title,
        p.category,
        p.status,
        s.name AS student_name
    FROM project p
    JOIN student s ON p.student_id = s.student_id
    WHERE p.project_id = ?
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("Project not found.");
}

/* =========================
   Fetch evaluations + weight
========================= */
$stmt = $conn->prepare("
    SELECT
        e.score,
        e.fuzzy_score,
        e.feedback,
        e.rubric_json,
        ev.name AS evaluator_name,
        a.weight
    FROM evaluation e
    JOIN evaluator ev ON e.evaluator_id = ev.evaluator_id
    JOIN assignment a 
        ON a.project_id = e.project_id 
       AND a.evaluator_id = e.evaluator_id
    WHERE e.project_id = ?
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$evaluations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($evaluations)) {
    die("No evaluation data available for this project.");
}

/* =========================
   Calculate final score
========================= */
$finalScore = 0;
foreach ($evaluations as $ev) {
    $finalScore += ($ev["score"] * $ev["weight"]);
}
$finalScore = round($finalScore, 2);
$finalFuzzy = calculateFuzzyScore($finalScore);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Result</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow">

<!-- ================= Project Info ================= -->
<h1 class="text-2xl font-bold mb-2">Project Evaluation Result</h1>

<p class="text-gray-700">
    <strong><?= htmlspecialchars($project["title"]) ?></strong>
</p>
<p class="text-sm text-gray-600 mb-6">
    Student: <?= htmlspecialchars($project["student_name"]) ?> |
    Category: <?= htmlspecialchars($project["category"]) ?> |
    Status: <?= htmlspecialchars($project["status"]) ?>
</p>

<!-- ================= Final Score ================= -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

    <div class="bg-blue-50 p-4 rounded-lg">
        <p class="text-sm text-gray-600">Final Score</p>
        <p class="text-3xl font-bold text-blue-700">
            <?= $finalScore ?>
        </p>
    </div>

    <div class="bg-green-50 p-4 rounded-lg">
        <p class="text-sm text-gray-600">Fuzzy Score</p>
        <p class="text-3xl font-bold text-green-700">
            <?= $finalFuzzy ?>
        </p>
    </div>

    <div class="bg-gray-50 p-4 rounded-lg">
        <p class="text-sm text-gray-600">Evaluator Count</p>
        <p class="text-3xl font-bold">
            <?= count($evaluations) ?>
        </p>
    </div>

</div>

<!-- ================= Evaluator Details ================= -->
<h2 class="text-xl font-semibold mb-4">Evaluator Breakdown</h2>

<?php foreach ($evaluations as $ev): ?>
<?php
    $rubric = $ev["rubric_json"]
        ? json_decode($ev["rubric_json"], true)
        : [];
?>

<div class="border rounded-lg p-5 mb-6">

    <div class="flex justify-between mb-3">
        <h3 class="font-semibold text-lg">
            <?= htmlspecialchars($ev["evaluator_name"]) ?>
        </h3>
        <span class="text-sm text-gray-500">
            Weight: <?= $ev["weight"] * 100 ?>%
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">

        <div class="bg-blue-50 p-3 rounded">
            <p class="text-sm text-gray-600">Raw Score</p>
            <p class="text-2xl font-bold text-blue-700">
                <?= $ev["score"] ?>
            </p>
        </div>

        <div class="bg-green-50 p-3 rounded">
            <p class="text-sm text-gray-600">Fuzzy Score</p>
            <p class="text-2xl font-bold text-green-700">
                <?= $ev["fuzzy_score"] ?>
            </p>
        </div>

        <div class="bg-gray-50 p-3 rounded">
            <p class="text-sm text-gray-600">Weighted Contribution</p>
            <p class="text-2xl font-bold">
                <?= round($ev["score"] * $ev["weight"], 2) ?>
            </p>
        </div>

    </div>

    <!-- Rubric -->
    <h4 class="font-semibold mb-2">Rubric Breakdown</h4>

    <?php if (empty($rubric)): ?>
        <p class="text-sm text-gray-500">No rubric data.</p>
    <?php else: ?>
        <table class="w-full text-sm border mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">Criteria</th>
                    <th class="border p-2 text-center">Score</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rubric as $k => $v): ?>
                <tr>
                    <td class="border p-2"><?= htmlspecialchars($k) ?></td>
                    <td class="border p-2 text-center font-semibold"><?= $v ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Feedback -->
    <h4 class="font-semibold mb-1">Evaluator Feedback</h4>
    <p class="text-gray-700 whitespace-pre-line">
        <?= htmlspecialchars($ev["feedback"]) ?>
    </p>

</div>
<?php endforeach; ?>

<!-- Back -->
<div class="mt-6">
    <a href="dashboard.php" class="text-blue-600 underline">
        ← Back to Admin Dashboard
    </a>
</div>

</div>
</body>
</html>
