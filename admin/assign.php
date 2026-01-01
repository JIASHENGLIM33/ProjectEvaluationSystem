<?php
require_once "../config/config.php";

$pid = $_GET["pid"];
$eid = $_GET["eid"];

$conn->query("UPDATE projects SET evaluator_id=$eid WHERE id=$pid");

header("Location: auto_assign_dashboard.php");
exit;
?>
