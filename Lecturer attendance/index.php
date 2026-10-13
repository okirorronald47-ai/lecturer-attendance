<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#050b14">

    <title>University Lecturer Attendance System</title>

    <style>
        :root {
            --bg: #050b14;
            --panel: #0b1626;
            --panel2: #101f33;
            --cyan: #00e5ff;
            --cyan-soft: #8bf4ff;
            --blue: #3478f6;
            --text: #edf8ff;
            --muted: #8ca4bc;
            --border: rgba(0, 229, 255, .19);
            --green: #30f2a3;
            --gold: #ffd166;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            color: var(--text);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(ellipse at 50% -10%, rgba(0, 229, 255, .13), transparent 45%),
                radial-gradient(ellipse at 100% 100%, rgba(52, 120, 246, .10), transparent 35%),
                var(--bg);
        }

        .topbar {
            position: relative;
            background: rgba(5, 11, 20, .88);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(16px);
        }

        .topbar::after {
            position: absolute;
            right: 0;
            bottom: -1px;
            left: 0;
            height: 1px;
            content: "";
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
        }

        .topbar-inner {
            width: min(1160px, calc(100% - 40px));
            min-height: 82px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            color: var(--cyan);
            background: linear-gradient(145deg, rgba(0, 229, 255, .14), rgba(52, 120, 246, .08));
            border: 1px solid rgba(0, 229, 255, .42);
            border-radius: 14px;
            font-size: 21px;
            font-weight: 900;
            box-shadow: 0 0 25px rgba(0, 229, 255, .08);
        }

        .brand-name {
            margin: 0;
            font-size: 13px;
            font-weight: 850;
            letter-spacing: .025em;
        }

        .brand-subtitle {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 10px;
            letter-spacing: .04em;
        }

        .system-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            color: var(--cyan-soft);
            background: rgba(0, 229, 255, .06);
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .system-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 10px var(--green);
        }

        main {
            width: min(1060px, calc(100% - 36px));
            margin: auto;
            padding: 66px 0 72px;
        }

        .intro {
            max-width: 760px;
            margin: 0 auto 39px;
            text-align: center;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 15px;
            color: var(--cyan);
            font-size: 10px;
            font-weight: 850;
            letter-spacing: .2em;
            text-transform: uppercase;
        }

        .eyebrow::before,
        .eyebrow::after {
            width: 20px;
            height: 1px;
            content: "";
            background: var(--cyan);
            box-shadow: 0 0 8px var(--cyan);
        }

        .intro h1 {
            margin: 0;
            font-size: clamp(32px, 5.2vw, 55px);
            line-height: 1.12;
            letter-spacing: -.045em;
        }

        .intro h1 span {
            color: var(--cyan);
            text-shadow: 0 0 28px rgba(0, 229, 255, .16);
        }

        .intro-description {
            max-width: 570px;
            margin: 17px auto 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.9;
        }

        .intro-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 23px;
            padding: 8px 12px;
            color: #c4d7e9;
            background: rgba(140, 164, 188, .055);
            border: 1px solid rgba(140, 164, 188, .14);
            border-radius: 8px;
            font-size: 10px;
        }

        .intro-tag span {
            color: var(--cyan);
        }

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 15px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 12px;
            font-weight: 850;
            letter-spacing: .04em;
        }

        .section-heading p {
            margin: 0;
            color: var(--muted);
            font-size: 10px;
        }

        .options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .option {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 265px;
            flex-direction: column;
            align-items: flex-start;
            overflow: hidden;
            padding: 25px;
            color: var(--text);
            background: linear-gradient(145deg, rgba(16, 31, 51, .94), rgba(8, 19, 33, .98));
            border: 1px solid var(--border);
            border-radius: 18px;
            text-decoration: none;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .16);
            transition: transform .22s, border-color .22s, box-shadow .22s;
        }

        .option::before {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 2px;
            content: "";
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            opacity: .6;
        }

        .option::after {
            position: absolute;
            top: -65px;
            right: -65px;
            width: 150px;
            height: 150px;
            content: "";
            border: 1px solid rgba(0, 229, 255, .08);
            border-radius: 50%;
            box-shadow: 0 0 0 20px rgba(0, 229, 255, .018),
                        0 0 0 42px rgba(0, 229, 255, .012);
            pointer-events: none;
        }

        .option:hover {
            border-color: rgba(0, 229, 255, .55);
            box-shadow: 0 22px 52px rgba(0, 0, 0, .24),
                        0 0 28px rgba(0, 229, 255, .055);
            transform: translateY(-4px);
        }

        .option-icon {
            width: 53px;
            height: 53px;
            display: grid;
            place-items: center;
            margin-bottom: 24px;
            color: var(--cyan);
            background: rgba(0, 229, 255, .075);
            border: 1px solid rgba(0, 229, 255, .23);
            border-radius: 15px;
            font-size: 24px;
            box-shadow: inset 0 0 20px rgba(0, 229, 255, .025);
        }

        .option.admin .option-icon {
            color: var(--gold);
            background: rgba(255, 209, 102, .07);
            border-color: rgba(255, 209, 102, .23);
        }

        .option h2 {
            margin: 0;
            font-size: 19px;
            font-weight: 850;
            letter-spacing: -.02em;
        }

        .option p {
            max-width: 390px;
            margin: 10px 0 24px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.9;
        }

        .option-action {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-top: auto;
            color: var(--cyan);
            font-size: 11px;
            font-weight: 850;
        }

        .option-action span {
            display: inline-grid;
            place-items: center;
            width: 25px;
            height: 25px;
            border: 1px solid rgba(0, 229, 255, .23);
            border-radius: 50%;
            transition: transform .2s, background .2s;
        }

        .option:hover .option-action span {
            background: rgba(0, 229, 255, .09);
            transform: translateX(3px);
        }

        .option.admin .option-action {
            color: var(--gold);
        }

        .option.admin .option-action span {
            border-color: rgba(255, 209, 102, .23);
        }

        .bottom-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-top: 25px;
            color: #7088a0;
            font-size: 10px;
            text-align: center;
            line-height: 1.8;
        }

        .bottom-note span {
            color: var(--green);
        }

        .footer {
            padding: 20px;
            color: #637b93;
            border-top: 1px solid rgba(140, 164, 188, .09);
            font-size: 10px;
            text-align: center;
            letter-spacing: .04em;
        }

        .footer span {
            color: var(--cyan);
        }

        a:focus-visible {
            outline: 3px solid rgba(0, 229, 255, .55);
            outline-offset: 4px;
        }

        @media (max-width: 650px) {
            .topbar-inner {
                width: calc(100% - 26px);
                min-height: 72px;
            }

            .brand {
                gap: 10px;
            }

            .brand-mark {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .brand-name {
                max-width: 235px;
                font-size: 11px;
                line-height: 1.5;
            }

            .brand-subtitle {
                font-size: 9px;
            }

            .system-badge {
                display: none;
            }

            main {
                width: calc(100% - 26px);
                padding: 44px 0 48px;
            }

            .intro {
                margin-bottom: 30px;
            }

            .intro h1 {
                font-size: clamp(31px, 8vw, 42px);
            }

            .intro-description {
                font-size: 12px;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
                gap: 6px;
            }

            .options {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .option {
                min-height: 225px;
                padding: 21px;
            }

            .option-icon {
                margin-bottom: 19px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
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
                    <p class="brand-name">University Lecturer Attendance System</p>
                    <p class="brand-subtitle">Intelligent attendance management</p>
                </div>
            </div>

            <div class="system-badge">
                <span class="system-dot"></span>
                System portal
            </div>
        </div>
    </header>

    <main>
        <section class="intro">
            <p class="eyebrow">Smart campus technology</p>
            <h1>Welcome to <span>Smart Attendance.</span></h1>
            <p class="intro-description">
                A modern lecturer attendance platform designed to simplify
                identity verification, record attendance, and support
                efficient university administration.
            </p>

            <div class="intro-tag">
                <span>●</span>
                Facial recognition attendance management
            </div>
        </section>

        <section aria-label="Choose a system area">
            <div class="section-heading">
                <h2>CHOOSE YOUR ACCESS PORTAL</h2>
                <p>Select an option to continue</p>
            </div>

            <div class="options">
                <a class="option admin" href="admin/login.php">
                    <span class="option-icon" aria-hidden="true">⚙</span>
                    <h2>Administrator Portal</h2>
                    <p>
                        Access the management dashboard to register lecturers,
                        manage system administrators, monitor attendance,
                        and review reports and analytics.
                    </p>
                    <span class="option-action">
                        Go to administrator login
                        <span aria-hidden="true">→</span>
                    </span>
                </a>

                <a class="option lecturer" href="lecturer/recognize.php">
                    <span class="option-icon" aria-hidden="true">◉</span>
                    <h2>Lecturer Attendance</h2>
                    <p>
                        Open the attendance camera, verify a registered
                        lecturer using facial recognition, and record
                        check-in or check-out activity.
                    </p>
                    <span class="option-action">
                        Open attendance capture
                        <span aria-hidden="true">→</span>
                    </span>
                </a>
            </div>

            <div class="bottom-note">
                <span>●</span>
                Use the appropriate portal to access the system's features.
            </div>
        </section>
    </main>

    <footer class="footer">
        UNIVERSITY LECTURER ATTENDANCE MANAGEMENT SYSTEM
        &nbsp;·&nbsp;
        <span>SMART CAMPUS TECHNOLOGY</span>
    </footer>
</body>
</html>
