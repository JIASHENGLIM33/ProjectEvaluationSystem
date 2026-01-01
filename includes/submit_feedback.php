<?php
session_start();
require_once __DIR__ . "/config/config.php";

// Ensure evaluator login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'evaluator') {
    header("Location: ../login.php");
    exit();
}

$evaluator_id = $_SESSION['user_id'];

$project_id = $_POST['project_id'];
$technical = $_POST['technical'];
$creativity = $_POST['creativity'];
$documentation = $_POST['documentation'];
$codeQuality = $_POST['codeQuality'];
$overall = $_POST['overall'];
$strengths = $_POST['strengths'];
$improvements = $_POST['improvements'];
$comments = $_POST['comments'];


$p = $conn->prepare("SELECT assigned_evaluators FROM projects WHERE id = ?");
$p->bind_param("i", $project_id);
$p->execute();
$project = $p->get_result()->fetch_assoc();

if (!$project) {
    die("Project does not exist.");
}

$assigned = explode(",", $project['assigned_evaluators']);

if (!in_array($evaluator_id, $assigned)) {
    die("You are not assigned to this project.");
}

$check = $conn->prepare("SELECT id FROM feedback WHERE evaluator_id = ? AND project_id = ?");
$check->bind_param("ii", $evaluator_id, $project_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    die("You already submitted feedback.");
}


$insert = $conn->prepare("
    INSERT INTO feedback (project_id, evaluator_id, technical_score, creativity_score, documentation_score, code_quality_score, overall_score, strengths, improvement_suggestions, overall_comments)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$insert->bind_param("iiiiiiisss",
    $project_id, $evaluator_id,
    $technical, $creativity, $documentation, $codeQuality, $overall,
    $strengths, $improvements, $comments
);
$insert->execute();


$done = $conn->prepare("SELECT COUNT(*) FROM feedback WHERE project_id = ?");
$done->bind_param("i", $project_id);
$done->execute();
$count_done = $done->get_result()->fetch_row()[0];

if ($count_done >= count($assigned)) {

    $conn->query("UPDATE projects SET status = 'Completed' WHERE id = $project_id");
}

header("Location: ../evaluator/project_list.php?success=1");
exit();
