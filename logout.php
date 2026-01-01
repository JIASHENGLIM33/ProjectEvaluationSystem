<?php
/*************************************************
 * logout.php
 * Project Evaluation System
 *************************************************/

session_start();
session_unset();
/* 清空 session 数据 */
$_SESSION = [];

/* 销毁 session */
session_destroy();

/* 清除 session cookie（保险做法） */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* 跳回登录页（绝对路径，最安全） */
header("Location: /pems/login.php");
exit();
