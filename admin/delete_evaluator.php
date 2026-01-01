<?php
/*************************************************
 * admin/delete_evaluator.php
 * FINAL – Soft Deactivate Evaluator (Safe Version)
 *************************************************/

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/auth_check.php";

allow_role("admin");

/* =========================
   1. 参数校验
========================= */
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: manage_evaluators.php?error=invalid");
    exit;
}

$evaluatorId = intval($_GET["id"]);

/* =========================
   2. 检查 evaluator 是否存在
========================= */
$check = $conn->prepare("
    SELECT evaluator_id, status
    FROM evaluator
    WHERE evaluator_id = ?
");
$check->bind_param("i", $evaluatorId);
$check->execute();

$result = $check->get_result()->fetch_assoc();

if (!$result) {
    // evaluator 不存在
    header("Location: manage_evaluators.php?error=notfound");
    exit;
}

/* =========================
   3. 如果已是 Inactive，直接返回
========================= */
if ($result["status"] === "Inactive") {
    header("Location: manage_evaluators.php?info=already_inactive");
    exit;
}


$stmt = $conn->prepare("
    UPDATE evaluator
    SET status = 'Inactive'
    WHERE evaluator_id = ?
");
$stmt->bind_param("i", $evaluatorId);

if (!$stmt->execute()) {

    header("Location: manage_evaluators.php?error=update_failed");
    exit;
}


header("Location: manage_evaluators.php?disabled=1");
exit;
