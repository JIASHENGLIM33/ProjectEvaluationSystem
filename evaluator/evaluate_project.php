<?php


require_once __DIR__ . "/../config/auth_check.php";
allow_role("evaluator");

require_once __DIR__ . "/../config/config.php";

$evaluatorId = $_SESSION["id"];


$projectId = intval($_GET["project_id"] ?? 0);
if ($projectId <= 0) {
    die("Invalid project ID.");
}


$stmt = $conn->prepare("
    SELECT
        p.project_id,
        p.title,
        p.category,
        p.description,
        p.abstract_pdf,
        p.extended_abstract_docx,
        p.system_video_mp4,
        p.poster_pptx,
        p.slides_pptx,
        p.github_link,
        s.name AS student_name
    FROM project p
    JOIN assignment a ON p.project_id = a.project_id
    JOIN student s ON p.student_id = s.student_id
    WHERE p.project_id = ?
      AND a.evaluator_id = ?
");
$stmt->bind_param("ii", $projectId, $evaluatorId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("Access denied or project not assigned to you.");
}


$check = $conn->prepare("
    SELECT evaluation_id
    FROM evaluation
    WHERE project_id = ?
      AND evaluator_id = ?
");
$check->bind_param("ii", $projectId, $evaluatorId);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    die("You have already evaluated this project.");
}


function normalizeScore(float $raw, float $max): float {
    if ($max <= 0) return 0;
    return round(($raw / $max) * 100, 2);
}


$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    function score($key) {
        return isset($_POST[$key]) ? floatval($_POST[$key]) : 0;
    }


    $taRaw =
        score('ta_usefulness')   / 10 * 5 +
        score('ta_creative')     / 10 * 2 +
        score('ta_novelty')      / 10 * 3 +
        score('ta_features')     / 10 * 10 +
        score('ta_design')       / 10 * 5 +
        score('ta_requirements') / 10 * 5 +
        score('ta_performance')  / 10 * 4 +
        score('ta_ui')           / 10 * 4 +
        score('ta_reliability')  / 10 * 4 +
        score('ta_security')     / 10 * 4 +
        score('ta_scalability')  / 10 * 4 +
        score('ta_completion')   / 10 * 5 +
        score('ta_problem')      / 10 * 5;


    $prRaw =
        score('pr_intro')      / 10 * 10 +
        score('pr_analysis')   / 10 * 20 +
        score('pr_design')     / 10 * 40 +
        score('pr_testing')    / 10 * 20 +
        score('pr_conclusion') / 10 * 10;


    $pRaw =
        score('p_preparation') / 10 * 20 +
        score('p_slides')      / 10 * 20 +
        score('p_content')     / 10 * 30 +
        score('p_qa')          / 10 * 30;


    $finalScore = round(
        normalizeScore($taRaw, 60) * 0.60 +
        normalizeScore($prRaw, 100) * 0.20 +
        normalizeScore($pRaw, 100)  * 0.20,
        2
    );

    /* ---------- Feedback ---------- */
    $feedback = trim($_POST['feedback'] ?? '');

    if ($feedback === '') {
        $error = "Evaluator feedback is required.";
    } else {

        $rubricJson = json_encode([
            "TA" => round($taRaw, 2),
            "PR" => round($prRaw, 2),
            "P"  => round($pRaw, 2),
            "Final" => $finalScore
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare("
            INSERT INTO evaluation
                (project_id, evaluator_id, score, rubric_json, feedback, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "iidss",
            $projectId,
            $evaluatorId,
            $finalScore,
            $rubricJson,
            $feedback
        );

        if ($stmt->execute()) {


            $assigned = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM assignment
                WHERE project_id = ?
            ");
            $assigned->bind_param("i", $projectId);
            $assigned->execute();
            $totalEvaluators = $assigned->get_result()->fetch_assoc()['total'];

            $completed = $conn->prepare("
                SELECT COUNT(*) AS done
                FROM evaluation
                WHERE project_id = ?
            ");
            $completed->bind_param("i", $projectId);
            $completed->execute();
            $doneEvaluations = $completed->get_result()->fetch_assoc()['done'];

            if ($doneEvaluations >= $totalEvaluators) {


    $stmtStatus = $conn->prepare("
        UPDATE project
        SET status = 'Completed'
        WHERE project_id = ?
    ");
    $stmtStatus->bind_param("i", $projectId);
    $stmtStatus->execute();

} else {


    $stmtStatus = $conn->prepare("
        UPDATE project
        SET status = 'Under Review'
        WHERE project_id = ?
    ");
    $stmtStatus->bind_param("i", $projectId);
    $stmtStatus->execute();
}


            $success = "Evaluation submitted successfully.";
        } else {
            $error = "Database error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Evaluate Project</title>
<script src="https://cdn.tailwindcss.com"></script>

<script>
function confirmDownload(fileName) {
    return confirm(
        "Are you sure you want to download this file?\n\nFile: " + fileName
    );
}
</script>
</head>

<body class="bg-gray-100 p-8">

<div class="max-w-5xl mx-auto bg-white p-6 rounded-xl shadow">

<h1 class="text-2xl font-bold mb-2">Evaluate Project</h1>

<p class="text-gray-700">
    <strong><?= htmlspecialchars($project["title"]) ?></strong>
</p>
<p class="text-sm text-gray-600 mb-6">
    Student: <?= htmlspecialchars($project["student_name"]) ?> |
    Category: <?= htmlspecialchars($project["category"]) ?>
</p>

<!-- ================= Deliverables ================= -->
<h2 class="text-lg font-semibold mb-4">Student Submitted Deliverables</h2>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

<!-- Abstract (PDF) : View + Download -->
<div class="border rounded-xl p-4 bg-gray-50">
    <h3 class="font-medium mb-2">Abstract (PDF)</h3>
    <?php if ($project["abstract_pdf"]): ?>
        <div class="flex gap-3">
            <a href="/pems/<?= htmlspecialchars($project["abstract_pdf"]) ?>" target="_blank"
               class="px-3 py-1 bg-blue-600 text-white rounded text-sm">
                View
            </a>
            <a href="/pems/<?= htmlspecialchars($project["abstract_pdf"]) ?>" download
               onclick="return confirmDownload('<?= basename($project["abstract_pdf"]) ?>')"
               class="px-3 py-1 bg-gray-600 text-white rounded text-sm">
                Download
            </a>
        </div>
    <?php else: ?>
        <p class="text-sm text-gray-400">Not submitted</p>
    <?php endif; ?>
</div>

<!-- Extended Abstract (DOCX) : Download only -->
<div class="border rounded-xl p-4 bg-gray-50">
    <h3 class="font-medium mb-2">Extended Abstract (DOCX)</h3>
    <?php if ($project["extended_abstract_docx"]): ?>
        <a href="/pems/<?= htmlspecialchars($project["extended_abstract_docx"]) ?>" download
           onclick="return confirmDownload('<?= basename($project["extended_abstract_docx"]) ?>')"
           class="px-3 py-1 bg-gray-600 text-white rounded text-sm">
            Download
        </a>
    <?php else: ?>
        <p class="text-sm text-gray-400">Not submitted</p>
    <?php endif; ?>
</div>

<!-- Poster (PPTX) : Download only -->
<div class="border rounded-xl p-4 bg-gray-50">
    <h3 class="font-medium mb-2">Poster (PPTX)</h3>
    <?php if ($project["poster_pptx"]): ?>
        <a href="/pems/<?= htmlspecialchars($project["poster_pptx"]) ?>" download
           onclick="return confirmDownload('<?= basename($project["poster_pptx"]) ?>')"
           class="px-3 py-1 bg-gray-600 text-white rounded text-sm">
            Download
        </a>
    <?php else: ?>
        <p class="text-sm text-gray-400">Not submitted</p>
    <?php endif; ?>
</div>

<!-- Presentation Slides (PPTX) : Download only -->
<div class="border rounded-xl p-4 bg-gray-50">
    <h3 class="font-medium mb-2">Presentation Slides</h3>
    <?php if ($project["slides_pptx"]): ?>
        <a href="/pems/<?= htmlspecialchars($project["slides_pptx"]) ?>" download
           onclick="return confirmDownload('<?= basename($project["slides_pptx"]) ?>')"
           class="px-3 py-1 bg-gray-600 text-white rounded text-sm">
            Download
        </a>
    <?php else: ?>
        <p class="text-sm text-gray-400">Not submitted</p>
    <?php endif; ?>
</div>


<!-- Video -->
<div class="border rounded-xl p-4 bg-gray-50 md:col-span-2">
    <h3 class="font-medium mb-2">System Operation Video</h3>
    <?php if ($project["system_video_mp4"]): ?>
        <video controls class="w-full max-w-3xl mb-2 border rounded">
            <source src="/pems/<?= htmlspecialchars($project["system_video_mp4"]) ?>" type="video/mp4">
        </video>
        <a href="/pems/<?= htmlspecialchars($project["system_video_mp4"]) ?>" download
           onclick="return confirmDownload('<?= basename($project["system_video_mp4"]) ?>')"
           class="inline-block px-4 py-1 bg-gray-600 text-white rounded text-sm">
            Download Video
        </a>
    <?php else: ?>
        <p class="text-sm text-gray-400">Not submitted</p>
    <?php endif; ?>
</div>

<!-- GitHub -->
<div class="border rounded-xl p-4 bg-gray-50 md:col-span-2">
    <h3 class="font-medium mb-2">GitHub Repository</h3>
    <?php if ($project["github_link"]): ?>
        <a href="<?= htmlspecialchars($project["github_link"]) ?>" target="_blank"
           class="text-blue-600 underline break-all">
            <?= htmlspecialchars($project["github_link"]) ?>
        </a>
    <?php else: ?>
        <p class="text-sm text-gray-400">Not submitted</p>
    <?php endif; ?>
</div>

</div>

<?php if ($error): ?>
<div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-green-100 text-green-700 p-3 rounded">
    <?= $success ?>
    <div class="mt-2">
        <a href="assigned_project.php" class="text-blue-600 underline">
            ← Back to Assigned Projects
        </a>
    </div>
</div>
<?php else: ?>

<!-- ================= Rubrics ================= -->
<form method="POST" class="space-y-8">

<h2 class="text-xl font-semibold mb-2">Rubric 1 – Technical Achievements (60%)</h2>

<table class="w-full border text-sm mb-8">
<thead class="bg-gray-200">
<tr>
    <th class="border p-2 w-24">Mark Allocation</th>
    <th class="border p-2">Key Assessment</th>
    <th class="border p-2 w-32 text-center">Scale (0–10)</th>
    <th class="border p-2 w-48">Remarks</th>
</tr>
</thead>

<tbody>

<!-- ================= I. Innovation ================= -->
<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">10%</td>
    <td colspan="3" class="border p-2">I. Innovation</td>
</tr>

<tr>
    <td class="border p-2">5</td>
    <td class="border p-2">
        <b>i) Usefulness</b><br>
        The quality or fact of being useful to the end users.<br>
        Can the product be potentially commercialized?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_usefulness" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_usefulness_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">2</td>
    <td class="border p-2">
        <b>ii) Creative</b><br>
        The idea is valuable and different from the existing ones.
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_creative" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_creative_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">3</td>
    <td class="border p-2">
        <b>iii) Novelty</b><br>
        The quality of being new and original.
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_novelty" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_novelty_remark" class="w-full border"></textarea>
    </td>
</tr>

<!-- ================= II. Functionalities ================= -->
<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">20%</td>
    <td colspan="3" class="border p-2">II. Functionalities</td>
</tr>

<tr>
    <td class="border p-2">10</td>
    <td class="border p-2">
        <b>i) Features</b><br>
        Is it fully functional, partially functional or not working?<br>
        Have all the objectives met accordingly?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_features" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_features_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">5</td>
    <td class="border p-2">
        <b>ii) Design</b><br>
        Are the levels of complexity of the design done by the student adequate?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_design" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_design_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">5</td>
    <td class="border p-2">
        <b>iii) Meeting Requirements</b><br>
        Has the conceptual model been correctly translated into an application?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_requirements" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_requirements_remark" class="w-full border"></textarea>
    </td>
</tr>

<!-- ================= III. Quality ================= -->
<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">20%</td>
    <td colspan="3" class="border p-2">III. Quality</td>
</tr>

<tr>
    <td class="border p-2">4</td>
    <td class="border p-2">
        <b>i) Performance</b><br>
        Database and system performance.
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_performance" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_performance_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">4</td>
    <td class="border p-2">
        <b>ii) User friendliness</b><br>
        User interface is easy to use.
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_ui" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_ui_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">4</td>
    <td class="border p-2">
        <b>iii) Reliability</b><br>
        The quality of performing consistently well.
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_reliability" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_reliability_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">4</td>
    <td class="border p-2">
        <b>iv) Security</b><br>
        System wise, is it vulnerable or robust?<br>
        Data security.
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_security" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_security_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">4</td>
    <td class="border p-2">
        <b>v) Scalability</b><br>
        Any opportunity for future enhancements?<br>
        Can it be customised to meet the user needs?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_scalability" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_scalability_remark" class="w-full border"></textarea>
    </td>
</tr>

<!-- ================= IV. Effort ================= -->
<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">10%</td>
    <td colspan="3" class="border p-2">IV. Effort</td>
</tr>

<tr>
    <td class="border p-2">5</td>
    <td class="border p-2">
        <b>i) Completion</b><br>
        Is the project complete based on the objectives or proposal?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_completion" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_completion_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr>
    <td class="border p-2">5</td>
    <td class="border p-2">
        <b>ii) Problem-solving Ability</b><br>
        Did they display a proactive attitude towards problem-solving and overcoming obstacles?<br>
        This could include technical issues, resource constraints, or unforeseen complications.
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="ta_problem" min="0" max="10" required class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="ta_problem_remark" class="w-full border"></textarea>
    </td>
</tr>

<!-- ================= TOTAL ================= -->
<tr class="bg-gray-200 font-semibold">
    <td class="border p-2">60</td>
    <td colspan="3" class="border p-2">TOTAL</td>
</tr>

</tbody>
</table>



<!-- ================= TABLE 2 ================= -->
<h2 class="text-xl font-semibold mb-2">Rubric 2 – Project Report (100%)</h2>

<table class="w-full border text-sm mb-8">
<thead class="bg-gray-200">
<tr>
    <th class="border p-2 w-24">Mark Allocation</th>
    <th class="border p-2">Key Assessment</th>
    <th class="border p-2 w-32 text-center">Scale (0–10)</th>
    <th class="border p-2 w-48">Remarks</th>
</tr>
</thead>

<tbody>

<!-- ================= Project Report ================= -->
<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">10%</td>
    <td colspan="3" class="border p-2">II. Introduction</td>
</tr>

<tr>
    <td class="border p-2">10</td>
    <td class="border p-2">
        <b>Introduction</b><br>
        Are the objectives clearly defined?<br>
        Is the conceptual framework explicit and justified?<br>
        Are the variables being investigated clearly identified and presented?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="pr_intro" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="pr_intro_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">20%</td>
    <td colspan="3" class="border p-2">III. Analysis Requirement</td>
</tr>

<tr>
    <td class="border p-2">20</td>
    <td class="border p-2">
        <b>Analysis Requirement</b><br>
        Source materials are up to date and comprehensive.<br>
        Is the literature review focused on a clear and relevant research question or topic?<br>
        Does it stay within the defined scope?<br>
        Are proper research methods and/or development tools defined and clearly described?<br>
        Are the research method and/or development tools discussed to achieve the goals of the project?<br>
        Are the design and solution achievable?<br>
        Approach and investigative scope are appropriate (in line with problem statements and objectives).
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="pr_analysis" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="pr_analysis_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">40%</td>
    <td colspan="3" class="border p-2">IV. Design and Coding</td>
</tr>

<tr>
    <td class="border p-2">40</td>
    <td class="border p-2">
        <b>Design and Coding</b><br>
        Are all the important implementation steps covered in adequate details?<br>
        Coding is well organised and well structured.<br>
        Any syntax errors found?<br>
        Is the overall design of the interface, layout and database structure properly developed?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="pr_design" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="pr_design_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">20%</td>
    <td colspan="3" class="border p-2">V. Testing & Debugging</td>
</tr>

<tr>
    <td class="border p-2">20</td>
    <td class="border p-2">
        <b>Testing & Debugging</b><br>
        Is the testing thoroughly done?<br>
        Is it properly documented which includes the test steps, report, and findings?<br>
        Does it include any debugging procedure?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="pr_testing" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="pr_testing_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">10%</td>
    <td colspan="3" class="border p-2">VI. Conclusion</td>
</tr>

<tr>
    <td class="border p-2">10</td>
    <td class="border p-2">
        <b>Conclusion</b><br>
        What is the level of achievements with respect to the objectives?<br>
        Limitation and future enhancement.
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="pr_conclusion" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="pr_conclusion_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-200 font-semibold">
    <td class="border p-2">100</td>
    <td colspan="3" class="border p-2">TOTAL</td>
</tr>

</tbody>
</table>



<!-- ================= TABLE 3 ================= -->
<h2 class="text-xl font-semibold mb-2">Rubric 3 – Presentation (100%)</h2>

<table class="w-full border text-sm mb-8">
<thead class="bg-gray-200">
<tr>
    <th class="border p-2 w-24">Mark Allocation</th>
    <th class="border p-2">Key Assessment</th>
    <th class="border p-2 w-32 text-center">Scale (0–10)</th>
    <th class="border p-2 w-48">Remarks</th>
</tr>
</thead>

<tbody>

<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">20%</td>
    <td colspan="3" class="border p-2">I. Preparation</td>
</tr>

<tr>
    <td class="border p-2">20</td>
    <td class="border p-2">
        <b>Preparation</b><br>
        Presenter’s preparedness for the presentation.<br>
        Is the presentation clear and concise with correct use of technical terms and descriptions?<br>
        Are the equipment, software tools, hardware/software prototype and test environment in place?<br>
        Are hardcopies of the project reports and presentation slides made available?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="p_preparation" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="p_preparation_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">20%</td>
    <td colspan="3" class="border p-2">II. Presentation Slides & Skills</td>
</tr>

<tr>
    <td class="border p-2">20</td>
    <td class="border p-2">
        <b>Presentation Slides & Skills</b><br>
        Are the slide design, diagrams, tables etc. informative, clear, attractive and interactive?<br>
        Is the presenter speaking at an appropriate speed and tone?<br>
        Can the presenter manage the presentation time (complete within 20 minutes excluding Q&A)?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="p_slides" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="p_slides_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">30%</td>
    <td colspan="3" class="border p-2">III. Content</td>
</tr>

<tr>
    <td class="border p-2">30</td>
    <td class="border p-2">
        <b>Content</b><br>
        Can the audience follow the flow of the content?<br>
        Does the presentation cover the key aspects of the project well – overall idea or scope, objectives and methodology?<br>
        Are adequate results presented with appropriate analysis?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="p_content" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="p_content_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-100 font-semibold">
    <td class="border p-2">30%</td>
    <td colspan="3" class="border p-2">IV. Question & Answer Session</td>
</tr>

<tr>
    <td class="border p-2">30</td>
    <td class="border p-2">
        <b>Question & Answer Session</b><br>
        Is the presenter aware of the limitations of the algorithm / systems / simulations / applications / prototypes / models / frameworks and provide suggestions for improvement?<br>
        Can the student answer all questions completely with appropriate supporting facts/materials?<br>
        Does the presenter demonstrate good technical knowledge and skills?
    </td>
    <td class="border p-2 text-center">
        <input type="number" name="p_qa" min="0" max="10" required
               class="w-20 border text-center">
    </td>
    <td class="border p-2">
        <textarea name="p_qa_remark" class="w-full border"></textarea>
    </td>
</tr>

<tr class="bg-gray-200 font-semibold">
    <td class="border p-2">100</td>
    <td colspan="3" class="border p-2">TOTAL</td>
</tr>

</tbody>
</table>




<!-- ================= FEEDBACK ================= -->
<div class="border-t pt-4">
    <label class="font-medium block mb-1">Evaluator Feedback</label>

    <textarea id="feedbackBox"
              name="feedback"
              rows="5"
              class="w-full border rounded px-3 py-2 mb-3"
              placeholder="Write feedback manually or use AI suggestion..."></textarea>

    <div class="flex justify-between items-center">
        <button type="button"
                onclick="generateAISuggestion()"
                class="px-4 py-2 bg-gray-700 text-white rounded">
            🤖 AI Suggest Feedback
        </button>

        <button type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded">
            Submit Evaluation
        </button>
    </div>
</div>

<script>
function generateAISuggestion() {

    function v(name) {
        return Number(document.querySelector(`[name="${name}"]`)?.value || 0);
    }

    const TA = (v("ta_features") + v("ta_design") + v("ta_security") + v("ta_scalability")) / 4;
    const PR = (v("pr_intro") + v("pr_analysis") + v("pr_design")) / 3;
    const P  = (v("p_preparation") + v("p_slides") + v("p_content")) / 3;

    let feedback = "Overall, the project demonstrates ";

    feedback += TA >= 7
        ? "a strong technical foundation with well-implemented system features. "
        : TA >= 4
            ? "a reasonable technical implementation, though several components require refinement. "
            : "limited technical robustness, with significant improvement required. ";

    feedback += PR >= 7
        ? "The project report is well-structured and supported by clear analysis. "
        : PR >= 4
            ? "The project report is understandable but lacks sufficient analytical depth. "
            : "The project report lacks clarity and technical justification. ";

    feedback += P >= 7
        ? "The presentation was confident and effectively communicated the outcomes. "
        : P >= 4
            ? "The presentation covered the main ideas but could be improved in delivery and structure. "
            : "The presentation did not effectively communicate the project outcomes. ";

    feedback += "Future improvements should focus on strengthening system robustness, enhancing analytical discussion, and improving presentation clarity.";

    document.getElementById("feedbackBox").value = feedback;
}

</script>

</form>
<?php endif; ?>

</div>
</body>
</html>
