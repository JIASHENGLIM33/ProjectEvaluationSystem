<?php
/*************************************************
 * login.php  (FINAL – LOGIN + REGISTER ENTRY)
 *************************************************/

session_start(); // ✅ 只 start，不 destroy
require_once __DIR__ . "/config/config.php";

$error = "";
$success = "";

/* =========================
   显示注册成功提示
========================= */
if (isset($_GET["registered"])) {
    $success = "Registration successful. Please login.";
}

/* =========================
   处理登录请求
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Please enter both email and password.";
    } else {

        $user = null;

        /* ---------- 查 Admin ---------- */
        $stmt = $conn->prepare("
            SELECT admin_id AS id, name, email, password, 'admin' AS role
            FROM administrator
            WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        /* ---------- 查 Evaluator ---------- */
        if (!$user) {
            $stmt = $conn->prepare("
                SELECT evaluator_id AS id, name, email, password, 'evaluator' AS role
                FROM evaluator
                WHERE email = ?
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        }

        /* ---------- 查 Student ---------- */
        if (!$user) {
            $stmt = $conn->prepare("
                SELECT student_id AS id, name, email, password, 'student' AS role
                FROM student
                WHERE email = ?
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        }

        /* ---------- 验证 ---------- */
        if (!$user) {
            $error = "User not found.";
        } else {

            $passwordCorrect =
                password_verify($password, $user["password"]) ||
                $password === $user["password"]; // 兼容旧明文数据

            if (!$passwordCorrect) {
                $error = "Incorrect password.";
            } else {

                /* =========================
                   登录成功
                ========================= */
                session_regenerate_id(true);

                $_SESSION["id"]    = $user["id"];
                $_SESSION["name"]  = $user["name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"]  = $user["role"];

                header("Location: {$user['role']}/dashboard.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Project Evaluation System</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100 p-4">

<div class="w-full max-w-md">
    <div class="bg-white shadow-lg rounded-xl p-8">

        <h2 class="text-2xl text-center font-bold text-gray-900">
            Project Evaluation System
        </h2>
        <p class="text-center text-gray-600 mt-2 mb-6">
            Login using your registered email
        </p>

        <?php if ($success): ?>
            <div class="mb-4 p-3 text-green-700 bg-green-100 border border-green-300 rounded">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 p-3 text-red-700 bg-red-100 border border-red-300 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- ================= LOGIN FORM ================= -->
        <form method="POST" class="space-y-5">

            <div>
                <label class="block text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required
                       placeholder="example@email.com"
                       class="w-full p-3 border rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                       placeholder="••••••••"
                       class="w-full p-3 border rounded-lg bg-gray-50">
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold">
                Sign In
            </button>
        </form>

        <!-- ================= REGISTER ENTRY ================= -->
        <div class="mt-6 text-center text-sm text-gray-600">
            Don’t have an account?
            <a href="register.php"
               class="text-blue-600 font-medium hover:underline">
                Register as Student / Evaluator
            </a>
        </div>

        <p class="text-center text-xs text-gray-500 mt-6">
            © Project Evaluation Management System
        </p>

    </div>
</div>

</body>
</html>
