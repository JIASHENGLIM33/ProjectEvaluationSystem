<?php


require_once __DIR__ . "/../config/auth_check.php";
allow_role("student");

require_once __DIR__ . "/../config/config.php";

$studentId = $_SESSION["id"];


$stmt = $conn->prepare("
    SELECT project_id, title, description, status
    FROM project
    WHERE student_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$selectedProjectId = $_GET["project_id"] ?? ($projects[0]["project_id"] ?? null);


$feedback = null;

if ($selectedProjectId) {
    $stmt = $conn->prepare("
        SELECT 
            e.score,
            e.fuzzy_score,
            e.feedback,
            e.rubric_json,
            e.created_at,
            ev.name AS evaluator_name
        FROM evaluation e
        JOIN evaluator ev ON e.evaluator_id = ev.evaluator_id
        WHERE e.project_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $selectedProjectId);
    $stmt->execute();
    $feedback = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Feedback</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">

<div class="max-w-6xl mx-auto bg-white rounded-xl shadow p-6">

    <h1 class="text-2xl font-bold mb-6">Project Feedback</h1>

    <div class="grid grid-cols-3 gap-6">


        <div class="col-span-1 border-r pr-4">
            <h3 class="font-semibold mb-3">Your Projects</h3>

            <?php if (empty($projects)): ?>
                <p class="text-sm text-gray-500">No projects submitted yet.</p>
            <?php endif; ?>

            <?php foreach ($projects as $p): ?>
                <a href="?project_id=<?= $p['project_id'] ?>">
                    <div class="mb-3 p-3 rounded-lg border
                        <?= $p['project_id'] == $selectedProjectId
                            ? 'bg-blue-50 border-blue-500'
                            : 'hover:bg-gray-50' ?>">
                        
                        <p class="font-medium text-gray-900">
                            <?= htmlspecialchars($p['title']) ?>
                        </p>

                        <p class="text-sm text-gray-500 truncate">
                            <?= htmlspecialchars($p['description']) ?>
                        </p>

                        <span class="inline-block mt-1 text-xs px-2 py-1 rounded
                            <?= $p['status'] === 'Completed'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-yellow-100 text-yellow-700' ?>">
                            <?= $p['status'] ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>


        <div class="col-span-2 pl-4">

            <?php if (!$selectedProjectId): ?>

                <p class="text-gray-500">No project selected.</p>

            <?php elseif (!$feedback): ?>

                <div class="p-6 bg-yellow-50 border border-yellow-200 rounded">
                    <h3 class="font-semibold text-yellow-800">Evaluation Pending</h3>
                    <p class="text-sm text-yellow-700 mt-2">
                        Your project has not been evaluated yet.
                    </p>
                </div>

            <?php else: ?>

                <?php
                $rubric = json_decode($feedback["rubric_json"], true);
                ?>

                <div class="space-y-5">

                    <!-- Summary -->
                    <div class="p-4 bg-gray-50 rounded">
                        <p><strong>Evaluator:</strong>
                            <?= htmlspecialchars($feedback['evaluator_name']) ?>
                        </p>
                        <p><strong>Final Score:</strong>
                            <?= $feedback['score'] ?> / 100
                        </p>
                    
                        <p class="text-sm text-gray-500">
                            Evaluated on <?= date("d/m/Y", strtotime($feedback['created_at'])) ?>
                        </p>
                    </div>

                    <!-- Rubric -->
                    <div class="p-4 bg-white border rounded">
                        <h3 class="font-semibold mb-2">Rubric Breakdown</h3>
                        <ul class="list-disc pl-6 text-gray-700">
                            <?php foreach ($rubric as $k => $v): ?>
                                <li><?= ucfirst($k) ?>: <?= $v ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Feedback -->
                    <div class="p-4 bg-white border rounded">
                        <h3 class="font-semibold mb-2">Evaluator Feedback</h3>
                        <p class="text-gray-700">
                            <?= nl2br(htmlspecialchars($feedback['feedback'])) ?>
                        </p>
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <div class="mt-6">
        <a href="dashboard.php" class="text-blue-600 underline">
            ← Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>
