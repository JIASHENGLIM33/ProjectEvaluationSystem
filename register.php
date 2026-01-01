<?php
/*************************************************
 * register.php
 * STUDENT / EVALUATOR SELF REGISTRATION (FINAL)
 *************************************************/

require_once __DIR__ . "/config/config.php";

$error = "";

/* =========================
   Skill Options (固定列表)
========================= */
$skillOptions = [
    "AI / Machine Learning",
    "Web Development",
    "Mobile Application",
    "Networking",
    "Cybersecurity",
    "Data Science",
    "Cloud Computing",
    "Software Engineering"
];

/* =========================
   Handle Registration
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name     = trim($_POST["name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $role     = $_POST["role"] ?? "";

    // Evaluator skills (array)
    $expertiseArr = $_POST["expertise"] ?? [];

    /* ---------- Basic Validation ---------- */
    if ($name === "" || $email === "" || $password === "" || $role === "") {
        $error = "All fields are required.";
    }
    elseif (!in_array($role, ["student", "evaluator"])) {
        $error = "Invalid role selected.";
    }
    elseif ($role === "evaluator" && empty($expertiseArr)) {
        $error = "Evaluator must select at least one expertise area.";
    }
    else {

        /* ---------- Email must be unique across system ---------- */
        $exists = false;

        $tables = [
            "administrator" => "admin_id",
            "evaluator"     => "evaluator_id",
            "student"       => "student_id"
        ];

        foreach ($tables as $table => $pk) {
            $stmt = $conn->prepare("SELECT $pk FROM $table WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            $error = "This email is already registered.";
        }
        else {

            /* ---------- Create Account ---------- */
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if ($role === "student") {

                $stmt = $conn->prepare("
                    INSERT INTO student (name, email, password, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->bind_param("sss", $name, $email, $hashedPassword);

            } else {
                // evaluator
                $expertise = implode(",", array_map("trim", $expertiseArr));

                $stmt = $conn->prepare("
                    INSERT INTO evaluator (name, email, password, expertise, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("ssss", $name, $email, $hashedPassword, $expertise);
            }

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register | Project Evaluation System</title>
<script src="https://cdn.tailwindcss.com"></script>

<script>
function toggleExpertise() {
    const role = document.getElementById("role").value;
    const box  = document.getElementById("expertiseBox");

    if (role === "evaluator") {
        box.classList.remove("hidden");
    } else {
        box.classList.add("hidden");
        document.querySelectorAll(".expertise-checkbox").forEach(cb => cb.checked = false);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelector("form").addEventListener("submit", function (e) {
        const role = document.getElementById("role").value;

        if (role === "evaluator") {
            const checked = document.querySelectorAll(".expertise-checkbox:checked");
            if (checked.length === 0) {
                e.preventDefault();
                alert("Please select at least one expertise area.");
            }
        }
    });
});
</script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100 p-4">

<div class="w-full max-w-md">
    <div class="bg-white shadow-lg rounded-xl p-8">

        <h2 class="text-2xl text-center font-bold">Create Account</h2>
        <p class="text-center text-gray-600 mt-2 mb-6">
            Student / Evaluator Registration
        </p>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">

            <div>
                <label class="block mb-1">Full Name</label>
                <input type="text" name="name" required
                       class="w-full border rounded p-3">
            </div>

            <div>
                <label class="block mb-1">Email</label>
                <input type="email" name="email" required
                       class="w-full border rounded p-3">
            </div>

            <div>
                <label class="block mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full border rounded p-3">
            </div>

            <div>
                <label class="block mb-1">Register As</label>
                <select name="role" id="role" required
                        onchange="toggleExpertise()"
                        class="w-full border rounded p-3">
                    <option value="">-- Select Role --</option>
                    <option value="student">Student</option>
                    <option value="evaluator">Evaluator</option>
                </select>
            </div>

            <!-- Evaluator Expertise -->
            <div id="expertiseBox" class="hidden">
                <label class="block mb-2 font-medium">
                    Evaluator Expertise <span class="text-red-500">*</span>
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($skillOptions as $skill): ?>
                        <label class="flex items-center gap-2 border rounded p-3 cursor-pointer">
                            <input
                                type="checkbox"
                                name="expertise[]"
                                value="<?= htmlspecialchars($skill) ?>"
                                class="expertise-checkbox"
                            >
                            <span><?= htmlspecialchars($skill) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p class="text-sm text-gray-500 mt-2">
                    Select at least one expertise area.
                </p>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded font-semibold">
                Register
            </button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
            Already have an account?
            <a href="login.php" class="text-blue-600 underline">
                Back to Login
            </a>
        </p>

    </div>
</div>

</body>
</html>
