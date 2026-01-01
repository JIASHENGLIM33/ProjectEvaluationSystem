<?php


require_once __DIR__ . "/../config/auth_check.php";
allow_role("admin");

require_once __DIR__ . "/../config/config.php";


$projects = $conn->query("
    SELECT project_id
    FROM project
    WHERE project_id NOT IN (
        SELECT project_id FROM assignment
    )
");

while ($p = $projects->fetch_assoc()) {


    $_POST["project_id"] = $p["project_id"];
    $_POST["evaluator_id"] = 0;

    $_SERVER["REQUEST_METHOD"] = "POST";

    // 直接复用 Fuzzy + AI 逻辑
    require __DIR__ . "/auto_assign.php";
}


header("Location: assign_project.php?auto=success");
exit;
