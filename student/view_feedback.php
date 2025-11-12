<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../login.php");
    exit();
}

$studentId = $_SESSION["user_id"];

$sql = "
    SELECT p.project_title, e.feedback, e.score, e.evaluator_id, e.submitted_at
    FROM evaluations e
    JOIN projects p ON e.project_id = p.id
    WHERE p.student_id = '$studentId'
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Feedback | Student</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Student</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="submit_project.php">📝 Submit Project</a>
        <a href="view_feedback.php" class="active">⭐ View Feedback</a>
        <a href="../logout.php" class="logout-btn">Logout →</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2>View Evaluation Feedback</h2>
        <p>Your project evaluation & reviewer comments</p>

        <div class="feedback-container">

        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            
            <div class="feedback-card">
                <h3><?php echo $row["project_title"]; ?></h3>

                <div class="feedback-score">
                    ⭐ Score: <strong><?php echo $row["score"]; ?>/100</strong>
                </div>

                <p><strong>Reviewer:</strong> <?php echo $row["evaluator_id"]; ?></p>
                <p><strong>Feedback Date:</strong> <?php echo $row["submitted_at"]; ?></p>

                <div class="feedback-box">
                    <?php echo nl2br($row["feedback"]); ?>
                </div>
            </div>

            <?php } ?>

        <?php } else { ?>
            <p class="no-feedback">❌ No feedback yet. Your project is still under review.</p>
        <?php } ?>

        </div>
    </div>
</div>

</body>
</html>

