<?php

require_once __DIR__ . "/../config/auth_check.php";
allow_role("admin");

require_once __DIR__ . "/../config/config.php";


function generateAIFeedback(array $rubric, int $totalScore): string {
    if ($totalScore >= 85) return "Consistently high-quality evaluations indicating strong assessment alignment.";
    if ($totalScore >= 70) return "Generally balanced evaluations with good judgment.";
    if ($totalScore >= 55) return "Acceptable evaluations but scoring consistency could be improved.";
    if ($totalScore >= 40) return "Evaluation scores show inconsistency and may require review.";
    return "Evaluation quality is significantly below expectations.";
}


$evaluators = $conn->query("
    SELECT 
        ev.evaluator_id,
        ev.name,
        COUNT(e.evaluation_id) AS total_reviews,
        AVG(e.score) AS avg_score,
        AVG(e.fuzzy_score) AS avg_fuzzy
    FROM evaluator ev
    LEFT JOIN evaluation e
        ON ev.evaluator_id = e.evaluator_id
    GROUP BY ev.evaluator_id
    ORDER BY ev.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Evaluation Analytics Report</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<div class="max-w-7xl mx-auto bg-white p-6 rounded-xl shadow">

<h1 class="text-2xl font-bold mb-6">
    Evaluator Performance & AI Analysis Report
</h1>

<table class="w-full text-sm border-collapse">
<thead>
<tr class="border-b bg-gray-50">
    <th class="p-3 text-left">Evaluator</th>
    <th>Total Reviews</th>
    <th>Avg Score</th>
    <th>Avg Fuzzy</th>
    <th>AI Insight</th>
</tr>
</thead>

<tbody>
<?php while ($ev = $evaluators->fetch_assoc()): ?>

<?php

$rubs = $conn->prepare("
    SELECT rubric_json, score
    FROM evaluation
    WHERE evaluator_id = ?
");
$rubs->bind_param("i", $ev["evaluator_id"]);
$rubs->execute();
$res = $rubs->get_result();

$combinedScore = 0;
$count = 0;
$lastRubric = [];

while ($r = $res->fetch_assoc()) {
    $combinedScore += $r["score"];
    $count++;
    $lastRubric = json_decode($r["rubric_json"] ?? "{}", true);
}

$avgScore = $count ? round($combinedScore / $count) : 0;
$aiInsight = $count ? generateAIFeedback($lastRubric, $avgScore) : "-";
?>

<tr class="border-b">
    <td class="p-3 font-medium">
        <?= htmlspecialchars($ev["name"]) ?>
    </td>

    <td class="text-center">
        <?= $ev["total_reviews"] ?>
    </td>

    <td class="text-center">
        <?= $ev["avg_score"] ? round($ev["avg_score"], 1) : "-" ?>
    </td>

    <td class="text-center">
        <?= $ev["avg_fuzzy"] ? round($ev["avg_fuzzy"], 1) : "-" ?>
    </td>

    <td class="text-gray-700 text-sm">
        <?= htmlspecialchars($aiInsight) ?>
    </td>
</tr>

<?php endwhile; ?>
</tbody>
</table>

<div class="mt-6">
    <a href="dashboard.php" class="text-blue-600 underline">
        ← Back to Admin Dashboard
    </a>
</div>

</div>

</body>
</html>
