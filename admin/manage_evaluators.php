<?php
require_once "../config/config.php";
require_once "../config/auth_check.php";

allow_role("admin");


$result = $conn->query("
    SELECT evaluator_id, name, email, expertise, status, created_at
    FROM evaluator
    ORDER BY status ASC, created_at DESC
");


function e($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Evaluator Management | PEMS</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-6xl mx-auto mt-12 bg-white p-6 rounded-xl shadow">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Evaluator Management</h1>
        <p class="text-gray-600 text-sm">
            Manage evaluator accounts (soft-deactivation enabled)
        </p>
    </div>

    <!-- Status Messages -->
    <?php if (isset($_GET['disabled'])): ?>
        <div class="mb-4 p-3 bg-yellow-100 text-yellow-800 rounded">
            Evaluator has been deactivated successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['restored'])): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            Evaluator has been restored to active status.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['info']) && $_GET['info'] === 'already_inactive'): ?>
        <div class="mb-4 p-3 bg-blue-100 text-blue-800 rounded">
            This evaluator is already inactive.
        </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full border border-gray-200 rounded-lg">
            <thead class="bg-gray-100 text-left text-gray-700">
                <tr>
                    <th class="p-3">Name</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Expertise</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Registered At</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="6" class="p-6 text-center text-gray-500">
                        No evaluator accounts found.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($ev = $result->fetch_assoc()): ?>
                    <tr class="border-t hover:bg-gray-50">

                        <!-- Name -->
                        <td class="p-3 font-medium">
                            <?= e($ev["name"]) ?>
                        </td>

                        <!-- Email -->
                        <td class="p-3">
                            <?= e($ev["email"]) ?>
                        </td>

                        <!-- Expertise -->
                        <td class="p-3 text-sm text-gray-700">
                            <?= e($ev["expertise"] ?: "—") ?>
                        </td>

                        <!-- Status Badge -->
                        <td class="p-3">
                            <?php if ($ev["status"] === "Active"): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-700">
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-200 text-gray-600">
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>


                        <td class="p-3 text-sm text-gray-600">
                            <?= date("d M Y", strtotime($ev["created_at"])) ?>
                        </td>

                        
                        <td class="p-3">
                            <?php if ($ev["status"] === "Active"): ?>

  
                                <a href="delete_evaluator.php?id=<?= $ev["evaluator_id"] ?>"
                                   onclick="return confirm(
                                       'Deactivate this evaluator?\n\n' +
                                       'They will no longer receive new project assignments.'
                                   )"
                                   class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                                    Deactivate
                                </a>

                            <?php else: ?>


                                <a href="restore_evaluator.php?id=<?= $ev["evaluator_id"] ?>"
                                   onclick="return confirm(
                                       'Restore this evaluator to Active status?\n\n' +
                                       'They will be eligible for future assignments.'
                                   )"
                                   class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                    Restore
                                </a>

                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>


    <div class="mt-6">
        <a href="dashboard.php" class="text-blue-600 underline">
            ← Back to Admin Dashboard
        </a>
    </div>

</div>

</body>
</html>
