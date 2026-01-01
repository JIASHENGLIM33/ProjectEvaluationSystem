<?php
require_once "../config/config.php";
require_once "../config/auth_check.php";

allow_role("admin");

$id = $_GET["id"] ?? null;
if (!$id) die("Invalid request.");

$stmt = $conn->prepare("
    DELETE FROM student WHERE student_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: manage_students.php");
exit;
