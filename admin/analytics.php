<?php
session_start();
include '../config/config.php';

// 只有 admin 可以访问
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

/*
 * 数据查询区
 */

// 1) 项目状态分布
$statusSql = "SELECT status, COUNT(*) AS cnt FROM projects GROUP BY status";
$statusRes = mysqli_query($conn, $statusSql);
$statusLabels = [];
$statusCounts = [];
while ($r = mysqli_fetch_assoc($statusRes)) {
    $statusLabels[] = $r['status'];
    $statusCounts[] = (int)$r['cnt'];
}

// 2) 每位评审的分配项目数量（显示 username 和数量）
$evaSql = "
    SELECT u.username, COUNT(p.id) AS cnt
    FROM users u
    LEFT JOIN projects p ON p.assigned_evaluator = u.id
    WHERE u.role = 'evaluator'
    GROUP BY u.id, u.username
    ORDER BY cnt DESC
";
$evaRes = mysqli_query($conn, $evaSql);
$evaLabels = [];
$evaCounts = [];
while ($r = mysqli_fetch_assoc($evaRes)) {
    $evaLabels[] = $r['username'];
    $evaCounts[] = (int)$r['cnt'];
}

// 3) 最近 6 个月按月提交数量（按 submitted_date）
$months = [];
$monthCounts = [];

// 生成最近 6 个月的 YYYY-MM 格式数组（从当前月往前 5 个月）
for ($i = 5; $i >= 0; $i--) {
    $m = date("Y-m", strtotime("-$i month"));
    $months[] = $m;
    $monthCounts[$m] = 0;
}

// 查询这些月份的提交量
$startMonth = $months[0] . "-01";
$endMonth = date("Y-m-t", strtotime(end($months) . "-01"));
$timeSql = "
    SELECT DATE_FORMAT(submitted_date, '%Y-%m') AS ym, COUNT(*) AS cnt
    FROM projects
    WHERE submitted_date BETWEEN '$startMonth' AND '$endMonth'
    GROUP BY ym
";
$timeRes = mysqli_query($conn, $timeSql);
while ($r = mysqli_fetch_assoc($timeRes)) {
    $ym = $r['ym'];
    if (isset($monthCounts[$ym])) $monthCounts[$ym] = (int)$r['cnt'];
}
$monthLabels = array_values($months);
$monthValues = array_map(function($m) use ($monthCounts) { return $monthCounts[$m]; }, $monthLabels);

?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8" />
    <title>Admin Analytics - Project Evaluation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="../assets/css/admin.css" />
    <style>
        /* 仅为 analytics 页面追加少量布局样式（你也可把这段并入 admin.css） */
        .analytics-container { padding: 24px; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
        .chart-card { background:#fff; padding:16px; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
        .chart-title { font-weight:600; margin-bottom:8px; color:#1f2937; }
        @media (max-width: 900px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
        .small-note { color:#6b7280; font-size:13px; margin-top:8px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="manage_users.php">👨‍🎓 Manage Users</a>
    <a href="manage_projects.php">📁 Manage Projects</a>
    <a href="analytics.php" class="active">📊 Analytics</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<div class="content analytics-container">
    <h1>Project Analytics</h1>
    <p class="small-note">Overview: project distribution, evaluator workload, submission trends (last 6 months).</p>

    <div class="charts-grid">
        <!-- 饼图：项目状态分布 -->
        <div class="chart-card">
            <div class="chart-title">Project Status Distribution</div>
            <canvas id="statusChart" width="400" height="300"></canvas>
            <p class="small-note">Shows how many projects are Pending / Under Review / Completed.</p>
        </div>

        <!-- 柱状图：评审工作量 -->
        <div class="chart-card">
            <div class="chart-title">Evaluator Assigned Projects</div>
            <canvas id="evaChart" width="400" height="300"></canvas>
            <p class="small-note">Number of projects assigned to each evaluator.</p>
        </div>

        <!-- 折线图：提交趋势 -->
        <div class="chart-card" style="grid-column: 1 / -1;">
            <div class="chart-title">Submissions Trend (Last 6 Months)</div>
            <canvas id="trendChart" width="900" height="250"></canvas>
            <p class="small-note">Monthly submission trend. Helps identify busy periods.</p>
        </div>
    </div>
</div>

<script>
    // 数据从 PHP 注入到 JS
    const statusLabels = <?php echo json_encode($statusLabels); ?>;
    const statusCounts = <?php echo json_encode($statusCounts); ?>;

    const evaLabels = <?php echo json_encode($evaLabels); ?>;
    const evaCounts = <?php echo json_encode($evaCounts); ?>;

    const monthLabels = <?php echo json_encode($monthLabels); ?>;
    const monthValues = <?php echo json_encode($monthValues); ?>;

    // ===== Status Pie Chart =====
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: ['#4e73df', '#f6c23e', '#1cc88a', '#6c757d'],
                hoverOffset: 6
            }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom' }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // ===== Evaluator Bar Chart =====
    const ctxEva = document.getElementById('evaChart').getContext('2d');
    const evaChart = new Chart(ctxEva, {
        type: 'bar',
        data: {
            labels: evaLabels,
            datasets: [{
                label: 'Assigned Projects',
                data: evaCounts,
                backgroundColor: '#4e73df'
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, precision:0, ticks: { stepSize: 1 } }
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // ===== Monthly Trend Line Chart =====
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    const trendChart = new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Submissions',
                data: monthValues,
                fill: true,
                tension: 0.3,
                backgroundColor: 'rgba(78,115,223,0.12)',
                borderColor: '#4e73df',
                pointBackgroundColor: '#fff',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, precision:0, ticks: { stepSize: 1 } }
            },
            plugins: { legend: { display: false } }
        }
    });
</script>

</body>
</html>
