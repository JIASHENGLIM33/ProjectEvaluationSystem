<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("evaluator");

require_once __DIR__ . "/../config/config.php";

$evaluator_id = $_SESSION["id"];


$stmt = $conn->prepare("
    SELECT
        p.project_id,
        p.title,
        p.description,
        p.status,
        p.student_id,
        (
            SELECT COUNT(*)
            FROM evaluation e
            WHERE e.project_id = p.project_id
            AND e.evaluator_id = ?
        ) AS has_feedback
    FROM assignment a
    JOIN project p ON a.project_id = p.project_id
    WHERE a.evaluator_id = ?
");
$stmt->bind_param("ii", $evaluator_id, $evaluator_id);
$stmt->execute();
$projects = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Evaluator | Project Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

    <!-- Top Navigation -->
    <div class="w-full bg-white shadow-sm p-4 flex justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-800">Evaluator Dashboard</h1>
        <a href="../logout.php" class="text-red-600 font-medium hover:underline">Logout</a>
    </div>

    <div class="max-w-6xl mx-auto mt-8">

        <h2 class="text-2xl font-bold text-gray-800 mb-4">Assigned Projects</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <?php while ($p = $projects->fetch_assoc()): ?>

            <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
                
                <h3 class="text-xl font-bold text-gray-900 mb-2">
                    <?= htmlspecialchars($p['title']) ?>
                </h3>

                <p class="text-gray-600 mb-4 line-clamp-2">
                    <?= htmlspecialchars($p['description']) ?>
                </p>

                <div class="mb-4">
                    <span class="px-3 py-1 rounded-full text-sm 
                        <?php
                        echo $p['status'] == 'Completed' ? 'bg-green-100 text-green-700' :
                             ($p['status'] == 'Under Review' ? 'bg-yellow-100 text-yellow-700' :
                             'bg-gray-100 text-gray-700');
                        ?>">
                        <?= $p['status'] ?>
                    </span>
                </div>

                <div class="flex justify-between items-center mt-4">

                    <a href="view_project.php?id=<?= $p['id'] ?>" 
                       class="text-blue-600 hover:underline font-medium">
                        View Project
                    </a>

                    <?php if ($p['has_feedback'] > 0): ?>
                        <!-- Already submitted feedback -->
                        <span class="px-4 py-2 text-sm bg-green-100 border border-green-300 text-green-700 rounded">
                            Feedback Submitted
                        </span>
                    <?php else: ?>
                        <!-- Not yet submitted feedback -->
                        <a href="provide_feedback.php?id=<?= $p['id'] ?>"
                           class="px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded shadow">
                           Provide Feedback
                        </a>
                    <?php endif; ?>
                </div>

            </div>

            <?php endwhile; ?>

        </div>
    </div>

</body>
</html>
