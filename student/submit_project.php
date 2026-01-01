<?php
require_once __DIR__ . "/../config/auth_check.php";
allow_role("student");

require_once __DIR__ . "/../config/config.php";

$studentId   = $_SESSION["id"];
$studentName = $_SESSION["name"];

$error   = "";
$success = "";


function uploadFile($input, $folder, $allowedExt, $maxMB = 50) {
    if (!isset($_FILES[$input]) || $_FILES[$input]["error"] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$input];
    $ext  = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt)) {
        throw new Exception("Invalid file type for {$input}");
    }

    if ($file["size"] > $maxMB * 1024 * 1024) {
        throw new Exception("{$input} exceeds {$maxMB}MB limit");
    }

    $dir = __DIR__ . "/../uploads/{$folder}/";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9_.-]/", "_", $file["name"]);
    $destPath = $dir . $safeName;

    move_uploaded_file($file["tmp_name"], $destPath);

    return "uploads/{$folder}/" . $safeName;
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title       = trim($_POST["title"] ?? "");
    $category    = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $githubLink  = trim($_POST["github_link"] ?? "");

    if (
        $title === "" ||
        $category === "" ||
        $description === "" ||
        $githubLink === ""
    ) {
        $error = "All fields are compulsory.";
    } else {

        try {
            $abstractPdf = uploadFile("abstract_pdf", "abstract", ["pdf"], 10);
            $extendedDoc = uploadFile("extended_abstract", "extended", ["doc","docx"], 10);
            $videoMp4    = uploadFile("system_video", "video", ["mp4"], 50);
            $posterPptx  = uploadFile("poster", "poster", ["ppt","pptx"], 20);
            $slidesPptx  = uploadFile("slides", "slides", ["ppt","pptx"], 20);

            if (
                !$abstractPdf || !$extendedDoc ||
                !$videoMp4 || !$posterPptx || !$slidesPptx
            ) {
                throw new Exception("All project files must be uploaded.");
            }

            $stmt = $conn->prepare("
                INSERT INTO project
                    (student_id, title, category, description,
                     abstract_pdf, extended_abstract_docx,
                     system_video_mp4, poster_pptx, slides_pptx,
                     github_link, status, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
            ");

            $stmt->bind_param(
                "isssssssss",
                $studentId,
                $title,
                $category,
                $description,
                $abstractPdf,
                $extendedDoc,
                $videoMp4,
                $posterPptx,
                $slidesPptx,
                $githubLink
            );

            $stmt->execute();

            $success = "Project submitted successfully.";
            $_POST = [];

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submit Project</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

<div class="flex">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow h-screen fixed px-6 py-6">
    <h1 class="text-xl font-semibold mb-6">Student Panel</h1>
    <p class="text-gray-500 mb-8">Project Evaluation System</p>

    <nav class="space-y-2">
        <a href="dashboard.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Dashboard</a>
        <a href="submit_project.php" class="block px-4 py-2 bg-blue-600 text-white rounded-lg">
            Submit Project
        </a>
        <a href="status.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">Project Status</a>
        <a href="view_feedback.php" class="block px-4 py-2 hover:bg-gray-200 rounded-lg">View Feedback</a>
    </nav>

    <a href="../logout.php" class="absolute bottom-6 left-6 text-gray-500">
        Sign Out
    </a>
</aside>

<!-- Main -->
<main class="flex-1 ml-64 p-10 max-w-4xl">

<h1 class="text-3xl font-semibold mb-2">Submit Project</h1>
<p class="text-gray-600 mb-6">Upload your project for evaluation.</p>

<?php if ($error): ?>
<div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= $success ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="space-y-5">

<!-- Project Title -->
<div>
    <label class="font-medium">Project Title *</label>
    <input type="text" name="title" required
           class="w-full border rounded-lg p-2">
</div>

<!-- Focus Expertise Area -->
<div>
    <label class="font-medium">Focus Expertise Area *</label>
    <select name="category" required
            class="w-full border rounded-lg p-2 bg-white">
        <option value="">-- Select Focus Area --</option>
        <option value="Artificial Intelligence">Artificial Intelligence</option>
        <option value="Web Development">Web Development</option>
        <option value="Mobile Application">Mobile Application</option>
        <option value="Networking">Networking</option>
        <option value="Cyber Security">Cyber Security</option>
        <option value="Multimedia">Multimedia</option>
        <option value="Data Science">Data Science</option>
    </select>
</div>

<!-- File Upload Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Abstract -->
    <div class="border rounded-xl p-4 bg-white">
        <label class="font-semibold">Full Report</label>
        <input type="file" name="abstract_pdf" accept=".pdf" required>
    </div>

    <div class="border rounded-xl p-4 bg-white">
        <label class="font-semibold">Extended Abstract (DOCX)</label>
        <input type="file" name="extended_abstract" accept=".doc,.docx" required>
    </div>

    <div class="border rounded-xl p-4 bg-white">
        <label class="font-semibold">System Video (MP4)</label>
        <input type="file" name="system_video" accept=".mp4" required>
    </div>

    <div class="border rounded-xl p-4 bg-white">
        <label class="font-semibold">Poster (PPTX)</label>
        <input type="file" name="poster" accept=".ppt,.pptx" required>
    </div>

    <div class="border rounded-xl p-4 bg-white">
        <label class="font-semibold">Presentation Slides</label>
        <input type="file" name="slides" accept=".ppt,.pptx" required>
    </div>

    <div class="border rounded-xl p-4 bg-white">
        <label class="font-semibold">GitHub Repository</label>
        <input type="url" name="github_link" required
               placeholder="https://github.com/username/project"
               class="w-full border rounded px-3 py-2">
    </div>
</div>

<!-- Description -->
<div>
    <label class="font-medium">Project Description *</label>
    <textarea name="description" rows="5" required
              class="w-full border rounded-lg p-2"></textarea>
</div>

<div class="flex gap-4">
    <a href="dashboard.php" class="px-6 py-2 border rounded-lg">
        Cancel
    </a>
    <button class="px-6 py-2 bg-blue-600 text-white rounded-lg">
        Submit Project
    </button>
</div>

</form>

</main>
</div>

</body>
</html>
