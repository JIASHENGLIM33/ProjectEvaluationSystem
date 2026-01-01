<?php


require_once __DIR__ . "/../config/auth_check.php";
allow_role("admin");

require_once __DIR__ . "/../config/config.php";

$adminId = $_SESSION["id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: assign_project.php");
    exit;
}


$projectId   = intval($_POST["project_id"] ?? 0);
$evaluatorId = intval($_POST["evaluator_id"] ?? 0); // optional

if ($projectId <= 0) {
    die("Invalid project ID.");
}


$check = $conn->prepare("
    SELECT assignment_id
    FROM assignment
    WHERE project_id = ?
");
$check->bind_param("i", $projectId);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    die("This project has already been assigned.");
}


if ($evaluatorId > 0) {

    $stmt = $conn->prepare("
        INSERT INTO assignment
            (project_id, evaluator_id, weight, assigned_by, assigned_date)
        VALUES (?, ?, 1.00, ?, NOW())
    ");
    $stmt->bind_param("iii", $projectId, $evaluatorId, $adminId);

    if (!$stmt->execute()) {
        die("Failed to assign evaluator.");
    }

}

else {


    function fuzzyLow($x) {
        return ($x <= 0) ? 1 : (($x >= 40) ? 0 : (1 - $x / 40));
    }

    function fuzzyMedium($x) {
        if ($x <= 30 || $x >= 70) return 0;
        return 1 - abs($x - 50) / 20;
    }

    function fuzzyHigh($x) {
        return ($x <= 60) ? 0 : (($x >= 100) ? 1 : (($x - 60) / 40));
    }

    function workloadLow($n) {
        return ($n <= 1) ? 1 : (($n >= 3) ? 0 : (3 - $n) / 2);
    }

    function workloadHigh($n) {
        return ($n >= 4) ? 1 : 0;
    }

    function fuzzyInference($expertiseScore, $workload) {

        $E_low  = fuzzyLow($expertiseScore);
        $E_med  = fuzzyMedium($expertiseScore);
        $E_high = fuzzyHigh($expertiseScore);

        $W_low  = workloadLow($workload);
        $W_high = workloadHigh($workload);

        $excellent = min($E_high, $W_low);
        $good      = max(min($E_high, 1 - $W_high), min($E_med, $W_low));
        $poor      = max($E_low, $W_high);

        $num = ($excellent * 90) + ($good * 70) + ($poor * 40);
        $den = $excellent + $good + $poor;

        return $den == 0 ? 0 : round($num / $den, 2);
    }

    function aiFinalScore($fuzzyScore, $workload) {
        $workloadPenalty = max(0, 100 - ($workload * 20));
        return round((0.7 * $fuzzyScore) + (0.3 * $workloadPenalty), 2);
    }


    $p = $conn->query("
        SELECT category
        FROM project
        WHERE project_id = $projectId
    ")->fetch_assoc();

    if (!$p) {
        die("Project not found.");
    }

    $category = strtolower($p["category"]);


    $evs = $conn->query("
        SELECT 
            e.evaluator_id,
            e.expertise,
            COUNT(a.assignment_id) AS workload
        FROM evaluator e
LEFT JOIN assignment a ON e.evaluator_id = a.evaluator_id
WHERE e.status = 'Active'
GROUP BY e.evaluator_id

    ");

    $ranked = [];

    while ($ev = $evs->fetch_assoc()) {

        $expertise = strtolower($ev["expertise"] ?? "");
        $list = array_filter(array_map("trim", explode(",", $expertise)));

        // expertise match score
        if (in_array($category, $list)) {
            $expertiseScore = 90;
        } elseif (strpos($expertise, $category) !== false) {
            $expertiseScore = 70;
        } else {
            $expertiseScore = 40;
        }

        $fuzzyScore = fuzzyInference($expertiseScore, $ev["workload"]);
        $aiScore    = aiFinalScore($fuzzyScore, $ev["workload"]);

        $ranked[] = [
            "id"    => $ev["evaluator_id"],
            "score" => $aiScore
        ];
    }

    if (empty($ranked)) {
        die("No evaluator available.");
    }

    usort($ranked, fn($a, $b) => $b["score"] <=> $a["score"]);

    $count    = count($ranked) >= 2 ? rand(1, 2) : 1;
    $selected = array_slice($ranked, 0, $count);
    $weight   = ($count === 2) ? 0.50 : 1.00;

    foreach ($selected as $ev) {

        $stmt = $conn->prepare("
            INSERT INTO assignment
                (project_id, evaluator_id, weight, assigned_by, assigned_date)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iidi", $projectId, $ev["id"], $weight, $adminId);
        $stmt->execute();
    }
}

$upd = $conn->prepare("
    UPDATE project
    SET status = 'Under Review'
    WHERE project_id = ?
");
$upd->bind_param("i", $projectId);
$upd->execute();


header("Location: assign_project.php?success=1");
exit;
