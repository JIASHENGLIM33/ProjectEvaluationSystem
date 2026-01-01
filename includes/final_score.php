<?php
function getFinalScore(mysqli $conn, int $projectId): array {

    $rows = $conn->query("
        SELECT 
            e.score,
            e.fuzzy_score,
            a.weight,
            ev.name AS evaluator
        FROM evaluation e
        JOIN assignment a 
            ON e.project_id = a.project_id
           AND e.evaluator_id = a.evaluator_id
        JOIN evaluator ev ON ev.evaluator_id = e.evaluator_id
        WHERE e.project_id = $projectId
    ")->fetch_all(MYSQLI_ASSOC);

    if (!$rows) {
        return ["final" => null, "details" => []];
    }

    $final = 0;
    foreach ($rows as $r) {
        $final += $r["score"] * $r["weight"];
    }

    return [
        "final"   => round($final, 1),
        "details" => $rows
    ];
}
