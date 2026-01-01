<?php
require_once "../config/config.php";
require_once "../config/auth_check.php";

allow_role("admin");


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["assign"])) {

    $projectId   = intval($_POST["project_id"]);
    $evaluatorId = intval($_POST["evaluator_id"]);
    $adminId     = $_SESSION["id"];


    $check = $conn->prepare("
        SELECT 1 FROM assignment
        WHERE project_id = ? AND evaluator_id = ?
    ");
    $check->bind_param("ii", $projectId, $evaluatorId);
    $check->execute();

    if ($check->get_result()->num_rows === 0) {


        $stmt = $conn->prepare("
            INSERT INTO assignment
                (project_id, evaluator_id, assigned_by, assigned_date)
            VALUES
                (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iii", $projectId, $evaluatorId, $adminId);
        $stmt->execute();


        $conn->query("
            UPDATE project
            SET status = 'Under Review'
            WHERE project_id = $projectId
        ");
    }

    header("Location: manage_projects.php");
    exit;
}


if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);

    $conn->query("DELETE FROM assignment WHERE project_id = $id");
    $conn->query("DELETE FROM project WHERE project_id = $id");

    header("Location: manage_projects.php");
    exit;
}


$projects = $conn->query("
    SELECT 
        p.project_id,
        p.title,
        p.status,
        p.created_at,
        s.name AS student_name,
        GROUP_CONCAT(e.name SEPARATOR ', ') AS evaluators
    FROM project p
    JOIN student s ON p.student_id = s.student_id
    LEFT JOIN assignment a ON p.project_id = a.project_id
    LEFT JOIN evaluator e ON a.evaluator_id = e.evaluator_id
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
");


$evaluators = $conn->query("
    SELECT evaluator_id, name, expertise
    FROM evaluator
    ORDER BY name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Projects</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-7xl mx-auto mt-10 bg-white p-6 rounded-xl shadow">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Project Management</h1>
        <a href="dashboard.php" class="text-blue-600 underline">
            ← Back to Admin Dashboard
        </a>
    </div>

    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="p-3">Project</th>
                <th class="p-3">Student</th>
                <th class="p-3">Evaluator(s)</th>
                <th class="p-3">Status</th>
                <th class="p-3">Assign Evaluator</th>
                <th class="p-3">Action</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($projects->num_rows === 0): ?>
            <tr>
                <td colspan="6" class="p-6 text-center text-gray-500">
                    No projects found.
                </td>
            </tr>
        <?php endif; ?>

        <?php while ($p = $projects->fetch_assoc()): ?>
            <tr class="border-b">
                <td class="p-3 font-medium">
                    <?= htmlspecialchars($p["title"]) ?>
                </td>

                <td class="p-3">
                    <?= htmlspecialchars($p["student_name"]) ?>
                </td>

                <td class="p-3 text-sm text-gray-700">
                    <?= $p["evaluators"] ?: "—" ?>
                </td>

                <td class="p-3">
                    <span class="px-3 py-1 rounded-full text-sm
                        <?= $p["status"] === "Completed"
                            ? "bg-green-100 text-green-700"
                            : ($p["status"] === "Under Review"
                                ? "bg-blue-100 text-blue-700"
                                : "bg-yellow-100 text-yellow-700") ?>">
                        <?= $p["status"] ?>
                    </span>
                </td>

                <!-- Assign Evaluator -->
                <td class="p-3">
                    <form method="POST" class="flex gap-2">
                        <input type="hidden" name="project_id" value="<?= $p["project_id"] ?>">

                        <select name="evaluator_id"
                                class="border rounded px-2 py-1 text-sm" required>
                            <option value="">Select</option>
                            <?php mysqli_data_seek($evaluators, 0); ?>
                            <?php while ($ev = $evaluators->fetch_assoc()): ?>
                                <option value="<?= $ev["evaluator_id"] ?>">
                                    <?= htmlspecialchars($ev["name"]) ?>
                                    (<?= htmlspecialchars($ev["expertise"] ?? "N/A") ?>)
                                </option>

                            <?php endwhile; ?>
                        </select>

                        <button name="assign"
                                class="px-3 py-1 bg-blue-600 text-white rounded text-sm">
                            Assign
                        </button>
                    </form>
                </td>

                <td class="p-3">
                    <a href="?delete=<?= $p["project_id"] ?>"
                       onclick="return confirm('Delete this project?')"
                       class="px-3 py-1 bg-red-600 text-white rounded text-sm">
                        Delete
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>

</body>
</html>
