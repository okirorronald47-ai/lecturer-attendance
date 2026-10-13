<?php
session_start();
require_once __DIR__ . "/../config/database.php";

date_default_timezone_set("Africa/Kampala");

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

$totalRecords = 0;
$presentRecords = 0;
$totalLecturers = 0;
$todayRecords = 0;
$dailyData = [];
$monthlyData = [];
$departmentData = [];
$dbError = false;

try {
    $queries = [
        "total" => "SELECT COUNT(*) AS total FROM attendance",
        "present" => "SELECT COUNT(*) AS total FROM attendance WHERE `Status` = 'Present'",
        "lecturers" => "SELECT COUNT(*) AS total FROM lecturers",
        "today" => "SELECT COUNT(*) AS total FROM attendance WHERE `Attendance date` = CURDATE()"
    ];

    foreach ($queries as $key => $sql) {
        $result = $conn->query($sql);
        $value = (int)$result->fetch_assoc()["total"];
        $result->free();

        if ($key === "total") $totalRecords = $value;
        if ($key === "present") $presentRecords = $value;
        if ($key === "lecturers") $totalLecturers = $value;
        if ($key === "today") $todayRecords = $value;
    }

    $result = $conn->query("
        SELECT `Attendance date` AS attendance_date, COUNT(*) AS total
        FROM attendance
        WHERE `Attendance date` >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
          AND `Attendance date` <= CURDATE()
        GROUP BY `Attendance date`
        ORDER BY `Attendance date` ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $dailyData[] = $row;
    }
    $result->free();

    $result = $conn->query("
        SELECT DATE_FORMAT(`Attendance date`, '%Y-%m') AS month_key,
               COUNT(*) AS total
        FROM attendance
        WHERE `Attendance date` >= DATE_FORMAT(
            DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01'
        )
          AND `Attendance date` <= CURDATE()
        GROUP BY DATE_FORMAT(`Attendance date`, '%Y-%m')
        ORDER BY month_key ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $monthlyData[] = $row;
    }
    $result->free();

    $result = $conn->query("
        SELECT
            COALESCE(NULLIF(l.`Department`, ''), 'Unassigned') AS department,
            COUNT(a.ID) AS total
        FROM attendance a
        LEFT JOIN lecturers l
            ON l.`Lecturer id` = a.`Lecturer id`
        GROUP BY COALESCE(NULLIF(l.`Department`, ''), 'Unassigned')
        ORDER BY total DESC, department ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $departmentData[] = $row;
    }
    $result->free();

} catch (Throwable $e) {
    $dbError = true;
}

$dailyLookup = [];
foreach ($dailyData as $row) {
    $dailyLookup[$row["attendance_date"]] = (int)$row["total"];
}

$dailyLabels = [];
$dailyValues = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i days"));
    $dailyLabels[] = date("D, d M", strtotime($date));
    $dailyValues[] = $dailyLookup[$date] ?? 0;
}

$monthlyLookup = [];
foreach ($monthlyData as $row) {
    $monthlyLookup[$row["month_key"]] = (int)$row["total"];
}

$monthlyLabels = [];
$monthlyValues = [];

for ($i = 5; $i >= 0; $i--) {
    $month = date("Y-m", strtotime("first day of -$i months"));
    $monthlyLabels[] = date("M Y", strtotime($month . "-01"));
    $monthlyValues[] = $monthlyLookup[$month] ?? 0;
}

$departmentLabels = [];
$departmentValues = [];

foreach ($departmentData as $row) {
    $departmentLabels[] = $row["department"];
    $departmentValues[] = (int)$row["total"];
}

$presentPercentage = $totalRecords > 0
    ? round(($presentRecords / $totalRecords) * 100, 1)
    : 0;

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

$dailyLabelsJson = json_encode($dailyLabels, $jsonFlags);
$dailyValuesJson = json_encode($dailyValues);
$monthlyLabelsJson = json_encode($monthlyLabels, $jsonFlags);
$monthlyValuesJson = json_encode($monthlyValues);
$departmentLabelsJson = json_encode($departmentLabels, $jsonFlags);
$departmentValuesJson = json_encode($departmentValues);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Analytics | Lecturer Attendance System</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --bg: #050b18;
    --panel: #0c172b;
    --panel2: #101f38;
    --cyan: #00eaff;
    --blue: #4285ff;
    --text: #eaf5ff;
    --muted: #8ca4c3;
    --border: rgba(0,234,255,.17);
}

body {
    min-height: 100vh;
    font-family: "Segoe UI", Arial, sans-serif;
    color: var(--text);
    background:
        radial-gradient(ellipse at 10% 0%, rgba(0,234,255,.10), transparent 35%),
        radial-gradient(ellipse at 90% 15%, rgba(66,133,255,.12), transparent 35%),
        var(--bg);
}

a {
    text-decoration: none;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    padding: 20px 5%;
    background: rgba(7,16,33,.92);
    border-bottom: 1px solid var(--border);
    box-shadow: 0 8px 35px rgba(0,0,0,.25);
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
}

.brand-icon {
    width: 47px;
    height: 47px;
    display: grid;
    place-items: center;
    border: 1px solid var(--cyan);
    border-radius: 14px;
    color: var(--cyan);
    font-size: 23px;
    font-weight: 800;
    background: linear-gradient(145deg, #142d49, #071020);
    box-shadow: 0 0 20px rgba(0,234,255,.13),
                inset 0 0 15px rgba(0,234,255,.07);
}

.topbar h1 {
    font-size: 19px;
    letter-spacing: .3px;
    margin-bottom: 5px;
}

.topbar p {
    color: var(--muted);
    font-size: 12px;
}

.nav-links {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.nav-links a {
    padding: 10px 13px;
    border: 1px solid transparent;
    border-radius: 9px;
    color: #c7d8ee;
    font-size: 12px;
    transition: .25s;
}

.nav-links a:hover,
.nav-links a.active {
    color: var(--cyan);
    border-color: var(--border);
    background: rgba(0,234,255,.07);
    box-shadow: 0 0 18px rgba(0,234,255,.06);
}

.container {
    width: 90%;
    max-width: 1400px;
    margin: 35px auto;
}

.intro {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 28px;
}

.eyebrow {
    color: var(--cyan);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 10px;
}

.intro h2 {
    font-size: clamp(25px, 3vw, 35px);
    margin-bottom: 10px;
    letter-spacing: -.7px;
}

.intro p {
    max-width: 700px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.8;
}

.live-label {
    color: var(--cyan);
    border: 1px solid var(--border);
    background: rgba(0,234,255,.05);
    border-radius: 30px;
    padding: 10px 15px;
    font-size: 11px;
    white-space: nowrap;
}

.live-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    margin-right: 8px;
    border-radius: 50%;
    background: var(--cyan);
    box-shadow: 0 0 10px var(--cyan);
}

.warning {
    padding: 15px 18px;
    margin-bottom: 22px;
    color: #ffd59c;
    background: rgba(255,160,40,.08);
    border: 1px solid rgba(255,160,40,.3);
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.6;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 18px;
    margin-bottom: 35px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    padding: 23px;
    min-height: 145px;
    background: linear-gradient(145deg, rgba(17,34,59,.98), rgba(8,18,35,.98));
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 14px 30px rgba(0,0,0,.20),
                inset 0 1px rgba(255,255,255,.025);
    transition: transform .25s, border-color .25s;
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: rgba(0,234,255,.48);
}

.stat-card::after {
    content: "";
    position: absolute;
    width: 110px;
    height: 110px;
    right: -40px;
    bottom: -50px;
    border-radius: 50%;
    background: rgba(0,234,255,.07);
    filter: blur(2px);
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 19px;
}

.stat-card .label {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.5;
}

.stat-icon {
    width: 37px;
    height: 37px;
    display: grid;
    place-items: center;
    color: var(--cyan);
    background: rgba(0,234,255,.08);
    border: 1px solid var(--border);
    border-radius: 11px;
    font-size: 18px;
}

.stat-card h3 {
    font-size: 32px;
    font-weight: 700;
    letter-spacing: -.5px;
}

.stat-card .subtext {
    margin-top: 7px;
    color: #6f89aa;
    font-size: 11px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin: 32px 0 17px;
}

.section-heading h2 {
    font-size: 18px;
}

.section-heading p {
    margin-top: 6px;
    color: var(--muted);
    font-size: 12px;
}

.section-tag {
    padding: 7px 11px;
    color: #91dfff;
    background: rgba(66,133,255,.08);
    border: 1px solid rgba(66,133,255,.2);
    border-radius: 7px;
    font-size: 10px;
}

.chart-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 20px;
}

.chart-card,
.department-card,
.summary-card {
    min-width: 0;
    padding: 24px;
    background: linear-gradient(145deg, rgba(14,29,51,.98), rgba(8,17,33,.98));
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 15px 35px rgba(0,0,0,.18),
                inset 0 1px rgba(255,255,255,.025);
}

.chart-card h3,
.department-card h3,
.summary-card h3 {
    font-size: 15px;
    margin-bottom: 8px;
}

.chart-card p,
.department-card > p {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.7;
    margin-bottom: 20px;
}

.chart-wrap {
    position: relative;
    width: 100%;
    height: 285px;
}

.department-card {
    margin-top: 20px;
}

.department-card .chart-wrap {
    height: 320px;
    max-width: 950px;
    margin: 0 auto;
}

.empty-state {
    color: var(--muted);
    text-align: center;
    padding: 15px;
    font-size: 12px;
}

.summary-card {
    margin-top: 20px;
}

.summary-content {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 20px;
}

.summary-content p {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.9;
}

.percentage {
    display: grid;
    place-items: center;
    width: 105px;
    height: 105px;
    border: 2px solid var(--cyan);
    border-radius: 50%;
    color: var(--cyan);
    background: rgba(0,234,255,.04);
    box-shadow: 0 0 22px rgba(0,234,255,.08),
                inset 0 0 20px rgba(0,234,255,.05);
    font-size: 21px;
    font-weight: 800;
}

.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 16px;
    border: 1px solid rgba(0,234,255,.4);
    border-radius: 9px;
    background: linear-gradient(135deg, #087b9b, #0752a5);
    color: white;
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 5px 20px rgba(0,160,255,.12);
}

.action-button:hover {
    filter: brightness(1.18);
}

.footer {
    text-align: center;
    padding: 28px 15px;
    margin-top: 35px;
    border-top: 1px solid rgba(0,234,255,.1);
    color: #607995;
    font-size: 11px;
}

@media (max-width: 1000px) {
    .stats {
        grid-template-columns: repeat(2, minmax(0,1fr));
    }

    .chart-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .topbar {
        padding: 18px 4%;
    }

    .container {
        width: 92%;
        margin-top: 25px;
    }

    .stats {
        gap: 12px;
    }

    .stat-card {
        padding: 17px;
        min-height: 130px;
    }

    .stat-card h3 {
        font-size: 27px;
    }

    .chart-card,
    .department-card,
    .summary-card {
        padding: 17px;
    }

    .chart-wrap {
        height: 245px;
    }

    .department-card .chart-wrap {
        height: 280px;
    }

    .summary-content {
        grid-template-columns: 1fr;
    }

    .percentage {
        width: 85px;
        height: 85px;
    }

    .intro h2 {
        font-size: 27px;
    }
}

@media print {
    body {
        background: white !important;
        color: #111 !important;
    }

    .topbar {
        background: white !important;
        color: #111 !important;
        box-shadow: none;
        border-bottom: 1px solid #ddd;
    }

    .topbar p,
    .intro p,
    .chart-card p,
    .department-card > p,
    .summary-content p {
        color: #444 !important;
    }

    .nav-links,
    .no-print,
    .live-label {
        display: none !important;
    }

    .stat-card,
    .chart-card,
    .department-card,
    .summary-card {
        background: white !important;
        color: #111 !important;
        box-shadow: none;
        border: 1px solid #ddd;
        break-inside: avoid;
    }

    .stat-card h3,
    .chart-card h3,
    .department-card h3,
    .summary-card h3,
    .section-heading h2,
    .intro h2 {
        color: #111 !important;
    }

    .container {
        width: 100%;
        margin: 15px auto;
    }
}
</style>
</head>

<body>

<header class="topbar">
    <div class="brand">
        <div class="brand-icon">⌁</div>
        <div>
            <h1>LECTURER ATTENDANCE SYSTEM</h1>
            <p>ADMIN CONTROL CENTER / ANALYTICS</p>
        </div>
    </div>

    <nav class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="reports.php">Reports</a>
        <a href="manage_admins.php">Administrators</a>
        <a href="analytics.php" class="active">Analytics</a>
        <a href="../index.php">Home</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <section class="intro">
        <div>
            <div class="eyebrow">System intelligence / Overview</div>
            <h2>Attendance Analytics</h2>
            <p>
                Monitor lecturer attendance records, examine daily and monthly
                trends, and compare recorded entries across academic departments.
            </p>
        </div>

        <div class="live-label">
            <span class="live-dot"></span>
            DATABASE ANALYTICS
        </div>
    </section>

    <?php if ($dbError): ?>
        <div class="warning">
            Some analytics could not be loaded. Check the database connection
            and confirm that your attendance and lecturers tables have the
            expected column names.
        </div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-top">
                <p class="label">Total Attendance Records</p>
                <div class="stat-icon">▤</div>
            </div>
            <h3><?= $totalRecords ?></h3>
            <p class="subtext">All saved attendance entries</p>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <p class="label">Records Marked Present</p>
                <div class="stat-icon">✓</div>
            </div>
            <h3><?= $presentRecords ?></h3>
            <p class="subtext">Entries with Present status</p>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <p class="label">Registered Lecturers</p>
                <div class="stat-icon">♙</div>
            </div>
            <h3><?= $totalLecturers ?></h3>
            <p class="subtext">Lecturers in the database</p>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <p class="label">Today's Attendance</p>
                <div class="stat-icon">◷</div>
            </div>
            <h3><?= $todayRecords ?></h3>
            <p class="subtext">Entries recorded today</p>
        </div>
    </section>

    <div class="no-print" style="display:flex;justify-content:flex-end;">
        <button type="button" class="action-button" onclick="window.print()">
            ⎙ &nbsp; Print Analytics
        </button>
    </div>

    <section>
        <div class="section-heading">
            <div>
                <h2>Attendance Trends</h2>
                <p>Historical activity from the attendance database.</p>
            </div>
            <span class="section-tag">TIME-BASED ANALYSIS</span>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <h3>Daily Attendance</h3>
                <p>Attendance entries over the last seven days, including today.</p>
                <div class="chart-wrap">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>Monthly Attendance</h3>
                <p>Recorded entries for this month and the previous five months.</p>
                <div class="chart-wrap">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <h2>Department Comparison</h2>
                <p>See how attendance entries are distributed across departments.</p>
            </div>
            <span class="section-tag">DEPARTMENT ANALYSIS</span>
        </div>

        <div class="department-card">
            <h3>Attendance by Department</h3>
            <p>
                This chart counts recorded attendance entries associated with
                each department. It does not measure punctuality or attendance
                compliance against scheduled classes.
            </p>

            <?php if (empty($departmentData)): ?>
                <div class="empty-state">
                    No department attendance data is available yet.
                </div>
            <?php endif; ?>

            <div class="chart-wrap">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>
    </section>

    <section class="summary-card">
        <div class="summary-content">
            <div>
                <h3>Recorded Attendance Summary</h3>
                <p>
                    The system contains <strong><?= $totalRecords ?></strong>
                    attendance records, of which
                    <strong><?= $presentRecords ?></strong> are marked Present.
                    These Present entries account for
                    <strong><?= number_format($presentPercentage, 1) ?>%</strong>
                    of all recorded entries.

                    This is the share of records marked Present, not the
                    percentage of all scheduled classes attended.
                </p>
            </div>

            <div class="percentage">
                <?= number_format($presentPercentage, 1) ?>%
            </div>
        </div>
    </section>

</main>

<footer class="footer">
    Lecturer Attendance Management System
    &copy; <?= date("Y") ?>. All rights reserved.
</footer>

<script>
const dailyLabels = <?= $dailyLabelsJson ?: "[]" ?>;
const dailyValues = <?= $dailyValuesJson ?: "[]" ?>;

const monthlyLabels = <?= $monthlyLabelsJson ?: "[]" ?>;
const monthlyValues = <?= $monthlyValuesJson ?: "[]" ?>;

const departmentLabels = <?= $departmentLabelsJson ?: "[]" ?>;
const departmentValues = <?= $departmentValuesJson ?: "[]" ?>;

if (typeof Chart !== "undefined") {
    Chart.defaults.font.family = "'Segoe UI', Arial, sans-serif";
    Chart.defaults.color = "#8ca4c3";
    Chart.defaults.borderColor = "rgba(140,164,195,0.13)";

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: "index"
        },
        plugins: {
            legend: {
                labels: {
                    color: "#b8cce5",
                    usePointStyle: true,
                    boxWidth: 8,
                    padding: 18
                }
            },
            tooltip: {
                backgroundColor: "#071426",
                titleColor: "#00eaff",
                bodyColor: "#eaf5ff",
                borderColor: "rgba(0,234,255,.35)",
                borderWidth: 1,
                padding: 12
            }
        }
    };

    new Chart(document.getElementById("dailyChart"), {
        type: "line",
        data: {
            labels: dailyLabels,
            datasets: [{
                label: "Attendance Records",
                data: dailyValues,
                borderColor: "#00eaff",
                backgroundColor: "rgba(0,234,255,.10)",
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: "#00eaff",
                pointBorderColor: "#071426",
                pointBorderWidth: 2,
                borderWidth: 2
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: "#8ca4c3" }
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: "#8ca4c3" },
                    grid: { color: "rgba(140,164,195,.12)" }
                }
            }
        }
    });

    new Chart(document.getElementById("monthlyChart"), {
        type: "bar",
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: "Attendance Records",
                data: monthlyValues,
                backgroundColor: "rgba(66,133,255,.75)",
                hoverBackgroundColor: "#00eaff",
                borderColor: "#4285ff",
                borderWidth: 1,
                borderRadius: 7,
                maxBarThickness: 48
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: "#8ca4c3" }
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: "#8ca4c3" },
                    grid: { color: "rgba(140,164,195,.12)" }
                }
            }
        }
    });

    new Chart(document.getElementById("departmentChart"), {
        type: "bar",
        data: {
            labels: departmentLabels,
            datasets: [{
                label: "Attendance Records",
                data: departmentValues,
                backgroundColor: "rgba(0,234,255,.62)",
                hoverBackgroundColor: "#00eaff",
                borderColor: "#00eaff",
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 38
            }]
        },
        options: {
            ...commonOptions,
            indexAxis: "y",
            plugins: {
                ...commonOptions.plugins,
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: "#8ca4c3" },
                    grid: { color: "rgba(140,164,195,.12)" }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: "#b8cce5" }
                }
            }
        }
    });
} else {
    document.querySelectorAll(".chart-wrap").forEach(function (element) {
        element.innerHTML =
            '<p class="empty-state">Charts could not load. Check your internet connection and reload this page.</p>';
    });
}
</script>

</body>
</html>
