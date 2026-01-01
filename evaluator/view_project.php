<?php
session_start();
require_once("../config/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'evaluator') {
    header("Location: ../login.php");
    exit();
}

$project_id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("Project not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>View Project</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<div class="max-w-3xl mx-auto mt-10 bg-white shadow-md p-8 rounded-xl">

    <h1 class="text-3xl font-bold mb-4">
        <?= htmlspecialchars($project['title']) ?>
    </h1>

    <p class="text-gray-700 mb-6"><?= nl2br(htmlspecialchars($project['description'])) ?></p>

    <h2 class="font-semibold text-gray-900 mb-2">Keywords:</h2>
    <div class="flex gap-2 flex-wrap mb-6">
        <?php foreach (explode(",", $project['keywords']) as $kw): ?>
            <span class="px-3 py-1 bg-gray-200 rounded-full text-sm"><?= htmlspecialchars($kw) ?></span>
        <?php endforeach; ?>
    </div>

    <a href="project_list.php" class="text-blue-600 underline">Back</a>

</div>

</body>
</html>
