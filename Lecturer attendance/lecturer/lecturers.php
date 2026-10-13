<?php
require_once __DIR__ . "/../config/database.php";

$sql = "SELECT
            `Lecturer id`,
            `Full name`,
            `Department`,
            `Email`,
            `Phone`,
            `Face_data`
        FROM lecturers
        ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

$lecturers = [];
$dbError = false;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $lecturers[] = $row;
    }
} else {
    $dbError = true;
    error_log("Lecturer directory query failed: " . mysqli_error($conn));
}

$totalLecturers = count($lecturers);

function h($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function initials($name): string
{
    $name = trim((string) $name);

    if ($name === "") {
        return "L";
    }

    $parts = preg_split('/\s+/', $name);
    $result = "";

    foreach (array_slice($parts, 0, 2) as $part) {
        $result .= function_exists("mb_substr")
            ? mb_substr($part, 0, 1, "UTF-8")
            : substr($part, 0, 1);
    }

    return strtoupper($result);
}

$registeredFaces = 0;

foreach ($lecturers as $lecturer) {
    if (!empty($lecturer["Face_data"])) {
        $registeredFaces++;
    }
}

$missingFaces = $totalLecturers - $registeredFaces;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#07111f">

    <title>Lecturer Directory | University Attendance System</title>

    <style>
        :root {
            --bg: #050b14;
            --bg-soft: #091525;
            --panel: rgba(13, 27, 46, 0.82);
            --panel-solid: #0d1b2e;
            --panel-hover: rgba(17, 39, 61, 0.94);
            --cyan: #42f5e6;
            --cyan-soft: #9afff5;
            --blue: #529dff;
            --green: #55f2a3;
            --amber: #ffc86b;
            --red: #ff7185;
            --text: #edf8ff;
            --muted: #8ea5bb;
            --line: rgba(124, 177, 211, 0.15);
            --shadow: 0 20px 55px rgba(0, 0, 0, 0.28);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--text);
            background:
                radial-gradient(
                    ellipse at 8% 0%,
                    rgba(29, 122, 157, 0.19),
                    transparent 34rem
                ),
                radial-gradient(
                    ellipse at 95% 18%,
                    rgba(43, 89, 174, 0.14),
                    transparent 30rem
                ),
                linear-gradient(145deg, #050b14, #081322 52%, #050b14);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            opacity: 0.22;
            background-image:
                linear-gradient(rgba(88, 163, 194, 0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(88, 163, 194, 0.045) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(to bottom, black, transparent 85%);
        }

        a {
            -webkit-tap-highlight-color: transparent;
        }

        a:focus-visible,
        input:focus-visible,
        button:focus-visible {
            outline: 2px solid var(--cyan);
            outline-offset: 4px;
        }

        .topbar {
            position: relative;
            z-index: 5;
            background: rgba(5, 12, 23, 0.86);
            border-bottom: 1px solid rgba(66, 245, 230, 0.2);
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.18);
            backdrop-filter: blur(20px);
        }

        .topbar::after {
            content: "";
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 35%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            opacity: 0.8;
        }

        .topbar-inner {
            width: min(1320px, calc(100% - 48px));
            min-height: 82px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            position: relative;
            width: 47px;
            height: 47px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            color: var(--cyan);
            background: linear-gradient(
                145deg,
                rgba(66, 245, 230, 0.17),
                rgba(36, 91, 131, 0.12)
            );
            border: 1px solid rgba(66, 245, 230, 0.43);
            border-radius: 15px;
            box-shadow:
                inset 0 0 16px rgba(66, 245, 230, 0.08),
                0 0 22px rgba(66, 245, 230, 0.09);
            font-size: 22px;
            font-weight: 900;
        }

        .brand-mark::after {
            content: "";
            position: absolute;
            inset: 5px;
            border: 1px solid rgba(66, 245, 230, 0.13);
            border-radius: 10px;
        }

        .brand h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.015em;
        }

        .brand p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .header-link {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 11px 15px;
            color: var(--cyan-soft);
            background: rgba(66, 245, 230, 0.06);
            border: 1px solid rgba(66, 245, 230, 0.22);
            border-radius: 11px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 750;
            transition: 180ms ease;
        }

        .header-link:hover {
            color: #06131d;
            background: var(--cyan);
            border-color: var(--cyan);
            box-shadow: 0 0 25px rgba(66, 245, 230, 0.18);
            transform: translateY(-2px);
        }

        .page {
            width: min(1320px, calc(100% - 48px));
            margin: 36px auto 42px;
        }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 25px;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 11px;
            color: var(--cyan);
            font-size: 10px;
            font-weight: 850;
            letter-spacing: 0.19em;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 22px;
            height: 2px;
            background: var(--cyan);
            box-shadow: 0 0 10px rgba(66, 245, 230, 0.75);
        }

        .page-heading h2 {
            margin: 0;
            font-size: clamp(27px, 3vw, 37px);
            line-height: 1.18;
            letter-spacing: -0.045em;
            font-weight: 850;
        }

        .page-heading h2 span {
            color: var(--cyan);
            text-shadow: 0 0 28px rgba(66, 245, 230, 0.18);
        }

        .page-heading p.description {
            max-width: 600px;
            margin: 10px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .button {
            position: relative;
            display: inline-flex;
            min-height: 45px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 18px;
            overflow: hidden;
            color: #04151b;
            background: linear-gradient(110deg, #42f5e6, #80ffe9);
            border: 1px solid rgba(154, 255, 245, 0.75);
            border-radius: 11px;
            box-shadow:
                0 8px 24px rgba(66, 245, 230, 0.12),
                inset 0 1px rgba(255, 255, 255, 0.55);
            text-decoration: none;
            font-size: 12px;
            font-weight: 850;
            white-space: nowrap;
            transition: 180ms ease;
        }

        .button:hover {
            box-shadow:
                0 12px 32px rgba(66, 245, 230, 0.25),
                0 0 22px rgba(66, 245, 230, 0.13);
            transform: translateY(-3px);
        }

        .button .plus {
            font-size: 20px;
            line-height: 12px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 22px;
        }

        .summary-card {
            position: relative;
            min-height: 132px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 21px;
            overflow: hidden;
            background:
                linear-gradient(140deg, rgba(20, 43, 66, 0.9), rgba(9, 22, 38, 0.94));
            border: 1px solid var(--line);
            border-radius: 17px;
            box-shadow: var(--shadow);
            transition: 220ms ease;
            isolation: isolate;
        }

        .summary-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 18px;
            right: 18px;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(66, 245, 230, 0.65),
                transparent
            );
            opacity: 0.65;
        }

        .summary-card::after {
            content: "";
            position: absolute;
            z-index: -1;
            right: -35px;
            bottom: -65px;
            width: 140px;
            height: 140px;
            background: rgba(66, 245, 230, 0.045);
            border: 1px solid rgba(66, 245, 230, 0.08);
            border-radius: 50%;
            box-shadow: 0 0 0 17px rgba(66, 245, 230, 0.018);
        }

        .summary-card:hover {
            border-color: rgba(66, 245, 230, 0.35);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3),
                        0 0 24px rgba(66, 245, 230, 0.055);
            transform: translateY(-3px);
        }

        .summary-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.11em;
            text-transform: uppercase;
        }

        .summary-number {
            margin-top: 10px;
            color: var(--text);
            font-size: 34px;
            line-height: 1;
            font-weight: 850;
            letter-spacing: -0.05em;
        }

        .summary-note {
            margin-top: 9px;
            color: #8098ad;
            font-size: 10px;
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            color: var(--cyan);
            background: rgba(66, 245, 230, 0.08);
            border: 1px solid rgba(66, 245, 230, 0.2);
            border-radius: 15px;
            box-shadow: inset 0 0 15px rgba(66, 245, 230, 0.035);
            font-size: 22px;
        }

        .summary-card.green .summary-icon {
            color: var(--green);
            background: rgba(85, 242, 163, 0.08);
            border-color: rgba(85, 242, 163, 0.22);
        }

        .summary-card.amber .summary-icon {
            color: var(--amber);
            background: rgba(255, 200, 107, 0.08);
            border-color: rgba(255, 200, 107, 0.22);
        }

        .table-card {
            overflow: hidden;
            background: linear-gradient(
                150deg,
                rgba(13, 27, 46, 0.94),
                rgba(7, 18, 32, 0.96)
            );
            border: 1px solid rgba(124, 177, 211, 0.17);
            border-radius: 19px;
            box-shadow: var(--shadow);
        }

        .table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 23px 24px;
            border-bottom: 1px solid var(--line);
        }

        .table-heading h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.015em;
        }

        .table-heading p {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .directory-count {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 11px;
            color: var(--cyan-soft);
            background: rgba(66, 245, 230, 0.06);
            border: 1px solid rgba(66, 245, 230, 0.16);
            border-radius: 9px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .directory-count .dot {
            width: 6px;
            height: 6px;
            background: var(--cyan);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(66, 245, 230, 0.8);
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px 22px;
            background: rgba(3, 11, 21, 0.28);
            border-bottom: 1px solid var(--line);
        }

        .search-box {
            position: relative;
            width: min(370px, 100%);
        }

        .search-box .search-icon {
            position: absolute;
            top: 50%;
            left: 13px;
            color: #8ba9bd;
            font-size: 16px;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .search-box input {
            width: 100%;
            min-height: 41px;
            padding: 10px 13px 10px 39px;
            color: var(--text);
            background: rgba(5, 15, 27, 0.78);
            border: 1px solid rgba(124, 177, 211, 0.19);
            border-radius: 10px;
            outline: none;
            font-family: inherit;
            font-size: 12px;
            transition: 180ms ease;
        }

        .search-box input::placeholder {
            color: #71899e;
        }

        .search-box input:focus {
            border-color: rgba(66, 245, 230, 0.55);
            box-shadow: 0 0 0 3px rgba(66, 245, 230, 0.06);
        }

        .search-result {
            color: #8da6ba;
            font-size: 11px;
            white-space: nowrap;
        }

        .table-container {
            overflow-x: auto;
            scrollbar-color: #25465d #091525;
            scrollbar-width: thin;
        }

        table {
            width: 100%;
            min-width: 920px;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
        }

        thead {
            background: rgba(8, 21, 36, 0.94);
        }

        th {
            padding: 15px 16px;
            color: #86a3b8;
            border-bottom: 1px solid rgba(124, 177, 211, 0.16);
            font-size: 9px;
            font-weight: 850;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 15px 16px;
            color: #b5c9d9;
            border-bottom: 1px solid rgba(124, 177, 211, 0.085);
            font-size: 11px;
            vertical-align: middle;
        }

        tbody tr {
            transition: background 160ms ease;
        }

        tbody tr:hover {
            background: rgba(66, 245, 230, 0.035);
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .lecturer-profile {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 180px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            color: var(--cyan-soft);
            background: linear-gradient(
                145deg,
                rgba(66, 245, 230, 0.15),
                rgba(67, 117, 190, 0.16)
            );
            border: 1px solid rgba(66, 245, 230, 0.23);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 850;
            box-shadow: inset 0 0 12px rgba(66, 245, 230, 0.035);
        }

        .lecturer-name {
            color: #f0f8ff;
            font-size: 12px;
            font-weight: 750;
            line-height: 1.45;
        }

        .lecturer-id {
            display: inline-block;
            padding: 6px 8px;
            color: var(--cyan-soft);
            background: rgba(66, 245, 230, 0.055);
            border: 1px solid rgba(66, 245, 230, 0.13);
            border-radius: 7px;
            font-family: Consolas, "Courier New", monospace;
            font-size: 10px;
            font-weight: 750;
            white-space: nowrap;
        }

        .department {
            display: inline-block;
            padding: 6px 9px;
            color: #a8c5ff;
            background: rgba(82, 157, 255, 0.08);
            border: 1px solid rgba(82, 157, 255, 0.15);
            border-radius: 7px;
            white-space: nowrap;
        }

        .contact {
            max-width: 220px;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .face-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 9px;
            color: var(--green);
            background: rgba(85, 242, 163, 0.075);
            border: 1px solid rgba(85, 242, 163, 0.18);
            border-radius: 8px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .face-status .status-dot {
            width: 6px;
            height: 6px;
            background: var(--green);
            border-radius: 50%;
            box-shadow: 0 0 9px rgba(85, 242, 163, 0.7);
        }

        .face-missing {
            color: var(--amber);
            background: rgba(255, 200, 107, 0.07);
            border-color: rgba(255, 200, 107, 0.19);
        }

        .face-missing .status-dot {
            background: var(--amber);
            box-shadow: 0 0 9px rgba(255, 200, 107, 0.55);
        }

        .no-data {
            padding: 55px 20px;
            text-align: center;
        }

        .no-data-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            display: grid;
            place-items: center;
            color: var(--cyan);
            background: rgba(66, 245, 230, 0.07);
            border: 1px solid rgba(66, 245, 230, 0.18);
            border-radius: 20px;
            font-size: 28px;
        }

        .no-data h3 {
            margin: 0;
            color: var(--text);
            font-size: 16px;
        }

        .no-data p {
            margin: 9px 0 21px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .db-warning {
            margin: 20px;
            padding: 15px;
            color: #ffd5db;
            background: rgba(255, 113, 133, 0.08);
            border: 1px solid rgba(255, 113, 133, 0.25);
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.6;
        }

        .no-results {
            display: none;
            padding: 35px 18px;
            color: var(--muted);
            text-align: center;
            font-size: 12px;
        }

        .no-results.visible {
            display: block;
        }

        .bottom-links {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 23px;
        }

        .text-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #91c9d0;
            text-decoration: none;
            font-size: 11px;
            font-weight: 750;
            transition: 160ms ease;
        }

        .text-link:hover {
            color: var(--cyan);
            text-shadow: 0 0 15px rgba(66, 245, 230, 0.2);
        }

        .footer {
            padding: 20px 15px 28px;
            color: #627b90;
            border-top: 1px solid rgba(124, 177, 211, 0.08);
            font-size: 10px;
            letter-spacing: 0.035em;
            text-align: center;
        }

        .footer span {
            color: #7fb8bd;
        }

        @media (max-width: 800px) {
            .summary-grid {
                gap: 12px;
            }

            .summary-card {
                padding: 17px;
            }

            .summary-number {
                font-size: 29px;
            }

            .summary-icon {
                width: 41px;
                height: 41px;
                font-size: 19px;
            }

            .page-heading {
                align-items: flex-start;
            }
        }

        @media (max-width: 620px) {
            .topbar-inner {
                width: calc(100% - 28px);
                min-height: 73px;
                gap: 10px;
            }

            .brand {
                gap: 10px;
            }

            .brand-mark {
                width: 39px;
                height: 39px;
                border-radius: 12px;
                font-size: 18px;
            }

            .brand h1 {
                max-width: 205px;
                font-size: 12px;
                line-height: 1.4;
            }

            .brand p {
                font-size: 9px;
            }

            .header-link {
                gap: 5px;
                padding: 9px;
                font-size: 10px;
                white-space: nowrap;
            }

            .page {
                width: calc(100% - 28px);
                margin: 27px auto 32px;
            }

            .page-heading {
                flex-direction: column;
                gap: 17px;
            }

            .page-heading h2 {
                font-size: 29px;
            }

            .page-heading p.description {
                font-size: 12px;
            }

            .button {
                min-height: 42px;
                padding: 11px 14px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
                gap: 11px;
            }

            .summary-card {
                min-height: 105px;
                padding: 17px 19px;
            }

            .summary-number {
                margin-top: 7px;
                font-size: 30px;
            }

            .summary-note {
                margin-top: 6px;
            }

            .table-heading {
                align-items: flex-start;
                padding: 19px 17px;
            }

            .directory-count {
                padding: 7px 8px;
                font-size: 9px;
            }

            .toolbar {
                align-items: flex-start;
                flex-direction: column;
                padding: 15px 16px;
            }

            .search-box {
                width: 100%;
            }

            .search-result {
                white-space: normal;
            }

            .bottom-links {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">

        <div class="brand">
            <div class="brand-mark" aria-hidden="true">U</div>

            <div>
                <h1>University Lecturer Attendance System</h1>
                <p>Lecturer management / Directory</p>
            </div>
        </div>

        <a class="header-link" href="../admin/dashboard.php">
            <span aria-hidden="true">←</span>
            Dashboard
        </a>

    </div>
</header>


<main class="page">

    <section class="page-heading">

        <div>
            <p class="eyebrow">Management console</p>

            <h2>Lecturer <span>Directory</span></h2>

            <p class="description">
                Manage your lecturer records, review contact information,
                and monitor facial recognition registration status.
            </p>
        </div>

        <a class="button" href="register.php">
            <span class="plus" aria-hidden="true">＋</span>
            Register lecturer
        </a>

    </section>


    <section class="summary-grid" aria-label="Lecturer statistics">

        <article class="summary-card">

            <div>
                <div class="summary-label">Total lecturers</div>

                <div class="summary-number">
                    <?php echo (int) $totalLecturers; ?>
                </div>

                <div class="summary-note">
                    Registered lecturer records
                </div>
            </div>

            <div class="summary-icon" aria-hidden="true">♙</div>

        </article>


        <article class="summary-card green">

            <div>
                <div class="summary-label">Face registered</div>

                <div class="summary-number">
                    <?php echo (int) $registeredFaces; ?>
                </div>

                <div class="summary-note">
                    Ready for facial matching
                </div>
            </div>

            <div class="summary-icon" aria-hidden="true">✓</div>

        </article>


        <article class="summary-card amber">

            <div>
                <div class="summary-label">Pending face data</div>

                <div class="summary-number">
                    <?php echo (int) $missingFaces; ?>
                </div>

                <div class="summary-note">
                    Records without saved face data
                </div>
            </div>

            <div class="summary-icon" aria-hidden="true">⌁</div>

        </article>

    </section>


    <section class="table-card" aria-labelledby="lecturers-heading">

        <div class="table-heading">

            <div>
                <h3 id="lecturers-heading">Lecturer database</h3>

                <p>
                    Lecturer identities, departments, contact details,
                    and biometric registration status.
                </p>
            </div>

            <div class="directory-count">
                <span class="dot" aria-hidden="true"></span>
                DIRECTORY
            </div>

        </div>


        <?php if ($dbError): ?>

            <div class="db-warning" role="alert">
                <strong>Database query failed.</strong>
                The lecturer records could not be loaded. Please check
                your database connection and table structure.
                The technical error has been written to the PHP error log.
            </div>

        <?php else: ?>

            <?php if ($totalLecturers > 0): ?>

                <div class="toolbar">

                    <div class="search-box">
                        <span class="search-icon" aria-hidden="true">⌕</span>

                        <input
                            type="search"
                            id="lecturerSearch"
                            placeholder="Search by name, ID, department, email..."
                            autocomplete="off"
                            aria-label="Search lecturers"
                        >
                    </div>

                    <div class="search-result" id="searchResult" aria-live="polite">
                        Showing <?php echo (int) $totalLecturers; ?> lecturers
                    </div>

                </div>


                <div class="table-container">

                    <table id="lecturersTable">

                        <thead>
                            <tr>
                                <th>Lecturer ID</th>
                                <th>Lecturer profile</th>
                                <th>Department</th>
                                <th>Email address</th>
                                <th>Phone number</th>
                                <th>Face status</th>
                            </tr>
                        </thead>


                        <tbody>

                            <?php foreach ($lecturers as $lecturer): ?>

                                <tr class="lecturer-row">

                                    <td>
                                        <span class="lecturer-id">
                                            <?php echo h($lecturer["Lecturer id"] ?? ""); ?>
                                        </span>
                                    </td>


                                    <td>
                                        <div class="lecturer-profile">

                                            <div class="avatar" aria-hidden="true">
                                                <?php echo h(initials($lecturer["Full name"] ?? "")); ?>
                                            </div>

                                            <div class="lecturer-name">
                                                <?php echo h($lecturer["Full name"] ?? ""); ?>
                                            </div>

                                        </div>
                                    </td>


                                    <td>
                                        <span class="department">
                                            <?php echo h($lecturer["Department"] ?? ""); ?>
                                        </span>
                                    </td>


                                    <td class="contact">
                                        <?php echo h($lecturer["Email"] ?? ""); ?>
                                    </td>


                                    <td>
                                        <?php echo h($lecturer["Phone"] ?? ""); ?>
                                    </td>


                                    <td>

                                        <?php if (!empty($lecturer["Face_data"])): ?>

                                            <span class="face-status">
                                                <span class="status-dot" aria-hidden="true"></span>
                                                Face registered
                                            </span>

                                        <?php else: ?>

                                            <span class="face-status face-missing">
                                                <span class="status-dot" aria-hidden="true"></span>
                                                Not registered
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <div class="no-results" id="noResults">
                    No lecturers match your search.
                    Try another name, lecturer ID, or department.
                </div>


            <?php else: ?>

                <div class="no-data">

                    <div class="no-data-icon" aria-hidden="true">♙</div>

                    <h3>No lecturers registered yet</h3>

                    <p>
                        Your lecturer directory is currently empty.
                        Register a lecturer to begin building your database.
                    </p>

                    <a class="button" href="register.php">
                        <span class="plus" aria-hidden="true">＋</span>
                        Register first lecturer
                    </a>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </section>


    <nav class="bottom-links" aria-label="Page navigation">

        <a class="text-link" href="register.php">
            <span aria-hidden="true">＋</span>
            Register another lecturer
        </a>

        <a class="text-link" href="../admin/dashboard.php">
            Back to dashboard
            <span aria-hidden="true">→</span>
        </a>

    </nav>

</main>


<footer class="footer">
    UNIVERSITY LECTURER ATTENDANCE MANAGEMENT SYSTEM
    <br><br>
    <span>BIOMETRIC ATTENDANCE • SECURE RECORDS • SMART MANAGEMENT</span>
</footer>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("lecturerSearch");
    const table = document.getElementById("lecturersTable");
    const searchResult = document.getElementById("searchResult");
    const noResults = document.getElementById("noResults");

    if (!searchInput || !table || !searchResult || !noResults) {
        return;
    }

    const rows = Array.from(
        table.querySelectorAll("tbody .lecturer-row")
    );

    function filterLecturers() {

        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach(function (row) {

            const rowText = row.textContent.toLowerCase();
            const matches = rowText.includes(query);

            row.style.display = matches ? "" : "none";

            if (matches) {
                visibleCount++;
            }

        });

        searchResult.textContent =
            "Showing " + visibleCount + " of " + rows.length + " lecturers";

        noResults.classList.toggle(
            "visible",
            visibleCount === 0
        );
    }

    searchInput.addEventListener("input", filterLecturers);

});
</script>

</body>
</html>
