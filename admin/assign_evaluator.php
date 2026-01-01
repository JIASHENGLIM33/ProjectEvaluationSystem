<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("admin");

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../ai_matcher.php";

/* =========================
   获取 project
========================= */
if (!isset($_GET['project_id'])) {
    die("Project not specified.");
}

$projectId = intval($_GET['project_id']);

$stmt = $conn->prepare("
    SELECT project_id, title, description
    FROM project

    WHERE project_id = ?
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("Project not found.");
}

/* =========================
检查是否已分配
========================= */
$check = $conn->prepare("
    SELECT * FROM assignment WHERE project_id = ?
");
$check->bind_param("i", $projectId);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    die("Evaluator already assigned for this project.");
}

/* =========================
获取 evaluators
========================= */
$evaluators = [];
$res = $conn->query("
    SELECT evaluator_id, name, expertise
    FROM evaluator
");

while ($row = $res->fetch_assoc()) {
    $evaluators[] = $row;
}

/* =========================
AI MATCHING
========================= */
$projectText = $project['title'] . " " . $project['description'];

$projectEmbedding = create_embedding($projectText);

$rankedEvaluators = [];

foreach ($evaluators as $ev) {
    $skillEmbedding = create_embedding($ev['expertise']);
    $score = cosine_similarity($projectEmbedding, $skillEmbedding);

    $rankedEvaluators[] = [
        'evaluator_id' => $ev['evaluator_id'],
        'name' => $ev['name'],
        'score' => $score
    ];
}

/* 按匹配度排序 */
usort($rankedEvaluators, fn($a, $b) => $b['score'] <=> $a['score']);

/* 推荐前 3 名 */
$topEvaluators = array_slice($rankedEvaluators, 0, 3);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Assign Evaluator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<h1 class="text-2xl font-bold mb-4">
    Assign Evaluator
</h1>

<div class="bg-white p-6 rounded-xl shadow mb-6">
    <h2 class="font-semibold">Project</h2>
    <p class="text-gray-700"><?= htmlspecialchars($project['project_title']) ?></p>
</div>

<div class="bg-white p-6 rounded-xl shadow">
    <h2 class="font-semibold mb-4">AI Recommended Evaluators</h2>

    <?php foreach ($topEvaluators as $ev): ?>
        <form method="POST" class="flex justify-between items-center border-b py-3">
            <div>
                <p class="font-medium"><?= htmlspecialchars($ev['name']) ?></p>
                <p class="text-sm text-gray-500">
                    Match Score: <?= number_format($ev['score'], 3) ?>
                </p>
            </div>

            <input type="hidden" name="evaluator_id" value="<?= $ev['evaluator_id'] ?>">
            <input type="hidden" name="project_id" value="<?= $projectId ?>">

            <button name="assign" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                Assign
            </button>
        </form>
    <?php endforeach; ?>
</div>

</body>
</html>

<?php
/* =========================
处理 assignment
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {

    $evaluatorId = intval($_POST['evaluator_id']);
    $projectId = intval($_POST['project_id']);

    $stmt = $conn->prepare("
        INSERT INTO assignment (project_id, evaluator_id, assigned_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param("ii", $projectId, $evaluatorId);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
}
