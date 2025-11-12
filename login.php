<?php
session_start();
require_once "db_connect.php";

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = strtoupper(trim($_POST["username"])); // 转大写避免小写问题
    $password = md5($_POST["password"]);

    // ✅ 身份识别逻辑
    $firstChar = strtoupper($username[0]);  // 取得第一个字符 B / D / E / A

    if ($firstChar == "B" || $firstChar == "D") {
        $role = "student";    // B = Bachelor, D = Diploma
    } elseif ($firstChar == "E") {
        $role = "evaluator";
    } elseif ($firstChar == "A") {
        $role = "admin";
    } else {
        $error_message = "❌ Invalid User Type!";
    }

    // ✅ Database login check (no role check in DB)
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {

        $_SESSION["user_id"] = $username;
        $_SESSION["role"] = $role;

        if ($role === "student") {
            header("Location: student/dashboard.php");
        } elseif ($role === "evaluator") {
            header("Location: evaluator/dashboard.php");
        } elseif ($role === "admin") {
            header("Location: admin/dashboard.php");
        }
        exit();

    } else {
        $error_message = "❌ Username or Password incorrect!";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Project Evaluation System | 登录</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <h2>Project Evaluation System</h2>
        <p class="subtitle">Login to continue</p>

        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter your username">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn-login">Login</button>

            <?php if (!empty($error_message)): ?>
                <p class="error"><?php echo $error_message ?></p>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
