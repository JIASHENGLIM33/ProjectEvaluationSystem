<?php
session_start();

function allow_role(string $role)
{
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== $role) {
        header("Location: ../login.php");
        exit;
    }
}
