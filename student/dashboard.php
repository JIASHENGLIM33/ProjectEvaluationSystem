<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("student");
require_once __DIR__ . "/../config/config.php";

$studentId   = $_SESSION["id"];
$studentName = $_SESSION["name"];

/* =========================
   获取学生项目
========================= */
$stmt = $conn->prepare("
    SELECT project_id, title, status, created_at
    FROM project
    WHERE student_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   Student Average Score
========================= */
$stmt = $conn->prepare("
    SELECT ROUND(AVG(e.score), 1) AS avg_score
    FROM evaluation e
    JOIN project p ON e.project_id = p.project_id
    WHERE p.student_id = ?
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$averageScore = $stmt->get_result()->fetch_assoc()["avg_score"];

/* =========================
   Chart Data
========================= */
$stmt = $conn->prepare("
    SELECT p.title, e.score
    FROM evaluation e
    JOIN project p ON e.project_id = p.project_id
    WHERE p.student_id = ?
    ORDER BY e.created_at ASC
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$chartLabels = [];
$chartScores = [];
foreach ($rows as $r) {
    $chartLabels[] = $r["title"];
    $chartScores[] = $r["score"];
}

/* =========================
   统计
========================= */
$totalProjects = count($projects);
$completed     = count(array_filter($projects, fn($p) => $p['status'] === 'Completed'));
$underReview   = count(array_filter($projects, fn($p) => $p['status'] === 'Under Review'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
<div class="flex">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow h-screen fixed px-6 py-6">
    <h1 class="text-xl font-semibold mb-6">Student Panel</h1>
    <p class="text-gray-500 mb-8">Project Evaluation System</p>

    <nav class="space-y-2">
        <a href="dashboard.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg">Dashboard</a>
        <a href="submit_project.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Submit Project</a>
        <a href="status.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Project Status</a>
        <a href="view_feedback.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">View Feedback</a>
    </nav>

    <a href="../logout.php" class="absolute bottom-6 left-6 text-gray-500">Sign Out</a>
</aside>

<!-- Main -->
<main class="flex-1 ml-64 p-10">

<h1 class="text-3xl font-semibold">Welcome, <?= htmlspecialchars($studentName) ?> 👋</h1>
<p class="text-gray-600 mb-8">Track your submissions and results.</p>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">

    <div class="p-6 bg-white rounded-xl shadow">
        <p class="text-gray-600">Total Projects</p>
        <p class="text-3xl font-semibold"><?= $totalProjects ?></p>
    </div>

    <div class="p-6 bg-white rounded-xl shadow">
        <p class="text-gray-600">Completed</p>
        <p class="text-3xl font-semibold"><?= $completed ?></p>
    </div>

    <div class="p-6 bg-white rounded-xl shadow">
        <p class="text-gray-600">Under Review</p>
        <p class="text-3xl font-semibold"><?= $underReview ?></p>
    </div>

    <div class="p-6 bg-white rounded-xl shadow">
        <p class="text-gray-600">Avg Score</p>

    <?php if ($averageScore === null): ?>
        <p class="text-2xl font-semibold mt-2 text-gray-400">N/A</p>
        <p class="text-sm text-gray-400">Not evaluated yet</p>
    <?php else: ?>
        <p class="text-3xl font-semibold mt-2 text-blue-600">
            <?= $averageScore ?>
        </p>
        <p class="text-sm text-gray-500">Based on evaluated projects</p>
        <?php endif; ?>
    </div>

    <!-- Score Trend Chart -->
<div class="bg-white p-6 rounded-xl shadow mb-10">
    <h2 class="text-lg font-semibold mb-4">Score Trend</h2>

    <?php if (empty($chartScores)): ?>
        <p class="text-gray-500">No evaluation data available.</p>
    <?php else: ?>
        <canvas id="scoreChart" height="100"></canvas>
    <?php endif; ?>
</div>


</div>

<!-- Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="bg-white p-6 rounded-xl shadow mb-10">
    <h2 class="font-semibold mb-4">Project Scores</h2>

    <?php if (empty($chartScores)): ?>
        <p class="text-gray-500">No evaluated projects yet.</p>
    <?php else: ?>
        <canvas id="scoreChart" height="120"></canvas>
    <?php endif; ?>
</div>



<!-- Evaluation Details -->
<div class="bg-white p-6 rounded-xl shadow mb-10">
    <h2 class="text-xl font-semibold mb-4">Evaluation Details</h2>

<?php if (empty($evaluations)): ?>
    <p class="text-gray-500">No evaluations available yet.</p>
<?php else: ?>

<?php foreach ($evaluations as $row): ?>
<?php
    $rubric = $row["rubric_json"]
        ? json_decode($row["rubric_json"], true)
        : [];
?>

<div class="border rounded-lg p-5 mb-6">

    <div class="flex justify-between items-center mb-3">
        <h3 class="font-semibold text-lg">
            <?= htmlspecialchars($row["title"]) ?>
        </h3>
        <span class="text-sm text-gray-500">
            Evaluated on <?= date("d/m/Y", strtotime($row["created_at"])) ?>
        </span>
    </div>

    <!-- Scores -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-blue-50 p-3 rounded">
            <p class="text-sm text-gray-600">Final Score</p>
            <p class="text-2xl font-bold text-blue-700">
                <?= $row["score"] ?>
            </p>
        </div>

        <div class="bg-green-50 p-3 rounded">
            <p class="text-sm text-gray-600">Fuzzy Score</p>
            <p class="text-2xl font-bold text-green-700">
                <?= $row["fuzzy_score"] ?? "N/A" ?>
            </p>
        </div>

        <div class="bg-gray-50 p-3 rounded">
            <p class="text-sm text-gray-600">Evaluator</p>
            <p class="font-medium">
                <?= htmlspecialchars($row["evaluator_name"]) ?>
            </p>
        </div>
    </div>

    <!-- Rubric -->
    <div class="mb-4">
        <h4 class="font-semibold mb-2">Rubric Breakdown</h4>

        <?php if (empty($rubric)): ?>
            <p class="text-gray-500 text-sm">No rubric data provided.</p>
        <?php else: ?>
            <table class="w-full text-sm border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 text-left">Criteria</th>
                        <th class="p-2 text-center">Score</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rubric as $criteria => $value): ?>
                    <tr class="border-t">
                        <td class="p-2"><?= htmlspecialchars($criteria) ?></td>
                        <td class="p-2 text-center font-semibold">
                            <?= $value ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Feedback -->
    <div>
        <h4 class="font-semibold mb-1">Evaluator Feedback</h4>
        <p class="text-gray-700 whitespace-pre-line">
            <?= htmlspecialchars($row["feedback"]) ?>
        </p>
    </div>

</div>
<?php endforeach; ?>
<?php endif; ?>

</div>

</main>
</div>

<?php if (!empty($chartScores)): ?>
<script>
const ctx = document.getElementById('scoreChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Project Score',
            data: <?= json_encode($chartScores) ?>,
            borderColor: '#2563eb', // blue-600
            backgroundColor: 'rgba(37, 99, 235, 0.15)',
            tension: 0.35,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: '#2563eb'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                min: 0,
                max: 100,
                ticks: {
                    stepSize: 10
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
<?php endif; ?>


</body>
</html>
