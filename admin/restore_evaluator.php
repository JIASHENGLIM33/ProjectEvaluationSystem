<?php
/*************************************************
 * admin/restore_evaluator.php
 * FINAL – Restore Inactive Evaluator
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

$evaluator = $check->get_result()->fetch_assoc();

if (!$evaluator) {
    header("Location: manage_evaluators.php?error=notfound");
    exit;
}

/* =========================
   3. 如果已经是 Active
========================= */
if ($evaluator["status"] === "Active") {
    header("Location: manage_evaluators.php?info=already_active");
    exit;
}

/* =========================
   4. 恢复 evaluator
========================= */
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

/* =========================
   5. 返回列表
========================= */
header("Location: manage_evaluators.php?restored=1");
exit;
