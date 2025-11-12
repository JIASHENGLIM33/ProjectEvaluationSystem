<?php
session_start();

session_unset();

session_destroy();


header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

header("Location: login.php");
exit();
?>
