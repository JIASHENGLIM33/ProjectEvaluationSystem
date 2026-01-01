<?php
require_once "../config/config.php";
require_once "../config/auth_check.php";


allow_role("admin");


$evaluatorId = intval($_POST["evaluator_id"] ?? 0);
$name        = trim($_POST["name"] ?? "");
$email       = trim($_POST["email"] ?? "");
$expertise   = trim($_POST["expertise"] ?? "");

if ($evaluatorId <= 0 || $name === "" || $email === "") {
    header("Location: manage_evaluators.php?error=invalid");
    exit;
}


$stmt = $conn->prepare("
    UPDATE evaluator
    SET name = ?, email = ?, expertise = ?
    WHERE evaluator_id = ?
");

$stmt->bind_param(
    "sssi",
    $name,
    $email,
    $expertise,
    $evaluatorId
);

if ($stmt->execute()) {
    header("Location: manage_evaluators.php?updated=1");
    exit;
} else {
    header("Location: manage_evaluators.php?error=db");
    exit;
}
