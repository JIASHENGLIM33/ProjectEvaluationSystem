<?php
require_once "../config/config.php";
require_once "../config/auth_check.php";

allow_role("admin");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name      = trim($_POST["name"]);
    $email     = trim($_POST["email"]);
    $expertise = trim($_POST["expertise"]);

    if ($name === "" || $email === "" || $expertise === "") {
        $error = "All fields are required.";
    } else {

        // ✅ 自动生成初始密码（8 位）
        $rawPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashedPwd   = password_hash($rawPassword, PASSWORD_DEFAULT);

        // 1️⃣ 插入 evaluator 表（hash 密码）
        $stmt = $conn->prepare("
            INSERT INTO evaluator (name, email, expertise, password, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssss", $name, $email, $expertise, $hashedPwd);

        if ($stmt->execute()) {

            // 2️⃣ 同时存一份账号清单（明文密码，仅 Admin 用）
            $log = $conn->prepare("
                INSERT INTO evaluator_credentials
                    (evaluator_name, email, raw_password, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $log->bind_param("sss", $name, $email, $rawPassword);
            $log->execute();

            $success = "Evaluator created successfully.";
        } else {
            $error = "Email already exists or database error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Evaluator</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-xl mx-auto mt-12 bg-white p-6 rounded shadow">

    <h1 class="text-2xl font-bold mb-4">Add Evaluator</h1>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <input name="name"
               placeholder="Evaluator Name"
               class="w-full p-3 border rounded"
               required>

        <input name="email"
               type="email"
               placeholder="Email Address"
               class="w-full p-3 border rounded"
               required>

        <input name="expertise"
               placeholder="Expertise (e.g. AI, Web, Networking)"
               class="w-full p-3 border rounded"
               required>

        <div class="flex justify-end gap-3">
            <a href="manage_evaluators.php"
               class="px-4 py-2 border rounded">
                Cancel
            </a>
            <button class="px-4 py-2 bg-blue-600 text-white rounded">
                Create Evaluator
            </button>
        </div>

    </form>

</div>

</body>
</html>
