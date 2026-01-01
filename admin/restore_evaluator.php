<?php


require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/auth_check.php";

allow_role("admin");


if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: manage_evaluators.php?error=invalid");
    exit;
}

$evaluatorId = intval($_GET["id"]);


$check = $conn->prepare("
    SELECT evaluator_id, status
    FROM evaluator
    WHERE evaluator_id = ?
");
$check->bind_param("i", $evaluatorId);
$check->execute();

$evaluator = $check->get_result()->fetch_assoc();

if (!$evaluator) {
    header("Location: manage_evaluators.php?error=notfound");
    exit;
}


if ($evaluator["status"] === "Active") {
    header("Location: manage_evaluators.php?info=already_active");
    exit;
}


$stmt = $conn->prepare("
    UPDATE evaluator
    SET status = 'Active'
    WHERE evaluator_id = ?
");
$stmt->bind_param("i", $evaluatorId);

if (!$stmt->execute()) {
    header("Location: manage_evaluators.php?error=restore_failed");
    exit;
}


header("Location: manage_evaluators.php?restored=1");
exit;
