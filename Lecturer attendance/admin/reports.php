<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

date_default_timezone_set("Africa/Kampala");

/* =========================================
   REPORT FILTERS
========================================= */

$startDate = trim($_GET["start_date"] ?? "");
$endDate   = trim($_GET["end_date"] ?? "");
$search    = trim($_GET["search"] ?? "");

$message = "";
$records = [];
$totalRecords = 0;
$presentCount = 0;
$departureCount = 0;

/* Validate dates */

function validDate($date)
{
    if ($date === "") {
        return true;
    }

    $parsed = DateTime::createFromFormat("!Y-m-d", $date);

    return $parsed && $parsed->format("Y-m-d") === $date;
}

if (!validDate($startDate) || !validDate($endDate)) {
    $message = "Please enter valid dates.";
    $startDate = "";
    $endDate = "";
} elseif (
    $startDate !== "" &&
    $endDate !== "" &&
    $startDate > $endDate
) {
    $message = "The start date cannot be later than the end date.";
    $startDate = "";
    $endDate = "";
}

/* =========================================
   FETCH ATTENDANCE REPORT
========================================= */

if ($message === "") {

    $sql = "
        SELECT
            a.`ID`,
            a.`Lecturer id`,
            l.`Full name`,
            l.`Department`,
            a.`Attendance date`,
            a.`Attendance time`,
            a.`Departure time`,
            a.`Status`
        FROM attendance a
        LEFT JOIN lecturers l
            ON a.`Lecturer id` = l.`Lecturer id`
        WHERE 1 = 1
    ";

    $types = "";
    $params = [];

    if ($startDate !== "") {
        $sql .= " AND a.`Attendance date` >= ?";
        $types .= "s";
        $params[] = $startDate;
    }

    if ($endDate !== "") {
        $sql .= " AND a.`Attendance date` <= ?";
        $types .= "s";
        $params[] = $endDate;
    }

    if ($search !== "") {
        $sql .= "
            AND (
                a.`Lecturer id` LIKE ?
                OR l.`Full name` LIKE ?
                OR l.`Department` LIKE ?
            )
        ";

        $types .= "sss";
        $searchValue = "%" . $search . "%";

        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
    }

    $sql .= "
        ORDER BY
            a.`Attendance date` DESC,
            a.`Attendance time` DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        if (!empty($params)) {
            $bindArguments = [$stmt, $types];

            foreach ($params as $key => $value) {
                $bindArguments[] = &$params[$key];
            }

            call_user_func_array(
                "mysqli_stmt_bind_param",
                $bindArguments
            );
        }

        if (mysqli_stmt_execute($stmt)) {

            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $records[] = $row;

                if (strtolower(trim($row["Status"] ?? "")) === "present") {
                    $presentCount++;
                }

                if (!empty($row["Departure time"])) {
                    $departureCount++;
                }
            }

            $totalRecords = count($records);

        } else {
            $message = "Unable to retrieve attendance records.";
        }

        mysqli_stmt_close($stmt);

    } else {
        $message = "Unable to prepare the attendance report.";
    }
}

/* =========================================
   DISPLAY HELPERS
========================================= */

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function displayTime($value)
{
    if (empty($value)) {
        return "—";
    }

    $timestamp = strtotime($value);

    return $timestamp !== false
        ? date("h:i A", $timestamp)
        : $value;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Attendance Reports | Lecturer Attendance System</title>

    <style>
        :root {
            --bg: #050b18;
            --panel: rgba(13, 25, 47, 0.94);
            --panel-light: #101f38;
            --cyan: #00e5ff;
            --cyan-soft: rgba(0, 229, 255, 0.12);
            --blue: #367cff;
            --green: #00e6a0;
            --gold: #ffc857;
            --text: #eaf6ff;
            --muted: #8fa7c5;
            --border: rgba(0, 229, 255, 0.19);
            --red: #ff7185;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(ellipse at 12% 0%, rgba(0, 229, 255, .12), transparent 36%),
                radial-gradient(ellipse at 90% 20%, rgba(54, 124, 255, .12), transparent 32%),
                var(--bg);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            opacity: .25;
            background-image:
                linear-gradient(rgba(0, 229, 255, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 229, 255, .045) 1px, transparent 1px);
            background-size: 38px 38px;
        }

        a {
            color: inherit;
        }

        .topbar {
            position: relative;
            border-bottom: 1px solid var(--border);
            background: rgba(5, 11, 24, .91);
            box-shadow: 0 8px 35px rgba(0, 0, 0, .25);
        }

        .topbar-inner {
            width: min(1380px, calc(100% - 40px));
            min-height: 84px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(0, 229, 255, .5);
            border-radius: 13px;
            color: var(--cyan);
            background: linear-gradient(145deg, #142b48, #07101f);
            box-shadow: 0 0 22px rgba(0, 229, 255, .12),
                        inset 0 0 15px rgba(0, 229, 255, .06);
            font-size: 20px;
        }

        .brand small {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-links a {
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: rgba(12, 27, 49, .8);
            color: #cce8ff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            transition: .2s ease;
        }

        .nav-links a:hover {
            border-color: var(--cyan);
            color: var(--cyan);
            box-shadow: 0 0 16px rgba(0, 229, 255, .12);
            transform: translateY(-2px);
        }

        .page {
            width: min(1380px, calc(100% - 40px));
            margin: 34px auto 50px;
        }

        .intro {
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: linear-gradient(120deg, rgba(14, 31, 56, .97), rgba(7, 17, 34, .94));
            box-shadow: 0 15px 40px rgba(0, 0, 0, .2),
                        inset 0 1px rgba(255, 255, 255, .025);
        }

        .intro::after {
            content: "";
            position: absolute;
            top: -100px;
            right: -40px;
            width: 260px;
            height: 260px;
            border: 1px solid rgba(0, 229, 255, .15);
            border-radius: 50%;
            box-shadow: 0 0 0 25px rgba(0, 229, 255, .025),
                        0 0 0 55px rgba(0, 229, 255, .02);
            pointer-events: none;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--cyan);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 12px var(--green);
        }

        .intro h1 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-size: clamp(26px, 4vw, 37px);
            letter-spacing: -.7px;
        }

        .intro p {
            position: relative;
            z-index: 1;
            max-width: 700px;
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .card {
            margin-bottom: 22px;
            padding: 24px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(14, 28, 51, .96), rgba(8, 18, 35, .98));
            box-shadow: 0 15px 35px rgba(0, 0, 0, .2),
                        inset 0 1px rgba(255, 255, 255, .025);
        }

        .card-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }

        .card-heading h2 {
            margin: 0;
            font-size: 16px;
        }

        .section-tag {
            color: var(--cyan);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: #bdd0e6;
            font-size: 11px;
            font-weight: 750;
        }

        .field input {
            width: 100%;
            min-height: 46px;
            padding: 11px 13px;
            border: 1px solid rgba(143, 167, 197, .25);
            border-radius: 9px;
            outline: none;
            background: #071326;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            color-scheme: dark;
            transition: .2s ease;
        }

        .field input::placeholder {
            color: #647c9a;
        }

        .field input:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(0, 229, 255, .09);
        }

        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 11px;
            margin-top: 20px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 43px;
            padding: 11px 16px;
            border: 1px solid transparent;
            border-radius: 9px;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            transition: .2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .primary {
            border-color: rgba(0, 229, 255, .5);
            background: linear-gradient(110deg, #007eaa, #00a8b8);
            color: white;
            box-shadow: 0 5px 18px rgba(0, 200, 235, .12);
        }

        .primary:hover {
            box-shadow: 0 7px 22px rgba(0, 229, 255, .2);
        }

        .secondary {
            border-color: var(--border);
            background: #10213a;
            color: #c9ddf4;
        }

        .secondary:hover {
            border-color: var(--cyan);
            color: var(--cyan);
        }

        .print-button {
            border-color: rgba(54, 124, 255, .45);
            background: linear-gradient(110deg, #183f86, #245fbb);
            color: white;
        }

        .summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
        }

        .summary h2 {
            margin: 0;
            font-size: 17px;
        }

        .count {
            padding: 9px 13px;
            border: 1px solid rgba(0, 229, 255, .25);
            border-radius: 9px;
            background: var(--cyan-soft);
            color: var(--cyan);
            font-size: 12px;
            font-weight: 800;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .stat {
            position: relative;
            overflow: hidden;
            min-height: 102px;
            padding: 17px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(14, 35, 60, .95), rgba(8, 19, 37, .95));
        }

        .stat::after {
            content: "";
            position: absolute;
            right: -18px;
            bottom: -25px;
            width: 85px;
            height: 85px;
            border: 1px solid rgba(0, 229, 255, .11);
            border-radius: 50%;
            box-shadow: 0 0 0 12px rgba(0, 229, 255, .025);
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
        }

        .stat-value {
            margin-top: 11px;
            color: var(--cyan);
            font-size: 27px;
            font-weight: 850;
        }

        .stat.present .stat-value {
            color: var(--green);
        }

        .stat.departures .stat-value {
            color: var(--gold);
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 11px;
        }

        table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #101f37;
        }

        th {
            padding: 15px 13px;
            border-bottom: 1px solid var(--border);
            color: #91abc9;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 15px 13px;
            border-bottom: 1px solid rgba(143, 167, 197, .10);
            color: #d8e7f7;
            font-size: 12px;
            white-space: nowrap;
        }

        tbody tr {
            transition: background .18s ease;
        }

        tbody tr:hover {
            background: rgba(0, 229, 255, .045);
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .lecturer-id {
            color: var(--cyan);
            font-weight: 800;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid rgba(0, 230, 160, .23);
            border-radius: 20px;
            background: rgba(0, 230, 160, .09);
            color: var(--green);
            font-size: 10px;
            font-weight: 800;
        }

        .status::before {
            content: "";
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .status.other {
            border-color: rgba(143, 167, 197, .2);
            background: rgba(143, 167, 197, .08);
            color: #a9bdd5;
        }

        .notice {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid rgba(255, 200, 87, .3);
            border-radius: 10px;
            background: rgba(255, 200, 87, .08);
            color: var(--gold);
            font-size: 13px;
            line-height: 1.6;
        }

        .empty {
            padding: 48px 15px;
            color: var(--muted);
            text-align: center;
            font-size: 13px;
        }

        .empty-icon {
            display: block;
            margin-bottom: 12px;
            color: var(--cyan);
            font-size: 30px;
        }

        .report-footer {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(143, 167, 197, .13);
            color: #8298b3;
            font-size: 10px;
            line-height: 1.7;
        }

        @media (max-width: 800px) {
            .topbar-inner {
                align-items: flex-start;
                flex-direction: column;
                padding: 17px 0;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .page {
                width: calc(100% - 24px);
                margin-top: 20px;
            }

            .intro,
            .card {
                padding: 18px;
            }

            .intro::after {
                opacity: .4;
            }
        }

        @media print {
            @page {
                size: landscape;
                margin: 12mm;
            }

            body {
                background: white !important;
                color: black !important;
                font-size: 10pt;
            }

            body::before,
            .topbar,
            .filters-card,
            .no-print {
                display: none !important;
            }

            .page {
                width: 100%;
                margin: 0;
            }

            .intro {
                margin-bottom: 14px;
                padding: 0 0 12px;
                border: 0;
                border-bottom: 2px solid #333;
                background: white;
                box-shadow: none;
            }

            .intro::after,
            .eyebrow {
                display: none;
            }

            .intro h1 {
                color: black;
                font-size: 21pt;
            }

            .intro p {
                color: #333;
            }

            .card {
                margin: 0;
                padding: 0;
                border: 0;
                background: white;
                box-shadow: none;
            }

            .summary {
                margin: 12px 0;
            }

            .summary h2 {
                color: black;
            }

            .count {
                border: 1px solid #999;
                background: white;
                color: black;
            }

            .stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                margin-bottom: 12px;
            }

            .stat {
                min-height: 65px;
                padding: 9px;
                border: 1px solid #aaa;
                background: white;
            }

            .stat::after {
                display: none;
            }

            .stat-label,
            .stat-value {
                color: black !important;
            }

            .stat-value {
                margin-top: 5px;
                font-size: 17pt;
            }

            .table-wrap {
                overflow: visible;
                border: 1px solid #999;
            }

            table {
                width: 100%;
                min-width: 0;
            }

            thead {
                display: table-header-group;
                background: #eee;
            }

            th,
            td {
                padding: 7px 5px;
                border: 1px solid #ccc;
                color: black;
                font-size: 8pt;
                white-space: normal;
            }

            tr {
                break-inside: avoid;
            }

            .lecturer-id {
                color: black;
            }

            .status,
            .status.other {
                padding: 0;
                border: 0;
                background: white;
                color: black;
            }

            .status::before {
                display: none;
            }

            .report-footer {
                border-top: 1px solid #aaa;
                color: #333;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">

        <div class="brand">
            <div class="brand-icon">⌘</div>
            <div>
                University Lecturer Attendance System
                <small>Reporting and monitoring module</small>
            </div>
        </div>

        <nav class="nav-links no-print">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../lecturer/attendance.php">All Records</a>
        </nav>

    </div>
</header>

<main class="page">

    <section class="intro">
        <div class="eyebrow">System reporting module</div>
        <h1>Attendance Reports</h1>
        <p>
            Monitor lecturer attendance, review check-in and departure times,
            filter records by date or lecturer, and generate a printable report.
        </p>
    </section>

    <section class="card filters-card">

        <div class="card-heading">
            <h2>Filter attendance records</h2>
            <span class="section-tag">Search controls</span>
        </div>

        <form method="GET" action="reports.php">

            <div class="filters">

                <div class="field">
                    <label for="start_date">START DATE</label>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?php echo e($startDate); ?>"
                    >
                </div>

                <div class="field">
                    <label for="end_date">END DATE</label>
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?php echo e($endDate); ?>"
                    >
                </div>

                <div class="field">
                    <label for="search">LECTURER ID, NAME OR DEPARTMENT</label>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        placeholder="e.g. L003, Stewy, Law"
                        value="<?php echo e($search); ?>"
                    >
                </div>

            </div>

            <div class="filter-actions">
                <button class="button primary" type="submit">
                    ⌕ Apply Filters
                </button>

                <a class="button secondary" href="reports.php">
                    ↺ Clear Filters
                </a>

                <button
                    class="button print-button"
                    type="button"
                    onclick="window.print()"
                >
                    ▤ Print Report
                </button>
            </div>

        </form>

    </section>

    <section class="card">

        <div class="summary">
            <h2>Attendance results</h2>

            <span class="count">
                <?php echo (int)$totalRecords; ?> RECORD(S)
            </span>
        </div>

        <?php if ($message !== ""): ?>

            <div class="notice">
                <?php echo e($message); ?>
            </div>

        <?php else: ?>

            <div class="stats">

                <div class="stat">
                    <div class="stat-label">TOTAL RECORDS</div>
                    <div class="stat-value">
                        <?php echo (int)$totalRecords; ?>
                    </div>
                </div>

                <div class="stat present">
                    <div class="stat-label">PRESENT RECORDS</div>
                    <div class="stat-value">
                        <?php echo (int)$presentCount; ?>
                    </div>
                </div>

                <div class="stat departures">
                    <div class="stat-label">DEPARTURE RECORDED</div>
                    <div class="stat-value">
                        <?php echo (int)$departureCount; ?>
                    </div>
                </div>

            </div>

            <?php if ($totalRecords > 0): ?>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Lecturer ID</th>
                                <th>Lecturer Name</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($records as $record): ?>

                                <?php
                                $statusValue = $record["Status"] ?? "";

                                $statusClass =
                                    strtolower(trim($statusValue)) === "present"
                                    ? "status"
                                    : "status other";
                                ?>

                                <tr>
                                    <td class="lecturer-id">
                                        <?php echo e($record["Lecturer id"] ?? ""); ?>
                                    </td>

                                    <td>
                                        <?php echo e($record["Full name"] ?? "Unknown"); ?>
                                    </td>

                                    <td>
                                        <?php echo e($record["Department"] ?? "Unknown"); ?>
                                    </td>

                                    <td>
                                        <?php echo e($record["Attendance date"] ?? ""); ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo e(
                                            displayTime(
                                                $record["Attendance time"] ?? ""
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo e(
                                            displayTime(
                                                $record["Departure time"] ?? ""
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <span class="<?php echo $statusClass; ?>">
                                            <?php echo e($statusValue ?: "Unknown"); ?>
                                        </span>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="empty">
                    <span class="empty-icon">⌕</span>
                    <strong>No matching attendance records</strong>
                    <p>Try changing the dates or lecturer search term.</p>
                </div>

            <?php endif; ?>

        <?php endif; ?>

        <div class="report-footer">
            <span>
                Generated on <?php echo date("F j, Y"); ?>
                at <?php echo date("h:i A"); ?> (Kampala time).
            </span>

            <span>
                Attendance records reflect data saved in the system.
                They do not automatically establish absence from a timetable.
            </span>
        </div>

    </section>

</main>

</body>
</html>
