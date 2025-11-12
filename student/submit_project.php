<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

// ↪ File upload + DB insert
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST["title"]);
    $description = mysqli_real_escape_string($conn, $_POST["description"]);
    $student_id = $_SESSION["user_id"];

    // Ensure upload folder exists
    $targetDir = "../uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (!empty($_FILES["project_file"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["project_file"]["name"]);
        $filePath = $targetDir . $fileName;
        $allowed = ['pdf', 'zip'];

        $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($fileType, $allowed)) {

            if (move_uploaded_file($_FILES["project_file"]["tmp_name"], $filePath)) {
                // Insert DB
                $sql = "INSERT INTO projects (student_id, project_title, description, file_path, status, submitted_date)
                        VALUES ('$student_id', '$title', '$description', '$fileName', 'Submitted', NOW())";

                if (mysqli_query($conn, $sql)) {
                    $success = "✅ Project submitted successfully!";
                } else {
                    $error = "❌ Database save failed!";
                }

            } else {
                $error = "❌ Upload failed!";
            }

        } else {
            $error = "❌ Only PDF or ZIP allowed.";
        }

    } else {
        $error = "⚠ Please upload a file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submit Project | Student</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="dashboard-container">

    <div class="sidebar">
        <h2>Student</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="submit_project.php" class="active">📝 Submit Project</a>
        <a href="view_feedback.php">⭐ View Feedback</a>
        <a href="../logout.php" class="logout-btn">Logout →</a>
    </div>

    <div class="main-content">

        <h2>Submit Project</h2>
        <p>Upload your project details & required documents</p>

        <div class="form-container">
    <div class="form-card">

        <?php if (!empty($success)): ?>
            <p class="alert success"><?php echo $success; ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p class="alert error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="project-form">
            <label>Project Title*</label>
            <input type="text" name="title" required placeholder="Enter project title">

            <label>Description (Optional)</label>
            <textarea name="description" rows="4" placeholder="Describe your project..."></textarea>

            <label>Upload PDF or ZIP *</label>
            <input type="file" name="project_file" accept=".pdf,.zip" required>

            <button type="submit" class="btn-primary full">Submit Project</button>
        </form>

    </div>
</div>

    </div>

</div>

</body>
</html>
