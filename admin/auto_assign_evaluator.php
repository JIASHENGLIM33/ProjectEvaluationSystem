<?php
include '../db.php';

function getBestEvaluator($projectKeywords) {
    global $conn;

    // 取出 evaluator 用户
    $sql = "SELECT id, username, specialization, workload FROM users WHERE role='evaluator'";
    $evaluators = mysqli_query($conn, $sql);

    $bestEvaluator = null;
    $bestScore = -1;

    while ($eva = mysqli_fetch_assoc($evaluators)) {
        $score = 0;

        // 关键词匹配评分
        foreach($projectKeywords as $key) {
            if (stripos($eva['specialization'], $key) !== false) {
                $score += 5;   // 匹配越多分越高
            }
        }

        // 任务工作量越少分越高
        $score += max(0, 5 - intval($eva['workload']));

        // 记录最佳评审员
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestEvaluator = $eva['id'];
        }
    }

    return $bestEvaluator;
}
?>
