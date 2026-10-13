
<?php
require_once __DIR__ . "/../config/database.php";

mysqli_set_charset($conn, "utf8mb4");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {

    $lecturer_id = trim($_POST["lecturer_id"] ?? "");
    $full_name   = trim($_POST["full_name"] ?? "");
    $department  = trim($_POST["department"] ?? "");
    $email       = trim($_POST["email"] ?? "");
    $phone       = trim($_POST["phone"] ?? "");
    $face_data   = trim($_POST["face_data"] ?? "");

    if (
        $lecturer_id === "" ||
        $full_name === "" ||
        $department === "" ||
        $email === "" ||
        $phone === ""
    ) {
        $message = "Please complete all lecturer details.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif ($face_data === "") {
        $message = "Please capture the lecturer's face before registering.";
        $message_type = "error";

    } else {
        $descriptor = json_decode($face_data, true);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !is_array($descriptor) ||
            count($descriptor) !== 128
        ) {
            $message = "The face data is invalid. Please capture the face again.";
            $message_type = "error";

        } else {
            $sql = "INSERT INTO `lecturers`
                    (`Lecturer id`, `Full name`, `Department`,
                     `Email`, `Phone`, `Face_data`)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                $message = "Database query could not be prepared: " .
                           mysqli_error($conn);
                $message_type = "error";

            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssss",
                    $lecturer_id,
                    $full_name,
                    $department,
                    $email,
                    $phone,
                    $face_data
                );

                if (mysqli_stmt_execute($stmt)) {
                    $new_id = mysqli_insert_id($conn);
                    $db_result = mysqli_query(
                        $conn,
                        "SELECT DATABASE() AS db_name"
                    );
                    $db_row = $db_result
                        ? mysqli_fetch_assoc($db_result)
                        : null;
                    $db_name = $db_row["db_name"] ?? "unknown";

                    $message = "Registration successful! Database: " .
                               $db_name .
                               " | Record ID: " . $new_id .
                               " | Lecturer ID: " . $lecturer_id;

                    $message_type = "success";
                } else {
                    $error_number = mysqli_stmt_errno($stmt);

                    if ($error_number === 1062) {
                        $message = "Registration failed: this lecturer ID, email, or another unique value already exists.";
                    } else {
                        $message = "Registration failed. MySQL error " .
                                   $error_number . ": " .
                                   mysqli_stmt_error($stmt);
                    }

                    $message_type = "error";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}

function h($value)
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#050b16">

    <title>Register Lecturer | University Attendance System</title>

    <style>
        :root {
            --bg: #050b16;
            --panel: rgba(12, 27, 47, 0.88);
            --teal: #40f5d0;
            --teal-bright: #73ffe4;
            --blue: #6aa8ff;
            --text: #eaf7ff;
            --muted: #8ba4bc;
            --line: rgba(99, 190, 218, 0.17);
            --line-strong: rgba(64, 245, 208, 0.35);
            --good: #53f5ae;
            --danger: #ff8294;
            --radius: 18px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
            font-family: "Segoe UI", Inter, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(ellipse at 12% 0%,
                    rgba(22, 130, 151, 0.14), transparent 32rem),
                radial-gradient(ellipse at 94% 15%,
                    rgba(69, 91, 192, 0.13), transparent 29rem),
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
                linear-gradient(rgba(101,181,210,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(101,181,210,.025) 1px, transparent 1px);
            background-size: 42px 42px;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button, input {
            font: inherit;
        }

        button:focus-visible, a:focus-visible, input:focus-visible {
            outline: 2px solid var(--teal);
            outline-offset: 3px;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            border-bottom: 1px solid var(--line-strong);
            background: rgba(5, 13, 25, .9);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
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
            gap: 13px;
            min-width: 0;
        }

        .brand-mark {
            width: 47px;
            height: 47px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(64,245,208,.42);
            border-radius: 14px;
            background: linear-gradient(145deg,
                rgba(64,245,208,.17), rgba(29,80,109,.1));
            color: var(--teal);
            box-shadow: 0 0 25px rgba(64,245,208,.08);
        }

        .brand-mark svg {
            width: 26px;
            height: 26px;
        }

        .brand h1 {
            font-size: clamp(14px, 2vw, 19px);
            font-weight: 760;
            letter-spacing: -.4px;
        }

        .brand p {
            margin-top: 5px;
            color: var(--muted);
            font-size: 10px;
            letter-spacing: 1.15px;
            text-transform: uppercase;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .live-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border: 1px solid rgba(83,245,174,.2);
            border-radius: 9px;
            background: rgba(83,245,174,.05);
            color: #8ef7c4;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--good);
            box-shadow: 0 0 12px rgba(83,245,174,.5);
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 39px;
            padding: 0 13px;
            border: 1px solid rgba(64,245,208,.27);
            border-radius: 9px;
            background: rgba(64,245,208,.06);
            color: var(--teal);
            font-size: 11px;
            font-weight: 750;
            transition: .2s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
            background: rgba(64,245,208,.12);
            border-color: var(--teal);
        }

        .nav-link svg {
            width: 16px;
            height: 16px;
        }

        .container {
            width: min(1120px, calc(100% - 48px));
            margin: 35px auto 0;
        }

        .hero {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 25px;
            padding: 29px;
            overflow: hidden;
            border: 1px solid rgba(64,245,208,.2);
            border-radius: 21px;
            background:
                radial-gradient(circle at 88% 0%,
                    rgba(64,245,208,.11), transparent 18rem),
                linear-gradient(120deg, rgba(13,32,54,.97), rgba(7,19,35,.97));
            box-shadow: 0 20px 50px rgba(0,0,0,.22);
        }

        .hero::before {
            position: absolute;
            top: -80px;
            right: 7%;
            width: 210px;
            height: 210px;
            border: 1px solid rgba(64,245,208,.12);
            border-radius: 50%;
            box-shadow: 0 0 0 25px rgba(64,245,208,.02),
                        0 0 0 50px rgba(64,245,208,.015);
            content: "";
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 12px;
            color: var(--teal);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            width: 21px;
            height: 2px;
            border-radius: 2px;
            background: var(--teal);
            content: "";
        }

        .hero h2 {
            color: #f1fbff;
            font-size: clamp(27px, 3.5vw, 38px);
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.2;
        }

        .hero h2 span {
            color: var(--teal);
        }

        .hero p {
            max-width: 690px;
            margin-top: 12px;
            color: #91a9c0;
            font-size: 12px;
            line-height: 1.9;
        }

        .hero-icon {
            position: relative;
            z-index: 1;
            width: 76px;
            height: 76px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(64,245,208,.3);
            border-radius: 20px;
            background: rgba(64,245,208,.07);
            color: var(--teal);
            box-shadow: 0 0 35px rgba(64,245,208,.06);
        }

        .hero-icon svg {
            width: 37px;
            height: 37px;
        }

        .message {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 22px;
            padding: 16px 18px;
            border: 1px solid;
            border-radius: 13px;
            font-size: 12px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .message svg {
            width: 21px;
            height: 21px;
            flex-shrink: 0;
        }

        .message.success {
            border-color: rgba(83,245,174,.3);
            background: rgba(83,245,174,.075);
            color: #9af7c8;
        }

        .message.error {
            border-color: rgba(255,130,148,.3);
            background: rgba(255,130,148,.075);
            color: #ffabb7;
        }

        .panel {
            margin-bottom: 23px;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: rgba(9,24,42,.91);
            box-shadow: 0 20px 45px rgba(0,0,0,.19),
                        inset 0 1px 0 rgba(255,255,255,.025);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 21px 24px;
            border-bottom: 1px solid var(--line);
            background:
                radial-gradient(circle at 100% 0%,
                    rgba(64,245,208,.055), transparent 16rem),
                rgba(12,28,48,.8);
        }

        .heading-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(64,245,208,.25);
            border-radius: 12px;
            background: rgba(64,245,208,.07);
            color: var(--teal);
        }

        .heading-icon svg {
            width: 22px;
            height: 22px;
        }

        .panel-heading h3 {
            color: #e8f7ff;
            font-size: 14px;
            font-weight: 800;
        }

        .panel-heading p {
            margin-top: 5px;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.7;
        }

        .panel-body {
            padding: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 9px;
            min-width: 0;
        }

        .field label {
            color: #b5c9da;
            font-size: 11px;
            font-weight: 750;
        }

        .required {
            color: var(--teal);
        }

        .field input {
            width: 100%;
            min-height: 47px;
            padding: 12px 14px;
            border: 1px solid rgba(99,190,218,.22);
            border-radius: 10px;
            outline: none;
            background: rgba(4,15,28,.85);
            color: var(--text);
            font-size: 12px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .field input::placeholder {
            color: #5e7891;
        }

        .field input:focus {
            border-color: rgba(64,245,208,.7);
            box-shadow: 0 0 0 3px rgba(64,245,208,.08);
        }

        .field small {
            color: #728ba2;
            font-size: 10px;
            line-height: 1.6;
        }

        .camera-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(230px, .7fr);
            gap: 22px;
            align-items: start;
        }

        .camera-frame {
            position: relative;
            min-width: 0;
            padding: 12px;
            border: 1px solid rgba(64,245,208,.2);
            border-radius: 15px;
            background: rgba(3,12,23,.8);
            box-shadow: inset 0 0 30px rgba(64,245,208,.025);
        }

        .camera-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
            color: #9bb4c9;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .camera-label span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .camera-label i {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--teal);
            box-shadow: 0 0 9px rgba(64,245,208,.6);
        }

        #video {
            display: block;
            width: 100%;
            height: auto;
            min-height: 240px;
            max-height: 410px;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            border: 1px solid rgba(99,190,218,.12);
            border-radius: 10px;
            background: #030914;
        }

        .camera-help {
            margin-top: 11px;
            color: #718ba2;
            font-size: 10px;
            line-height: 1.8;
        }

        .camera-side {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }

        .status-card {
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(4,15,28,.7);
        }

        .status-card h4 {
            margin-bottom: 8px;
            color: #dcecf6;
            font-size: 11px;
            font-weight: 800;
        }

        .status-card p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .status-line {
            min-height: 42px;
            color: #9bb4c9;
            font-size: 11px;
            line-height: 1.8;
        }

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 43px;
            padding: 11px 15px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .2s ease, background .2s ease,
                        border-color .2s ease, box-shadow .2s ease;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }

        .btn svg {
            width: 17px;
            height: 17px;
        }

        .btn:disabled {
            opacity: .48;
            cursor: not-allowed;
        }

        .btn-primary {
            border-color: rgba(64,245,208,.35);
            background: rgba(64,245,208,.12);
            color: var(--teal-bright);
        }

        .btn-primary:hover:not(:disabled) {
            background: rgba(64,245,208,.19);
            box-shadow: 0 0 20px rgba(64,245,208,.07);
        }

        .btn-secondary {
            border-color: rgba(106,168,255,.27);
            background: rgba(106,168,255,.08);
            color: #a9ccff;
        }

        .btn-secondary:hover:not(:disabled) {
            background: rgba(106,168,255,.14);
        }

        .capture-status {
            min-height: 65px;
            padding: 13px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: rgba(9,24,42,.75);
            color: #9bb4c9;
            font-size: 11px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .submit-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 25px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
        }

        .submit-note {
            max-width: 430px;
            color: #728ba2;
            font-size: 10px;
            line-height: 1.8;
        }

        .submit-button {
            min-height: 48px;
            padding: 13px 22px;
            border: 1px solid rgba(64,245,208,.4);
            border-radius: 10px;
            background: linear-gradient(135deg,
                rgba(64,245,208,.19), rgba(22,137,125,.18));
            color: #9bffeb;
            font-size: 12px;
            font-weight: 850;
            cursor: pointer;
            box-shadow: 0 0 24px rgba(64,245,208,.045);
            transition: transform .2s ease, background .2s ease;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg,
                rgba(64,245,208,.27), rgba(22,137,125,.23));
        }

        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 29px;
            padding: 21px 0 27px;
            border-top: 1px solid var(--line);
            color: #6e879e;
            font-size: 10px;
            line-height: 1.8;
        }

        .footer strong {
            color: #9bb5ca;
        }

        .footer span {
            color: var(--teal);
        }

        @media (max-width: 800px) {
            .camera-layout {
                grid-template-columns: 1fr;
            }

            .camera-side {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .capture-status {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 600px) {
            .topbar {
                position: relative;
            }

            .topbar-inner {
                width: calc(100% - 28px);
                align-items: flex-start;
                flex-direction: column;
                padding: 15px 0;
                gap: 14px;
            }

            .brand-mark {
                width: 42px;
                height: 42px;
            }

            .brand h1 {
                font-size: 14px;
            }

            .brand p {
                font-size: 9px;
            }

            .top-actions {
                width: 100%;
                justify-content: space-between;
            }

            .live-label {
                font-size: 9px;
            }

            .container {
                width: calc(100% - 28px);
                margin-top: 21px;
            }

            .hero {
                padding: 22px 19px;
                border-radius: 16px;
            }

            .hero h2 {
                font-size: 28px;
            }

            .hero p {
                font-size: 11px;
            }

            .hero-icon {
                width: 52px;
                height: 52px;
                border-radius: 14px;
            }

            .hero-icon svg {
                width: 27px;
                height: 27px;
            }

            .panel-heading {
                padding: 17px;
            }

            .panel-body {
                padding: 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 17px;
            }

            .camera-side {
                grid-template-columns: 1fr;
            }

            .capture-status {
                grid-column: auto;
            }

            #video {
                min-height: 190px;
            }

            .submit-row {
                align-items: stretch;
                flex-direction: column;
            }

            .submit-button {
                width: 100%;
            }

            .footer {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">

        <a class="brand" href="../admin/dashboard.php">
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

        <div class="top-actions">
            <div class="live-label">
                <span class="live-dot"></span>
                Registration Module
            </div>

            <a class="nav-link" href="../admin/dashboard.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"/>
                    <path d="M9 12h12"/>
                </svg>
                Dashboard
            </a>
        </div>

    </div>
</header>

<main class="container">

    <section class="hero">
        <div class="hero-content">
            <div class="eyebrow">Lecturer Management</div>
            <h2>Register a <span>Lecturer.</span></h2>
            <p>
                Create a lecturer profile and securely associate it with a
                facial-recognition descriptor for the university attendance system.
                Complete the details and capture the lecturer's face before submitting.
            </p>
        </div>

        <div class="hero-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="8" r="4"/>
                <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                <path d="M19 8v6M16 11h6"/>
            </svg>
        </div>
    </section>

    <?php if ($message !== ""): ?>
        <div class="message <?= $message_type === "success" ? "success" : "error" ?>"
             role="status">
            <?php if ($message_type === "success"): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="m8 12 2.5 2.5L16 9"/>
                </svg>
            <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v5M12 16h.01"/>
                </svg>
            <?php endif; ?>
            <div><?= h($message) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" id="registrationForm" autocomplete="off">

        <section class="panel">
            <div class="panel-heading">
                <div class="heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                    </svg>
                </div>
                <div>
                    <h3>Lecturer Information</h3>
                    <p>Enter the lecturer's official details below.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-grid">

                    <div class="field">
                        <label for="lecturer_id">Lecturer ID <span class="required">*</span></label>
                        <input type="text" id="lecturer_id" name="lecturer_id"
                               placeholder="e.g. L004" required>
                        <small>Use a unique ID for each lecturer.</small>
                    </div>

                    <div class="field">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name"
                               placeholder="Enter lecturer's full name" required>
                    </div>

                    <div class="field">
                        <label for="department">Department <span class="required">*</span></label>
                        <input type="text" id="department" name="department"
                               placeholder="e.g. Information Technology" required>
                    </div>

                    <div class="field">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email"
                               placeholder="lecturer@example.com" required>
                    </div>

                    <div class="field">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone"
                               placeholder="Enter phone number" required>
                    </div>

                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div class="heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="3"/>
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M7 3v2M17 3v2M7 19v2M17 19v2"/>
                    </svg>
                </div>
                <div>
                    <h3>Facial Recognition Setup</h3>
                    <p>Start the camera and capture a valid face descriptor.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="camera-layout">

                    <div class="camera-frame">
                        <div class="camera-label">
                            <span><i></i> Live camera preview</span>
                            <span>Face capture</span>
                        </div>

                        <video id="video" autoplay muted playsinline></video>

                        <p class="camera-help">
                            Position one lecturer in front of the camera. Use good
                            lighting and keep the face clearly visible.
                        </p>
                    </div>

                    <div class="camera-side">

                        <div class="status-card">
                            <h4>Recognition Engine</h4>
                            <p id="cameraStatus" class="status-line">
                                Loading facial recognition models...
                            </p>
                        </div>

                        <div class="status-card">
                            <h4>Camera Controls</h4>
                            <div class="button-row">
                                <button type="button" class="btn btn-secondary"
                                        id="startCamera">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m8 5 12 7-12 7z"/>
                                    </svg>
                                    Start Camera
                                </button>

                                <button type="button" class="btn btn-primary"
                                        id="captureFace" disabled>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 7h3l2-3h6l2 3h3v13H4z"/>
                                        <circle cx="12" cy="13" r="4"/>
                                    </svg>
                                    Capture Face
                                </button>
                            </div>
                        </div>

                        <div id="faceStatus" class="capture-status" aria-live="polite">
                            No face captured yet. Start the camera, then capture the face.
                        </div>

                    </div>
                </div>

                <input type="hidden" id="face_data" name="face_data" value="">

                <div class="submit-row">
                    <p class="submit-note">
                        All lecturer details and a valid face descriptor are required.
                        The descriptor is submitted with the lecturer profile.
                    </p>

                    <button type="submit" name="register" value="1"
                            class="submit-button" id="registerButton">
                        Register Lecturer &rarr;
                    </button>
                </div>
            </div>
        </section>

    </form>

    <footer class="footer">
        <div><strong>Lecturer Attendance Management System</strong></div>
        <div><span>Futuristic Control Interface</span> &nbsp;·&nbsp; Registration Module</div>
    </footer>

</main>

<script src="../js/face-api.min.js"></script>

<script>
const video = document.getElementById("video");
const cameraStatus = document.getElementById("cameraStatus");
const faceStatus = document.getElementById("faceStatus");
const faceDataInput = document.getElementById("face_data");
const startCameraButton = document.getElementById("startCamera");
const captureFaceButton = document.getElementById("captureFace");
const registrationForm = document.getElementById("registrationForm");

let modelsLoaded = false;
let cameraStream = null;

async function loadFaceModels() {
    try {
        cameraStatus.textContent = "Loading facial recognition models...";

        await faceapi.nets.tinyFaceDetector.loadFromUri("../models");
        await faceapi.nets.faceLandmark68Net.loadFromUri("../models");
        await faceapi.nets.faceRecognitionNet.loadFromUri("../models");

        modelsLoaded = true;
        cameraStatus.textContent =
            "All facial recognition models loaded successfully.";
        cameraStatus.style.color = "#53f5ae";

        captureFaceButton.disabled = false;

    } catch (error) {
        console.error("Model loading error:", error);

        cameraStatus.textContent =
            "Could not load the models. Check that the model files are inside the models folder.";
        cameraStatus.style.color = "#ff8294";
    }
}

async function startCamera() {
    if (!modelsLoaded) {
        cameraStatus.textContent =
            "Please wait until the facial recognition models finish loading.";
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        cameraStatus.textContent =
            "Camera access is unavailable. Open this page through localhost in a supported browser.";
        cameraStatus.style.color = "#ff8294";
        return;
    }

    try {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
        }

        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: "user",
                width: { ideal: 640 },
                height: { ideal: 480 }
            },
            audio: false
        });

        video.srcObject = cameraStream;
        await video.play();

        cameraStatus.textContent =
            "Camera is running. Position the face in the video.";
        cameraStatus.style.color = "#53f5ae";

        faceDataInput.value = "";
        faceStatus.textContent =
            "Ready to capture the lecturer's face.";
        faceStatus.style.color = "#9bb4c9";

    } catch (error) {
        console.error("Camera error:", error);

        cameraStatus.textContent =
            "Camera could not start. Allow camera access and close other apps using the camera.";
        cameraStatus.style.color = "#ff8294";
    }
}

async function captureFace() {
    if (!modelsLoaded) {
        faceStatus.textContent =
            "Facial recognition models are not ready.";
        return;
    }

    if (!video.srcObject || video.readyState < 2) {
        faceStatus.textContent =
            "Start the camera and wait for the video first.";
        return;
    }

    captureFaceButton.disabled = true;
    faceStatus.textContent = "Detecting face...";

    try {
        const result = await faceapi
            .detectSingleFace(
                video,
                new faceapi.TinyFaceDetectorOptions({
                    inputSize: 320,
                    scoreThreshold: 0.5
                })
            )
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!result) {
            faceDataInput.value = "";
            faceStatus.textContent =
                "No face detected. Face the camera, improve the lighting, and try again.";
            faceStatus.style.color = "#ff8294";
            return;
        }

        const descriptor = Array.from(result.descriptor);

        if (descriptor.length !== 128) {
            faceDataInput.value = "";
            faceStatus.textContent =
                "The face descriptor is invalid. Please try again.";
            faceStatus.style.color = "#ff8294";
            return;
        }

        faceDataInput.value = JSON.stringify(descriptor);

        faceStatus.textContent =
            "Face captured successfully! You can now register the lecturer.";
        faceStatus.style.color = "#53f5ae";

    } catch (error) {
        console.error("Face capture error:", error);

        faceDataInput.value = "";
        faceStatus.textContent =
            "Face capture failed. Check the browser console and try again.";
        faceStatus.style.color = "#ff8294";

    } finally {
        captureFaceButton.disabled = false;
    }
}

startCameraButton.addEventListener("click", startCamera);
captureFaceButton.addEventListener("click", captureFace);

registrationForm.addEventListener("submit", function(event) {
    if (!faceDataInput.value) {
        event.preventDefault();
        alert("Please capture the lecturer's face before registering.");
        return;
    }

    let descriptor;

    try {
        descriptor = JSON.parse(faceDataInput.value);
    } catch (error) {
        event.preventDefault();
        alert("The face data is invalid. Please capture the face again.");
        return;
    }

    if (!Array.isArray(descriptor) || descriptor.length !== 128) {
        event.preventDefault();
        alert("The face data is invalid. Please capture the face again.");
    }
});

window.addEventListener("beforeunload", function() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
    }
});

loadFaceModels();
</script>

</body>
</html>
