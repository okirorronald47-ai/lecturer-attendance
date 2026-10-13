
<?php
require_once __DIR__ . "/../config/database.php";

mysqli_set_charset($conn, "utf8mb4");

$lecturerFilter = trim($_GET["lecturer"] ?? "");
$dateFilter = trim($_GET["date"] ?? "");

$attendanceRecords = [];
$totalRecords = 0;
$presentCount = 0;
$departureCount = 0;
$queryError = "";
$databaseName = "";

// Confirm which database this page is using.
$dbResult = mysqli_query($conn, "SELECT DATABASE() AS db_name");

if ($dbResult) {
    $dbRow = mysqli_fetch_assoc($dbResult);
    $databaseName = $dbRow["db_name"] ?? "";
}

// Build the query without restricting results to one lecturer.
// GROUP BY is intentionally not used: each attendance record must appear.
$sql = "
    SELECT
        a.ID AS attendance_id,
        a.`Lecturer id` AS lecturer_id,
        COALESCE(NULLIF(l.`Full name`, ''), 'Unknown lecturer') AS lecturer_name,
        COALESCE(NULLIF(l.`Department`, ''), 'Unknown') AS department,
        a.`Attendance date` AS attendance_date,
        a.`Attendance time` AS attendance_time,
        a.`Status` AS attendance_status,
        a.`Created at` AS created_at,
        a.`Departure time` AS departure_time
    FROM `attendance` AS a
    LEFT JOIN `lecturers` AS l
        ON TRIM(LOWER(a.`Lecturer id`)) =
           TRIM(LOWER(l.`Lecturer id`))
    WHERE 1 = 1
";

$params = [];
$types = "";

// Search by lecturer ID, lecturer name, or department.
if ($lecturerFilter !== "") {
    $sql .= "
        AND (
            a.`Lecturer id` LIKE ?
            OR l.`Full name` LIKE ?
            OR l.`Department` LIKE ?
        )
    ";

    $searchValue = "%" . $lecturerFilter . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sss";
}

// Filter by attendance date only when a date was selected.
if ($dateFilter !== "") {
    $sql .= " AND a.`Attendance date` = ? ";
    $params[] = $dateFilter;
    $types .= "s";
}

// Show newest attendance records first.
$sql .= "
    ORDER BY
        a.`Attendance date` DESC,
        a.`Attendance time` DESC,
        a.ID DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $queryError = mysqli_error($conn);
} else {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $queryError = mysqli_stmt_error($stmt);
    } else {
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $attendanceRecords[] = $row;
            }
        } else {
            $queryError = mysqli_error($conn);
        }
    }

    mysqli_stmt_close($stmt);
}

$totalRecords = count($attendanceRecords);

foreach ($attendanceRecords as $record) {
    if (
        strtolower(trim((string)($record["attendance_status"] ?? "")))
        === "present"
    ) {
        $presentCount++;
    }

    if (trim((string)($record["departure_time"] ?? "")) !== "") {
        $departureCount++;
    }
}

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function displayTime($value) {
    if ($value === null || trim((string)$value) === "") {
        return "—";
    }

    $timestamp = strtotime((string)$value);

    return $timestamp !== false
        ? date("h:i:s A", $timestamp)
        : (string)$value;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#050b18">

<title>Attendance Records | University Attendance System</title>

<style>
:root {
    --bg: #050b18;
    --panel: rgba(13, 25, 48, .92);
    --cyan: #42f5e9;
    --blue: #5598ff;
    --text: #edf7ff;
    --muted: #93a9c4;
    --border: rgba(93, 208, 238, .22);
    --green: #45f0b1;
    --red: #ff7188;
    --gold: #ffd477;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    color: var(--text);
    font-family: "Segoe UI", Arial, sans-serif;
    background:
        radial-gradient(ellipse at 15% 0%, rgba(36,100,190,.23), transparent 40%),
        radial-gradient(ellipse at 95% 35%, rgba(0,225,210,.12), transparent 35%),
        linear-gradient(145deg, #040916, #081326 55%, #050b18);
    overflow-x: hidden;
}

.topbar {
    border-bottom: 1px solid var(--border);
    background: rgba(5, 12, 27, .94);
}

.topbar-inner {
    max-width: 1250px;
    min-height: 82px;
    padding: 15px 24px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
}

.brand-mark {
    width: 45px;
    height: 45px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(66,245,233,.4);
    border-radius: 14px;
    color: var(--cyan);
    background: rgba(66,245,233,.09);
    font-size: 20px;
    font-weight: 800;
}

.brand h1 {
    margin: 0;
    font-size: 15px;
}

.brand p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 12px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

.live-status {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #b8fbe6;
    font-size: 11px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--green);
}

.nav-link {
    color: var(--cyan);
    text-decoration: none;
    border: 1px solid var(--border);
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
}

.nav-link:hover {
    background: rgba(66,245,233,.09);
}

.page {
    width: min(1250px, calc(100% - 40px));
    margin: 34px auto;
}

.breadcrumb {
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 27px;
}

.breadcrumb span {
    color: var(--cyan);
}

.page-heading {
    margin-bottom: 27px;
}

.eyebrow {
    color: var(--cyan);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin: 0 0 10px;
}

.page-heading h2 {
    font-size: clamp(27px, 4vw, 38px);
    margin: 0;
}

.page-heading p {
    color: var(--muted);
    margin: 10px 0 0;
    font-size: 13px;
    line-height: 1.7;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 17px;
    margin-bottom: 22px;
}

.stat-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 22px;
    border: 1px solid var(--border);
    border-radius: 17px;
    background: linear-gradient(140deg, var(--panel), rgba(8,18,37,.96));
}

.stat-label {
    color: var(--muted);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.1px;
    text-transform: uppercase;
}

.stat-number {
    margin-top: 8px;
    font-size: 30px;
    font-weight: 800;
}

.stat-icon {
    width: 47px;
    height: 47px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    color: var(--cyan);
    background: rgba(66,245,233,.09);
    border: 1px solid rgba(66,245,233,.16);
    font-size: 21px;
}

.panel {
    padding: 24px;
    border: 1px solid var(--border);
    border-radius: 19px;
    background: linear-gradient(145deg, var(--panel), rgba(8,18,37,.96));
}

.panel-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 22px;
}

.panel-heading h3 {
    margin: 0;
    font-size: 16px;
}

.panel-heading p {
    color: var(--muted);
    margin: 7px 0 0;
    font-size: 12px;
}

.record-count {
    color: var(--cyan);
    border: 1px solid var(--border);
    background: rgba(66,245,233,.05);
    border-radius: 9px;
    padding: 9px 12px;
    font-size: 11px;
    white-space: nowrap;
}

.filters {
    padding: 18px;
    margin-bottom: 22px;
    border: 1px solid rgba(130,165,205,.14);
    border-radius: 13px;
    background: rgba(3,10,24,.38);
}

.filter-title {
    color: #dceaff;
    font-size: 12px;
    font-weight: 750;
    margin-bottom: 14px;
}

.filter-form {
    display: grid;
    grid-template-columns: minmax(200px,1fr) minmax(160px,220px) auto auto;
    align-items: end;
    gap: 11px;
}

.field label {
    display: block;
    margin-bottom: 8px;
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
}

.field input {
    width: 100%;
    min-height: 43px;
    padding: 11px 13px;
    color: var(--text);
    background: rgba(3,10,24,.78);
    border: 1px solid rgba(130,165,205,.23);
    border-radius: 9px;
    outline: none;
    font: inherit;
    font-size: 12px;
}

.field input:focus {
    border-color: var(--cyan);
}

.field input::placeholder {
    color: #627995;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    filter: invert(1);
}

.button {
    min-height: 43px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 15px;
    border: 1px solid transparent;
    border-radius: 9px;
    cursor: pointer;
    text-decoration: none;
    font: inherit;
    font-size: 11px;
    font-weight: 750;
    white-space: nowrap;
}

.button-primary {
    color: #03131d;
    background: linear-gradient(110deg, var(--cyan), #83ffe4);
}

.button-secondary {
    color: #dceaff;
    background: rgba(75,119,176,.12);
    border-color: rgba(120,167,220,.23);
}

.table-container {
    overflow-x: auto;
    border: 1px solid rgba(130,165,205,.15);
    border-radius: 12px;
}

table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
    text-align: left;
}

thead {
    background: rgba(66,245,233,.045);
}

th {
    padding: 15px 14px;
    color: #8da9c7;
    border-bottom: 1px solid var(--border);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    white-space: nowrap;
}

td {
    padding: 15px 14px;
    color: #c5d6e9;
    border-bottom: 1px solid rgba(130,165,205,.1);
    font-size: 12px;
    vertical-align: middle;
}

tbody tr:hover {
    background: rgba(66,245,233,.035);
}

tbody tr:last-child td {
    border-bottom: none;
}

.row-number {
    color: #7088a5;
}

.lecturer-id {
    color: var(--cyan);
    font-weight: 750;
    white-space: nowrap;
}

.lecturer-name {
    color: #f0f7ff;
    font-weight: 700;
}

.time-value {
    white-space: nowrap;
    color: #d5e5f6;
}

.status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 30px;
    font-size: 10px;
    font-weight: 750;
    white-space: nowrap;
}

.status-present {
    color: var(--green);
    background: rgba(69,240,177,.09);
    border: 1px solid rgba(69,240,177,.2);
}

.status-absent {
    color: var(--red);
    background: rgba(255,113,136,.08);
    border: 1px solid rgba(255,113,136,.2);
}

.status-other {
    color: var(--gold);
    background: rgba(255,212,119,.07);
    border: 1px solid rgba(255,212,119,.17);
}

.empty-state,
.error-state {
    padding: 45px 18px;
    text-align: center;
}

.empty-state h3,
.error-state h3 {
    margin: 0;
    font-size: 15px;
}

.empty-state p,
.error-state p {
    margin: 9px 0 0;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.7;
}

.error-state h3 {
    color: var(--red);
}

.bottom-links {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 22px;
}

.text-link {
    color: var(--cyan);
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

.footer {
    padding: 0 15px 28px;
    color: #627995;
    font-size: 11px;
    text-align: center;
    letter-spacing: .4px;
}

@media (max-width: 900px) {
    .filter-form {
        grid-template-columns: 1fr 1fr;
    }

    .filter-form .button {
        width: 100%;
    }
}

@media (max-width: 650px) {
    .topbar-inner {
        padding: 14px;
    }

    .brand h1 {
        max-width: 220px;
        font-size: 12px;
        line-height: 1.5;
    }

    .brand p,
    .live-status {
        display: none;
    }

    .brand-mark {
        width: 39px;
        height: 39px;
    }

    .page {
        width: calc(100% - 26px);
        margin: 25px auto;
    }

    .summary-grid {
        grid-template-columns: 1fr;
        gap: 11px;
    }

    .stat-card {
        padding: 17px;
    }

    .stat-number {
        font-size: 25px;
    }

    .panel {
        padding: 16px;
    }

    .panel-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .bottom-links {
        flex-direction: column;
    }
}
</style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">
        <div class="brand">
            <div class="brand-mark">U</div>
            <div>
                <h1>University Lecturer Attendance System</h1>
                <p>Attendance monitoring console</p>
            </div>
        </div>

        <div class="header-actions">
            <div class="live-status">
                <span class="dot"></span>
                Attendance module
            </div>
            <a class="nav-link" href="../admin/dashboard.php">← Dashboard</a>
        </div>
    </div>
</header>

<main class="page">

    <div class="breadcrumb">
        System / Attendance Management / <span>Attendance Records</span>
    </div>

    <section class="page-heading">
        <p class="eyebrow">Attendance management</p>
        <h2>Attendance Records</h2>
        <p>Review lecturer check-ins, departure times, and recorded attendance status.</p>
    </section>

    <section class="summary-grid">
        <div class="stat-card">
            <div>
                <div class="stat-label">Records found</div>
                <div class="stat-number"><?= (int)$totalRecords ?></div>
            </div>
            <div class="stat-icon">▤</div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-label">Present records</div>
                <div class="stat-number"><?= (int)$presentCount ?></div>
            </div>
            <div class="stat-icon">✓</div>
        </div>

        <div class="stat-card">
            <div>
                <div class="stat-label">Departure recorded</div>
                <div class="stat-number"><?= (int)$departureCount ?></div>
            </div>
            <div class="stat-icon">↗</div>
        </div>
    </section>

    <section class="panel">

        <div class="panel-heading">
            <div>
                <h3>Attendance database</h3>
                <p>Search by lecturer ID, name, department, or attendance date.</p>
            </div>

            <div class="record-count">
                <?= (int)$totalRecords ?> record(s)
            </div>
        </div>

        <div class="filters">
            <div class="filter-title">⌕ &nbsp; Filter attendance records</div>

            <form class="filter-form" method="GET" action="attendance.php">
                <div class="field">
                    <label for="lecturer">Lecturer search</label>
                    <input
                        type="text"
                        id="lecturer"
                        name="lecturer"
                        placeholder="ID, lecturer name or department"
                        value="<?= e($lecturerFilter) ?>"
                    >
                </div>

                <div class="field">
                    <label for="date">Attendance date</label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        value="<?= e($dateFilter) ?>"
                    >
                </div>

                <button type="submit" class="button button-primary">
                    Search records
                </button>

                <a href="attendance.php" class="button button-secondary">
                    Clear filters
                </a>
            </form>
        </div>

        <?php if ($queryError !== ""): ?>

            <div class="error-state">
                <h3>Unable to load attendance records</h3>
                <p><?= e($queryError) ?></p>
                <p>Connected database: <?= e($databaseName) ?></p>
                <p>
                    Check that the attendance table has the columns used by this page,
                    including Departure time.
                </p>
            </div>

        <?php elseif ($totalRecords > 0): ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Lecturer ID</th>
                            <th>Lecturer name</th>
                            <th>Department</th>
                            <th>Attendance date</th>
                            <th>Check-in time</th>
                            <th>Departure time</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($attendanceRecords as $index => $record): ?>
                        <?php
                        $rawStatus = trim(
                            (string)($record["attendance_status"] ?? "")
                        );

                        $normalizedStatus = strtolower($rawStatus);

                        if ($normalizedStatus === "present") {
                            $statusClass = "status-present";
                            $statusLabel = "✓ Present";
                        } elseif ($normalizedStatus === "absent") {
                            $statusClass = "status-absent";
                            $statusLabel = "× Absent";
                        } else {
                            $statusClass = "status-other";
                            $statusLabel = $rawStatus !== ""
                                ? $rawStatus
                                : "Unknown";
                        }
                        ?>

                        <tr>
                            <td class="row-number"><?= $index + 1 ?></td>

                            <td class="lecturer-id">
                                <?= e($record["lecturer_id"]) ?>
                            </td>

                            <td class="lecturer-name">
                                <?= e($record["lecturer_name"]) ?>
                            </td>

                            <td><?= e($record["department"]) ?></td>

                            <td><?= e($record["attendance_date"]) ?></td>

                            <td class="time-value">
                                <?= e(displayTime($record["attendance_time"])) ?>
                            </td>

                            <td class="time-value">
                                <?= e(displayTime($record["departure_time"])) ?>
                            </td>

                            <td>
                                <span class="status <?= e($statusClass) ?>">
                                    <?= e($statusLabel) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <div class="empty-state">
                <h3>No attendance records found</h3>
                <p>
                    <?php if ($lecturerFilter !== "" || $dateFilter !== ""): ?>
                        Try a different search or date, or clear filters to see all records.
                    <?php else: ?>
                        There are currently no attendance records in the connected database.
                    <?php endif; ?>
                </p>
            </div>

        <?php endif; ?>

        <p style="color:#93a9c4;font-size:11px;margin-top:18px;">
            Connected database: <?= e($databaseName) ?>
        </p>

    </section>

    <nav class="bottom-links">
        <a class="text-link" href="../admin/dashboard.php">← Back to dashboard</a>
        <a class="text-link" href="recognize.php">Open facial recognition →</a>
    </nav>

</main>

<footer class="footer">
    UNIVERSITY LECTURER ATTENDANCE SYSTEM · ATTENDANCE MONITORING MODULE
</footer>

</body>
</html>
