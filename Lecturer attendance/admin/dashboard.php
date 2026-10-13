<?php
session_start();

require_once __DIR__ . "/../config/database.php";

date_default_timezone_set("Africa/Kampala");

// Only logged-in administrators can access the dashboard.
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Safely display text in HTML.
function h($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Format attendance times.
function formatAttendanceTime($time)
{
    if (empty($time)) {
        return "—";
    }

    $timestamp = strtotime($time);

    return $timestamp !== false
        ? date("h:i A", $timestamp)
        : h($time);
}

// Initialize dashboard statistics.
$totalLecturers = 0;
$totalAttendance = 0;
$todayAttendance = 0;
$presentToday = 0;
$recentAttendance = [];
$dbError = false;

try {
    $result = $conn->query(
        "SELECT COUNT(*) AS total FROM lecturers"
    );

    if ($result) {
        $row = $result->fetch_assoc();
        $totalLecturers = (int)$row["total"];
        $result->free();
    }

    $result = $conn->query(
        "SELECT COUNT(*) AS total FROM attendance"
    );

    if ($result) {
        $row = $result->fetch_assoc();
        $totalAttendance = (int)$row["total"];
        $result->free();
    }

    $result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM attendance
         WHERE `Attendance date` = CURDATE()"
    );

    if ($result) {
        $row = $result->fetch_assoc();
        $todayAttendance = (int)$row["total"];
        $result->free();
    }

    $result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM attendance
         WHERE `Attendance date` = CURDATE()
         AND `Status` = 'Present'"
    );

    if ($result) {
        $row = $result->fetch_assoc();
        $presentToday = (int)$row["total"];
        $result->free();
    }

    $sql = "
        SELECT
            a.`Lecturer id` AS lecturer_id,
            l.`Full name` AS full_name,
            l.`Department` AS department,
            a.`Attendance date` AS attendance_date,
            a.`Attendance time` AS attendance_time,
            a.`Departure time` AS departure_time,
            a.`Status` AS attendance_status
        FROM attendance a
        LEFT JOIN lecturers l
            ON l.`Lecturer id` = a.`Lecturer id`
        ORDER BY
            a.`Attendance date` DESC,
            a.`Attendance time` DESC
        LIMIT 5
    ";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentAttendance[] = $row;
        }

        $result->free();
    }

} catch (Throwable $e) {
    error_log("Dashboard database error: " . $e->getMessage());
    $dbError = true;
}

$adminDisplayName =
    $_SESSION["admin_name"]
    ?? $_SESSION["admin_username"]
    ?? "Administrator";

$adminInitial = strtoupper(
    substr(trim((string)$adminDisplayName), 0, 1)
);

if ($adminInitial === "") {
    $adminInitial = "A";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Futuristic University Lecturer Attendance System administrator dashboard.">
    <meta name="theme-color" content="#06101f">

    <title>Command Center | Lecturer Attendance System</title>

    <style>
        :root {
            --bg: #050b16;
            --panel: rgba(12, 27, 47, 0.86);
            --teal: #40f5d0;
            --teal-bright: #73ffe4;
            --blue: #6aa8ff;
            --purple: #a78bfa;
            --text: #eaf7ff;
            --muted: #8ba4bc;
            --muted-2: #647e98;
            --line: rgba(99, 190, 218, 0.17);
            --line-strong: rgba(64, 245, 208, 0.35);
            --good: #53f5ae;
            --warning: #ffc76a;
            --danger: #ff8294;
            --radius: 18px;
            --shadow: 0 20px 50px rgba(0, 0, 0, 0.25),
                      0 4px 12px rgba(0, 0, 0, 0.16);
            --glow: 0 0 0 1px rgba(64, 245, 208, 0.09),
                    0 12px 35px rgba(0, 0, 0, 0.26),
                    0 0 32px rgba(20, 203, 176, 0.045);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 100px;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
            font-family: "Segoe UI", Inter, -apple-system,
                         BlinkMacSystemFont, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(ellipse at 12% 0%,
                    rgba(22, 130, 151, 0.14), transparent 32rem),
                radial-gradient(ellipse at 94% 15%,
                    rgba(69, 91, 192, 0.13), transparent 29rem),
                radial-gradient(ellipse at 50% 90%,
                    rgba(10, 145, 126, 0.06), transparent 35rem),
                var(--bg);
            -webkit-font-smoothing: antialiased;
        }

        body::before {
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            content: "";
            background-image:
                linear-gradient(rgba(101, 181, 210, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(101, 181, 210, 0.025) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(to bottom, black, transparent 85%);
        }

        body::after {
            position: fixed;
            top: -220px;
            right: -170px;
            z-index: -1;
            width: 440px;
            height: 440px;
            pointer-events: none;
            content: "";
            border: 1px solid rgba(64, 245, 208, 0.09);
            border-radius: 50%;
            box-shadow: 0 0 0 32px rgba(64, 245, 208, 0.018),
                        0 0 0 72px rgba(64, 245, 208, 0.014),
                        0 0 0 115px rgba(64, 245, 208, 0.01);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button, input {
            font: inherit;
        }

        a:focus-visible, button:focus-visible {
            outline: 2px solid var(--teal);
            outline-offset: 4px;
        }

        svg {
            display: block;
            width: 21px;
            height: 21px;
        }

        .icon {
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        /* TOP NAVIGATION */

        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid var(--line-strong);
            background: rgba(5, 13, 25, 0.88);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.18);
        }

        .topbar::after {
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 34%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--teal), transparent);
            content: "";
            opacity: 0.8;
        }

        .topbar-inner {
            width: min(1480px, calc(100% - 64px));
            min-height: 84px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            position: relative;
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(64, 245, 208, 0.42);
            border-radius: 15px;
            color: var(--teal);
            background: linear-gradient(145deg,
                rgba(64, 245, 208, 0.17), rgba(29, 80, 109, 0.10));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.09),
                        0 0 25px rgba(64, 245, 208, 0.08),
                        0 8px 22px rgba(0, 0, 0, 0.24);
            transform: perspective(300px) rotateY(-7deg);
        }

        .brand-mark::after {
            position: absolute;
            inset: 4px;
            border: 1px solid rgba(64, 245, 208, 0.11);
            border-radius: 11px;
            content: "";
        }

        .brand-mark svg {
            width: 27px;
            height: 27px;
        }

        .brand h1 {
            font-size: clamp(15px, 2vw, 20px);
            font-weight: 760;
            letter-spacing: -0.45px;
        }

        .brand p {
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 18px;
            flex-wrap: wrap;
        }

        .system-live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border: 1px solid rgba(83, 245, 174, 0.19);
            border-radius: 9px;
            background: rgba(83, 245, 174, 0.055);
            color: #8ef7c4;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        .live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--good);
            box-shadow: 0 0 0 4px rgba(83, 245, 174, 0.09),
                        0 0 12px rgba(83, 245, 174, 0.45);
            animation: pulse 2.2s ease-in-out infinite;
        }

        .admin-identity {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(64, 245, 208, 0.4);
            border-radius: 50%;
            background: linear-gradient(135deg,
                rgba(64, 245, 208, 0.22), rgba(106, 168, 255, 0.12));
            color: var(--teal-bright);
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 0 18px rgba(64, 245, 208, 0.08);
        }

        .admin-meta small {
            display: block;
            margin-bottom: 4px;
            color: var(--muted-2);
            font-size: 9px;
            letter-spacing: 1.1px;
            text-transform: uppercase;
        }

        .admin-meta strong {
            display: block;
            max-width: 190px;
            overflow: hidden;
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .logout {
            min-height: 39px;
            padding: 0 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid rgba(255, 130, 148, 0.25);
            border-radius: 9px;
            background: rgba(255, 130, 148, 0.055);
            color: #ffabb7;
            font-size: 11px;
            font-weight: 750;
            transition: transform 0.2s ease, background 0.2s ease,
                        border-color 0.2s ease;
        }

        .logout svg {
            width: 16px;
            height: 16px;
        }

        .logout:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 130, 148, 0.55);
            background: rgba(255, 130, 148, 0.13);
        }

        /* MAIN LAYOUT */

        .container {
            width: min(1480px, calc(100% - 64px));
            margin: 38px auto 0;
        }

        .welcome {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 34px;
            padding: 29px 31px;
            overflow: hidden;
            border: 1px solid rgba(64, 245, 208, 0.20);
            border-radius: 22px;
            background:
                radial-gradient(circle at 83% 5%,
                    rgba(64, 245, 208, 0.11), transparent 18rem),
                radial-gradient(circle at 100% 100%,
                    rgba(106, 168, 255, 0.10), transparent 23rem),
                linear-gradient(120deg, rgba(13, 32, 54, 0.97), rgba(7, 19, 35, 0.97));
            box-shadow: var(--shadow), inset 0 1px 0 rgba(255, 255, 255, 0.035);
            transform-style: preserve-3d;
        }

        .welcome::before {
            position: absolute;
            top: -80px;
            right: 8%;
            width: 220px;
            height: 220px;
            border: 1px solid rgba(64, 245, 208, 0.14);
            border-radius: 50%;
            box-shadow: 0 0 0 24px rgba(64, 245, 208, 0.025),
                        0 0 0 49px rgba(64, 245, 208, 0.018);
            content: "";
            pointer-events: none;
        }

        .welcome::after {
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, var(--teal), transparent);
            content: "";
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 13px;
            color: var(--teal);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            width: 22px;
            height: 2px;
            border-radius: 3px;
            background: var(--teal);
            box-shadow: 0 0 12px rgba(64, 245, 208, 0.65);
            content: "";
        }

        .welcome h2 {
            color: #f1fbff;
            font-size: clamp(28px, 3.2vw, 39px);
            font-weight: 800;
            letter-spacing: -1.2px;
            line-height: 1.18;
        }

        .welcome h2 span {
            color: var(--teal);
            text-shadow: 0 0 24px rgba(64, 245, 208, 0.15);
        }

        .welcome p {
            max-width: 670px;
            margin-top: 13px;
            color: #91a9c0;
            font-size: 12px;
            line-height: 1.9;
        }

        .date-panel {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 13px;
            flex-shrink: 0;
            padding: 15px 18px;
            border: 1px solid rgba(64, 245, 208, 0.20);
            border-radius: 14px;
            background: rgba(3, 15, 28, 0.63);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04),
                        0 12px 24px rgba(0, 0, 0, 0.12);
        }

        .date-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(64, 245, 208, 0.21);
            border-radius: 12px;
            background: rgba(64, 245, 208, 0.075);
            color: var(--teal);
        }

        .date-icon svg {
            width: 21px;
            height: 21px;
        }

        .date-panel small {
            display: block;
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .date-panel strong {
            display: block;
            color: var(--text);
            font-size: 11px;
            font-weight: 750;
            white-space: nowrap;
        }

        .warning {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 22px;
            padding: 16px 18px;
            border: 1px solid rgba(255, 199, 106, 0.35);
            border-radius: 13px;
            background: rgba(255, 199, 106, 0.07);
            color: #ffdaa0;
            font-size: 12px;
            line-height: 1.8;
        }

        .warning svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        /* SECTION HEADINGS */

        .section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin: 34px 0 18px;
        }

        .section-heading h2 {
            color: #e8f7ff;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .section-heading p {
            margin-top: 6px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            padding: 8px 11px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: rgba(12, 27, 47, 0.8);
            color: #a5bfd4;
            font-size: 10px;
            font-weight: 750;
            white-space: nowrap;
        }

        .section-tag svg {
            width: 15px;
            height: 15px;
            color: var(--teal);
        }

        /* STATISTICS CARDS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            perspective: 1100px;
        }

        .stat-card {
            --accent: var(--teal);
            --accent-rgb: 64, 245, 208;
            position: relative;
            isolation: isolate;
            min-width: 0;
            min-height: 190px;
            padding: 23px;
            overflow: hidden;
            border: 1px solid rgba(var(--accent-rgb), 0.20);
            border-radius: var(--radius);
            background:
                radial-gradient(circle at 100% 0%,
                    rgba(var(--accent-rgb), 0.105), transparent 12rem),
                linear-gradient(145deg, rgba(15, 34, 56, 0.97), rgba(7, 19, 34, 0.98));
            box-shadow: var(--glow), inset 0 1px 0 rgba(255, 255, 255, 0.045),
                        inset 0 -1px 0 rgba(0, 0, 0, 0.22);
            transform-style: preserve-3d;
            transition: transform 0.28s ease, border-color 0.28s ease,
                        box-shadow 0.28s ease;
            animation: card-enter 0.55s ease both;
        }

        .stat-card:nth-child(2) {
            --accent: var(--blue);
            --accent-rgb: 106, 168, 255;
            animation-delay: 0.07s;
        }

        .stat-card:nth-child(3) {
            --accent: var(--purple);
            --accent-rgb: 167, 139, 250;
            animation-delay: 0.14s;
        }

        .stat-card:nth-child(4) {
            --accent: var(--good);
            --accent-rgb: 83, 245, 174;
            animation-delay: 0.21s;
        }

        .stat-card::before {
            position: absolute;
            top: 0;
            left: 0;
            z-index: -1;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            content: "";
            opacity: 0.95;
        }

        .stat-card::after {
            position: absolute;
            right: -48px;
            bottom: -66px;
            width: 160px;
            height: 160px;
            border: 1px solid rgba(var(--accent-rgb), 0.13);
            border-radius: 50%;
            box-shadow: 0 0 0 17px rgba(var(--accent-rgb), 0.025),
                        0 0 0 35px rgba(var(--accent-rgb), 0.018);
            content: "";
            pointer-events: none;
        }

        .stat-card:hover {
            transform: perspective(900px) translateY(-7px) rotateX(2deg);
            border-color: rgba(var(--accent-rgb), 0.52);
            box-shadow: 0 26px 48px rgba(0, 0, 0, 0.32),
                        0 0 30px rgba(var(--accent-rgb), 0.075),
                        inset 0 1px 0 rgba(255, 255, 255, 0.075);
        }

        .stat-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .stat-label {
            color: #a4bdd2;
            font-size: 11px;
            font-weight: 650;
            line-height: 1.7;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(var(--accent-rgb), 0.28);
            border-radius: 14px;
            background: linear-gradient(145deg,
                rgba(var(--accent-rgb), 0.14), rgba(var(--accent-rgb), 0.025));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.075),
                        0 7px 16px rgba(0, 0, 0, 0.15);
            color: var(--accent);
            transform: translateZ(16px);
        }

        .stat-icon svg {
            width: 22px;
            height: 22px;
        }

        .stat-value {
            position: relative;
            z-index: 1;
            margin-top: 20px;
            color: #f3fbff;
            font-size: clamp(30px, 3vw, 39px);
            font-weight: 850;
            letter-spacing: -1.2px;
            line-height: 1.1;
            overflow-wrap: anywhere;
            text-shadow: 0 0 22px rgba(var(--accent-rgb), 0.07);
        }

        .stat-foot {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 13px;
            color: #7895ad;
            font-size: 10px;
            line-height: 1.6;
        }

        .mini-dot {
            width: 6px;
            height: 6px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 10px rgba(var(--accent-rgb), 0.55);
        }

        /* QUICK ACTIONS */

        .actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            perspective: 1100px;
        }

        .action {
            position: relative;
            isolation: isolate;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            min-width: 0;
            min-height: 132px;
            padding: 21px;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 16px;
            background:
                radial-gradient(circle at 100% 0%,
                    rgba(64, 245, 208, 0.065), transparent 11rem),
                linear-gradient(145deg, rgba(14, 32, 53, 0.96), rgba(7, 19, 34, 0.98));
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15),
                        inset 0 1px 0 rgba(255, 255, 255, 0.035);
            transform-style: preserve-3d;
            transition: transform 0.25s ease, border-color 0.25s ease,
                        box-shadow 0.25s ease;
            animation: card-enter 0.5s ease both;
        }

        .action:nth-child(2) { animation-delay: 0.04s; }
        .action:nth-child(3) { animation-delay: 0.08s; }
        .action:nth-child(4) { animation-delay: 0.12s; }
        .action:nth-child(5) { animation-delay: 0.16s; }
        .action:nth-child(6) { animation-delay: 0.20s; }

        .action::before {
            position: absolute;
            top: 0;
            left: 18px;
            width: 48px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--teal), transparent);
            opacity: 0.45;
            content: "";
        }

        .action:hover {
            transform: perspective(900px) translateY(-6px) rotateX(1.5deg);
            border-color: rgba(64, 245, 208, 0.38);
            box-shadow: 0 23px 42px rgba(0, 0, 0, 0.26),
                        0 0 25px rgba(64, 245, 208, 0.045),
                        inset 0 1px 0 rgba(255, 255, 255, 0.065);
        }

        .action:active {
            transform: translateY(-1px);
        }

        .action-icon {
            width: 49px;
            height: 49px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(64, 245, 208, 0.26);
            border-radius: 14px;
            background: linear-gradient(145deg,
                rgba(64, 245, 208, 0.14), rgba(23, 91, 108, 0.09));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.075),
                        0 8px 16px rgba(0, 0, 0, 0.18);
            color: var(--teal);
            transition: transform 0.25s ease, box-shadow 0.25s ease,
                        background 0.25s ease;
            transform: translateZ(15px);
        }

        .action:hover .action-icon {
            transform: translateZ(18px) rotate(-4deg) scale(1.04);
            background: linear-gradient(145deg,
                rgba(64, 245, 208, 0.22), rgba(23, 91, 108, 0.13));
            box-shadow: 0 0 23px rgba(64, 245, 208, 0.10),
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .action-icon svg {
            width: 23px;
            height: 23px;
        }

        .action-content {
            min-width: 0;
            flex: 1;
        }

        .action h3 {
            margin: 3px 0 8px;
            color: #eaf8ff;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.5;
        }

        .action p {
            color: #839db5;
            font-size: 10px;
            line-height: 1.9;
        }

        .action-arrow {
            position: absolute;
            right: 15px;
            bottom: 12px;
            color: var(--teal);
            font-size: 17px;
            opacity: 0;
            transform: translate(-4px, 4px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .action:hover .action-arrow {
            opacity: 0.9;
            transform: translate(0, 0);
        }

        /* RECENT ATTENDANCE TABLE */

        .table-card {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: rgba(9, 24, 42, 0.91);
            box-shadow: var(--shadow), inset 0 1px 0 rgba(255, 255, 255, 0.025);
        }

        .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 21px 23px;
            border-bottom: 1px solid var(--line);
            background:
                radial-gradient(circle at 100% 0%,
                    rgba(64, 245, 208, 0.055), transparent 16rem),
                rgba(12, 28, 48, 0.8);
        }

        .table-toolbar h3 {
            color: #e8f7ff;
            font-size: 13px;
            font-weight: 800;
        }

        .table-toolbar p {
            margin-top: 6px;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.7;
        }

        .view-all {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 13px;
            border: 1px solid rgba(64, 245, 208, 0.28);
            border-radius: 9px;
            background: rgba(64, 245, 208, 0.07);
            color: var(--teal);
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
            transition: transform 0.2s ease, background 0.2s ease,
                        border-color 0.2s ease;
        }

        .view-all:hover {
            transform: translateY(-2px);
            border-color: rgba(64, 245, 208, 0.52);
            background: rgba(64, 245, 208, 0.12);
        }

        .view-all svg {
            width: 15px;
            height: 15px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 790px;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
        }

        thead th {
            padding: 15px 17px;
            border-bottom: 1px solid var(--line);
            background: rgba(5, 16, 29, 0.85);
            color: #7f9bb4;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        tbody td {
            padding: 16px 17px;
            border-bottom: 1px solid rgba(99, 190, 218, 0.09);
            color: #a9bfd2;
            font-size: 10px;
            white-space: nowrap;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr {
            transition: background 0.18s ease;
        }

        tbody tr:hover {
            background: rgba(64, 245, 208, 0.035);
        }

        .lecturer-id {
            display: inline-flex;
            align-items: center;
            padding: 6px 9px;
            border: 1px solid rgba(106, 168, 255, 0.2);
            border-radius: 7px;
            background: rgba(106, 168, 255, 0.065);
            color: #a8c9ff;
            font-size: 10px;
            font-weight: 800;
        }

        .lecturer-name {
            color: #e8f7ff;
            font-size: 11px;
            font-weight: 750;
        }

        .department-text {
            color: #91a9c0;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 10px;
            border: 1px solid rgba(83, 245, 174, 0.22);
            border-radius: 20px;
            background: rgba(83, 245, 174, 0.075);
            color: #8cf5c2;
            font-size: 9px;
            font-weight: 800;
        }

        .status::before {
            width: 5px;
            height: 5px;
            flex-shrink: 0;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 8px currentColor;
            content: "";
        }

        .status.other {
            border-color: rgba(139, 164, 188, 0.20);
            background: rgba(139, 164, 188, 0.07);
            color: #afc1d2;
        }

        .empty {
            padding: 40px 20px;
            color: var(--muted);
            text-align: center;
            font-size: 11px;
            line-height: 1.9;
            white-space: normal;
        }

        .empty strong {
            color: #dcecf6;
        }

        .empty-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            margin: 0 auto 13px;
            border: 1px solid rgba(64, 245, 208, 0.22);
            border-radius: 13px;
            background: rgba(64, 245, 208, 0.06);
            color: var(--teal);
        }

        /* SYSTEM MODULES */

        .system-status {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
        }

        .system-item {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            min-width: 0;
            padding: 20px;
            border: 1px solid var(--line);
            border-radius: 15px;
            background:
                radial-gradient(circle at 100% 0%,
                    rgba(64, 245, 208, 0.055), transparent 10rem),
                linear-gradient(145deg, rgba(13, 30, 51, 0.95), rgba(7, 19, 34, 0.98));
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.13),
                        inset 0 1px 0 rgba(255, 255, 255, 0.03);
            transition: transform 0.23s ease, border-color 0.23s ease;
        }

        .system-item:hover {
            transform: translateY(-4px);
            border-color: rgba(64, 245, 208, 0.29);
        }

        .system-icon {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(64, 245, 208, 0.22);
            border-radius: 11px;
            background: rgba(64, 245, 208, 0.07);
            color: var(--teal);
        }

        .system-icon svg {
            width: 19px;
            height: 19px;
        }

        .system-item strong {
            display: block;
            margin: 2px 0 7px;
            color: #e6f5ff;
            font-size: 11px;
            font-weight: 800;
        }

        .system-item span {
            display: block;
            color: #849db4;
            font-size: 10px;
            line-height: 1.8;
        }

        .system-item .system-indicator {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 9px;
            color: #8df4c1;
            font-size: 9px;
            font-weight: 800;
        }

        .dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--good);
            box-shadow: 0 0 8px rgba(83, 245, 174, 0.5);
        }

        .system-indicator.is-warning {
            color: #ffd18b;
        }

        .system-indicator.is-warning .dot {
            background: var(--warning);
            box-shadow: 0 0 8px rgba(255, 199, 106, 0.45);
        }

        /* FOOTER */

        footer {
            width: min(1480px, calc(100% - 64px));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 40px auto 0;
            padding: 22px 0 28px;
            border-top: 1px solid var(--line);
            color: #6e879e;
            font-size: 10px;
            line-height: 1.8;
        }

        .footer-brand {
            color: #9bb5ca;
            font-weight: 750;
        }

        .footer-accent {
            color: var(--teal);
        }

        /* ANIMATIONS */

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                box-shadow: 0 0 0 4px rgba(83, 245, 174, 0.09);
            }
            50% {
                opacity: 0.65;
                box-shadow: 0 0 0 7px rgba(83, 245, 174, 0.025);
            }
        }

        @keyframes card-enter {
            from {
                opacity: 0;
                transform: translateY(13px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 1150px) {
            .topbar-inner, .container, footer {
                width: calc(100% - 44px);
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .stat-value {
                font-size: 36px;
            }

            .welcome {
                padding: 25px;
            }
        }

        @media (max-width: 760px) {
            .topbar {
                position: relative;
            }

            .topbar-inner {
                align-items: flex-start;
                flex-direction: column;
                gap: 17px;
                padding: 17px 0;
            }

            .topbar-right {
                width: 100%;
                justify-content: space-between;
                gap: 12px;
            }

            .system-live {
                display: none;
            }

            .welcome {
                align-items: flex-start;
                flex-direction: column;
                gap: 22px;
            }

            .date-panel {
                width: 100%;
            }

            .system-status {
                grid-template-columns: 1fr;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            footer {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 500px) {
            .topbar-inner, .container, footer {
                width: calc(100% - 28px);
            }

            .container {
                margin-top: 23px;
            }

            .brand {
                gap: 11px;
            }

            .brand-mark {
                width: 42px;
                height: 42px;
                border-radius: 12px;
            }

            .brand h1 {
                font-size: 15px;
                line-height: 1.5;
            }

            .brand p {
                font-size: 9px;
                letter-spacing: 0.8px;
            }

            .admin-meta strong {
                max-width: 125px;
            }

            .logout {
                min-height: 37px;
                padding: 0 10px;
            }

            .welcome {
                padding: 22px 19px;
                border-radius: 17px;
            }

            .welcome h2 {
                font-size: 29px;
            }

            .welcome p {
                font-size: 11px;
            }

            .date-panel strong {
                white-space: normal;
            }

            .stats, .actions {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .stat-card {
                min-height: 178px;
                padding: 21px;
            }

            .stat-value {
                font-size: 35px;
            }

            .action {
                min-height: 122px;
                padding: 18px;
            }

            .table-toolbar {
                align-items: flex-start;
                flex-direction: column;
                padding: 18px;
            }

            .view-all {
                width: 100%;
            }

            .section-heading {
                margin-top: 28px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">

        <a class="brand" href="dashboard.php" aria-label="Admin dashboard home">
            <div class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 20V8l8-4 8 4v12"/>
                    <path d="M8 20v-7h8v7"/>
                    <path d="M9 9h.01M15 9h.01"/>
                    <path d="M2 20h20"/>
                </svg>
            </div>

            <div>
                <h1>Lecturer Attendance System</h1>
                <p>Futuristic Administration Centre</p>
            </div>
        </a>

        <div class="topbar-right">

            <div class="system-live">
                <span class="live-dot"></span>
                Control Centre
            </div>

            <div class="admin-identity">
                <div class="avatar" aria-hidden="true">
                    <?= h($adminInitial) ?>
                </div>

                <div class="admin-meta">
                    <small>Signed in as</small>
                    <strong><?= h($adminDisplayName) ?></strong>
                </div>
            </div>

            <a class="logout" href="logout.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="M10 17l5-5-5-5"/>
                    <path d="M15 12H3"/>
                    <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
                </svg>
                Logout
            </a>

        </div>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <div class="welcome-content">
            <div class="eyebrow">University Administration</div>

            <h2>
                Welcome to your
                <span>Command Centre.</span>
            </h2>

            <p>
                Monitor lecturer registration, review attendance activity,
                and manage your university attendance system from one
                futuristic control panel.
            </p>
        </div>

        <div class="date-panel">
            <div class="date-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                    <path d="M16 3v4M8 3v4M3 10h18"/>
                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01"/>
                </svg>
            </div>

            <div>
                <small>Current date · Kampala</small>
                <strong><?= h(date("l, d F Y")) ?></strong>
            </div>
        </div>
    </section>

    <?php if ($dbError): ?>
        <div class="warning" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M12 3 2.5 20h19L12 3z"/>
                <path d="M12 9v4M12 17h.01"/>
            </svg>

            <div>
                <strong>Some dashboard information could not be loaded.</strong>
                Please check your database connection and table structure.
                Technical database details are hidden for security.
            </div>
        </div>
    <?php endif; ?>

    <section aria-labelledby="overview-heading">
        <div class="section-heading">
            <div>
                <h2 id="overview-heading">System Overview</h2>
                <p>Live totals from your lecturer and attendance database.</p>
            </div>

            <span class="section-tag">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="M3 3v18h18"/>
                    <path d="m7 14 4-4 4 3 5-7"/>
                </svg>
                Database statistics
            </span>
        </div>

        <div class="stats">

            <article class="stat-card">
                <div class="stat-top">
                    <p class="stat-label">Registered Lecturers</p>
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="10" cy="7" r="4"/>
                            <path d="M20 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-value"><?= number_format($totalLecturers) ?></h3>
                <div class="stat-foot"><span class="mini-dot"></span>Total lecturer profiles</div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <p class="stat-label">Attendance Records</p>
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="3" width="16" height="18" rx="2"/>
                            <path d="M8 8h8M8 12h8M8 16h5"/>
                            <path d="M7 3l1 2M16 3l1 2"/>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-value"><?= number_format($totalAttendance) ?></h3>
                <div class="stat-foot"><span class="mini-dot"></span>All recorded attendance entries</div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <p class="stat-label">Today's Attendance</p>
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-value"><?= number_format($todayAttendance) ?></h3>
                <div class="stat-foot"><span class="mini-dot"></span>Records dated today</div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <p class="stat-label">Marked Present Today</p>
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="m8 12 2.5 2.5L16 9"/>
                        </svg>
                    </div>
                </div>
                <h3 class="stat-value"><?= number_format($presentToday) ?></h3>
                <div class="stat-foot"><span class="mini-dot"></span>Records with Present status today</div>
            </article>

        </div>
    </section>

    <section aria-labelledby="actions-heading">
        <div class="section-heading">
            <div>
                <h2 id="actions-heading">Quick Actions</h2>
                <p>Access your existing attendance system modules.</p>
            </div>
        </div>

        <div class="actions">

            <a class="action" href="../lecturer/register.php">
                <span class="action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="8" r="4"/>
                        <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                        <path d="M19 8v6M16 11h6"/>
                    </svg>
                </span>
                <span class="action-content">
                    <h3>Register Lecturer</h3>
                    <p>Add lecturer details and register facial recognition information.</p>
                </span>
                <span class="action-arrow" aria-hidden="true">↗</span>
            </a>

            <!-- Take Attendance intentionally removed from the admin dashboard. -->

            <a class="action" href="../lecturer/lecturers.php">
                <span class="action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="8" r="3.5"/>
                        <path d="M2.5 20v-1.5a6.5 6.5 0 0 1 13 0V20z"/>
                        <path d="M16 5.5a3.5 3.5 0 0 1 0 6.8"/>
                        <path d="M18 15a5 5 0 0 1 3.5 4.8V20"/>
                    </svg>
                </span>
                <span class="action-content">
                    <h3>Registered Lecturers</h3>
                    <p>View lecturer profiles, departments and registration details.</p>
                </span>
                <span class="action-arrow" aria-hidden="true">↗</span>
            </a>

            <a class="action" href="../lecturer/attendance.php">
                <span class="action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="3" width="16" height="18" rx="2"/>
                        <path d="M8 8h8M8 12h8M8 16h5"/>
                    </svg>
                </span>
                <span class="action-content">
                    <h3>Attendance Records</h3>
                    <p>Review attendance, check-in times and departure times.</p>
                </span>
                <span class="action-arrow" aria-hidden="true">↗</span>
            </a>

            <a class="action" href="reports.php">
                <span class="action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/>
                        <path d="M14 3v5h5M8 12h8M8 16h8"/>
                    </svg>
                </span>
                <span class="action-content">
                    <h3>Attendance Reports</h3>
                    <p>Search records, filter by date and print reports.</p>
                </span>
                <span class="action-arrow" aria-hidden="true">↗</span>
            </a>

            <a class="action" href="manage_admins.php">
                <span class="action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 4 6v5c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                </span>
                <span class="action-content">
                    <h3>Manage Administrators</h3>
                    <p>View existing administrators and create administrator accounts.</p>
                </span>
                <span class="action-arrow" aria-hidden="true">↗</span>
            </a>

            <a class="action" href="analytics.php">
                <span class="action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18"/>
                        <path d="m7 14 4-4 4 3 5-7"/>
                        <path d="M17 6h3v3"/>
                    </svg>
                </span>
                <span class="action-content">
                    <h3>Attendance Analytics</h3>
                    <p>Explore attendance trends and compare activity across departments.</p>
                </span>
                <span class="action-arrow" aria-hidden="true">↗</span>
            </a>

        </div>
    </section>

    <section aria-labelledby="recent-heading">
        <div class="section-heading">
            <div>
                <h2 id="recent-heading">Recent Attendance</h2>
                <p>The five most recently recorded attendance entries.</p>
            </div>
            <span class="section-tag">Latest activity</span>
        </div>

        <div class="table-card">
            <div class="table-toolbar">
                <div>
                    <h3>Attendance Activity</h3>
                    <p>Check-in, departure and status information.</p>
                </div>

                <a class="view-all" href="../lecturer/attendance.php">
                    View all records
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                         aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Lecturer ID</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($recentAttendance)): ?>
                            <tr>
                                <td colspan="7" class="empty">
                                    <div class="empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="4" y="3" width="16" height="18" rx="2"/>
                                            <path d="M8 8h8M8 12h8M8 16h5"/>
                                        </svg>
                                    </div>
                                    <strong>No attendance records yet.</strong>
                                    <br>
                                    New attendance entries will appear here after they are recorded.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAttendance as $record): ?>
                                <?php
                                $statusValue = $record["attendance_status"] ?? "";

                                $statusClass =
                                    strtolower(trim((string)$statusValue)) === "present"
                                    ? "status"
                                    : "status other";

                                $date = $record["attendance_date"] ?? "";
                                $timestamp = $date !== "" ? strtotime($date) : false;
                                ?>

                                <tr>
                                    <td>
                                        <span class="lecturer-id">
                                            <?= h($record["lecturer_id"] ?? "") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="lecturer-name">
                                            <?= h(
                                                $record["full_name"]
                                                ?: "Unknown Lecturer"
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="department-text">
                                            <?= h($record["department"] ?: "—") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        echo $timestamp !== false
                                            ? h(date("d M Y", $timestamp))
                                            : h($date);
                                        ?>
                                    </td>

                                    <td>
                                        <?= formatAttendanceTime(
                                            $record["attendance_time"] ?? ""
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= formatAttendanceTime(
                                            $record["departure_time"] ?? ""
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="<?= h($statusClass) ?>">
                                            <?= h(
                                                $statusValue !== ""
                                                    ? $statusValue
                                                    : "Recorded"
                                            ) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section aria-labelledby="system-heading">
        <div class="section-heading">
            <div>
                <h2 id="system-heading">System Modules</h2>
                <p>A quick overview of the main components.</p>
            </div>
        </div>

        <div class="system-status">

            <article class="system-item">
                <div class="system-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="8" ry="3"/>
                        <path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/>
                        <path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>
                    </svg>
                </div>
                <div>
                    <strong>Database</strong>
                    <span>
                        <?= $dbError
                            ? "Database queries need attention."
                            : "Dashboard database queries completed." ?>
                    </span>
                    <span class="system-indicator <?= $dbError ? "is-warning" : "" ?>">
                        <i class="dot"></i>
                        <?= $dbError ? "Check connection" : "Queries successful" ?>
                    </span>
                </div>
            </article>

            <article class="system-item">
                <div class="system-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="3"/>
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M7 3v2M17 3v2M7 19v2M17 19v2"/>
                    </svg>
                </div>
                <div>
                    <strong>Face Recognition</strong>
                    <span>Browser-based facial recognition module.</span>
                    <span class="system-indicator"><i class="dot"></i>Module configured</span>
                </div>
            </article>

            <article class="system-item">
                <div class="system-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="m8 12 2.5 2.5L16 9"/>
                    </svg>
                </div>
                <div>
                    <strong>Attendance Module</strong>
                    <span>Check-in and check-out workflow.</span>
                    <span class="system-indicator"><i class="dot"></i>Module configured</span>
                </div>
            </article>

        </div>
    </section>

</main>

<footer>
    <div class="footer-brand">Lecturer Attendance Management System</div>
    <div>
        <span class="footer-accent">Futuristic Control Interface</span>
        &nbsp;·&nbsp;
        &copy; <?= date("Y") ?> All rights reserved.
    </div>
</footer>

</body>
</html>
