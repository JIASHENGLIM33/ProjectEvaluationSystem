<?php
session_start();
include '../config/db.php';

if ($_SESSION['role'] !== 'evaluator') {
    header("Location: ../index.php");
    exit();
}

$evaluator_id = $_SESSION['user_id'];

// 获取 GET project_id
if (!isset($_GET['project_id'])) {
    die("❌ Error: Missing project ID.");
}
$project_id = $_GET['project_id'];

// 获取项目信息
$projectQuery = mysqli_query($conn, "SELECT p.*, u.username 
                                     FROM projects p
                                     JOIN users u ON p.student_id = u.id
                                     WHERE p.id = '$project_id'");
$project = mysqli_fetch_assoc($projectQuery);

// 提交评分
if (isset($_POST['submitFeedback'])) {
    $score = $_POST['score'];
    $comments = $_POST['comments'];

    $insert = mysqli_query($conn, "INSERT INTO evaluations (project_id, evaluator_id, score, comments)
                                   VALUES ('$project_id', '$evaluator_id', '$score', '$comments')");
    if ($insert) {
        echo "<script>alert('✅ Feedback Submitted Successfully!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('❌ Error submitting feedback');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluator Feedback</title>

    <link rel="stylesheet" href="../assets/css/evaluator.css">
</head>
<body>

<div class="container">
    <h2>Evaluator Feedback</h2>

    <div class="project-box">
        <h3>Project Title: <?php echo $project['title']; ?></h3>
        <p><b>Submitted By:</b> <?php echo $project['username']; ?></p>
        <p><b>Description:</b><br><?php echo $project['description']; ?></p>
    </div>

    <form action="" method="POST" class="feedback-form">
        <label>Score (1 - 100)</label>
        <input type="number" name="score" min="1" max="100" required>

        <label>Feedback / Comments</label>
        <textarea name="comments" placeholder="Write your feedback here..." required></textarea>

        <button type="submit" name="submitFeedback" class="btn-submit">Submit Feedback</button>
        <a href="dashboard.php" class="btn-cancel">Cancel</a>
    </form>
</div>

</body>
</html>
