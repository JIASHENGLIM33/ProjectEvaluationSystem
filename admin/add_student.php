<?php
require_once "../config/config.php";
require_once "../config/auth_check.php";

allow_role("admin");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name    = trim($_POST["name"]);
    $email   = trim($_POST["email"]);
    $program = trim($_POST["program"]);
    $intake  = trim($_POST["intake"]);

    if ($name === "" || $email === "" || $program === "" || $intake === "") {
        $error = "All fields are required.";
    } else {


        $rawPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashedPwd   = password_hash($rawPassword, PASSWORD_DEFAULT);


        $stmt = $conn->prepare("
            INSERT INTO student (name, email, password, program, intake, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("sssss", $name, $email, $hashedPwd, $program, $intake);

        if ($stmt->execute()) {


            $log = $conn->prepare("
                INSERT INTO student_credentials (student_name, email, raw_password, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $log->bind_param("sss", $name, $email, $rawPassword);
            $log->execute();

            $success = "Student created successfully.";
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
<title>Add Student</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-xl mx-auto mt-12 bg-white p-6 rounded shadow">

    <h1 class="text-2xl font-bold mb-4">Add Student</h1>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <input name="name" placeholder="Student Name"
               class="w-full p-3 border rounded" required>

        <input name="email" type="email" placeholder="Campus Email"
               class="w-full p-3 border rounded" required>

        <input name="program" placeholder="Program"
               class="w-full p-3 border rounded" required>

        <input name="intake" placeholder="Intake (e.g. 2024/09)"
               class="w-full p-3 border rounded" required>

        <div class="flex justify-end gap-3">
            <a href="manage_students.php" class="px-4 py-2 border rounded">
                Cancel
            </a>
            <button class="px-4 py-2 bg-blue-600 text-white rounded">
                Create Student
            </button>
        </div>

    </form>

</div>

</body>
</html>
