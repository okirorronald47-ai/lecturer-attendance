<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#07111f">
    <title>Smart Attendance Capture | University Attendance System</title>

    <script src="../js/face-api.min.js"></script>

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
            --amber: #ffd166;
            --red: #ff647c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--text);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(ellipse at 50% -10%, rgba(0, 229, 255, .13), transparent 45%),
                radial-gradient(ellipse at 100% 100%, rgba(52, 120, 246, .10), transparent 35%),
                var(--bg);
        }

        button, a {
            -webkit-tap-highlight-color: transparent;
        }

        .topbar {
            position: relative;
            border-bottom: 1px solid var(--border);
            background: rgba(5, 11, 20, .88);
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
            opacity: .65;
        }

        .topbar-inner {
            width: min(1180px, calc(100% - 40px));
            min-height: 82px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
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
            font-size: 21px;
            font-weight: 900;
            border: 1px solid rgba(0, 229, 255, .42);
            border-radius: 14px;
            background: linear-gradient(145deg, rgba(0, 229, 255, .14), rgba(52, 120, 246, .08));
            box-shadow: 0 0 25px rgba(0, 229, 255, .08), inset 0 0 18px rgba(0, 229, 255, .04);
        }

        .brand h1 {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .025em;
        }

        .brand p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: .04em;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 18px;
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
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .system-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 10px var(--green);
        }

        .header-link {
            color: var(--muted);
            text-decoration: none;
            font-size: 12px;
            font-weight: 750;
            transition: color .2s;
        }

        .header-link:hover {
            color: var(--cyan);
        }

        .page {
            width: min(1120px, calc(100% - 36px));
            margin: 38px auto 30px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 10px;
            color: var(--cyan);
            font-size: 10px;
            font-weight: 850;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .eyebrow::before {
            width: 22px;
            height: 2px;
            content: "";
            background: var(--cyan);
            box-shadow: 0 0 10px var(--cyan);
        }

        .page-heading h2 {
            margin: 0;
            font-size: clamp(26px, 4vw, 38px);
            line-height: 1.2;
            letter-spacing: -.035em;
        }

        .page-heading p.description {
            max-width: 640px;
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .workspace {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(270px, .8fr);
            gap: 20px;
            align-items: start;
        }

        .panel {
            min-width: 0;
            overflow: hidden;
            background: linear-gradient(145deg, rgba(16, 31, 51, .94), rgba(8, 19, 33, .98));
            border: 1px solid var(--border);
            border-radius: 19px;
            box-shadow: 0 18px 55px rgba(0, 0, 0, .22);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 19px 20px;
            border-bottom: 1px solid rgba(140, 164, 188, .12);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .step-number {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            color: var(--cyan);
            background: rgba(0, 229, 255, .08);
            border: 1px solid var(--border);
            border-radius: 9px;
            font-size: 12px;
            font-weight: 900;
        }

        .panel-header h3 {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
        }

        .panel-header p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 10px;
        }

        .live-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--muted);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .1em;
        }

        .live-label span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--amber);
        }

        .panel-body {
            padding: 20px;
        }

        .action-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 23px;
        }

        .action-choice {
            min-width: 0;
            min-height: 104px;
            padding: 16px;
            color: var(--text);
            text-align: left;
            background: rgba(5, 11, 20, .55);
            border: 1px solid rgba(140, 164, 188, .19);
            border-radius: 13px;
            cursor: pointer;
            transition: border-color .2s, background .2s, transform .2s, box-shadow .2s;
        }

        .action-choice:hover:not(:disabled) {
            border-color: rgba(0, 229, 255, .5);
            transform: translateY(-2px);
        }

        .action-choice.selected {
            background: rgba(0, 229, 255, .075);
            border-color: var(--cyan);
            box-shadow: inset 0 0 22px rgba(0, 229, 255, .045), 0 0 18px rgba(0, 229, 255, .05);
        }

        .action-icon {
            display: block;
            margin-bottom: 11px;
            color: var(--cyan);
            font-size: 19px;
            line-height: 1;
        }

        .action-choice strong {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .action-choice small {
            display: block;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.6;
        }

        .action-choice:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .camera-frame {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            background: #020810;
            border: 1px solid rgba(0, 229, 255, .25);
            border-radius: 14px;
            box-shadow: inset 0 0 35px rgba(0, 229, 255, .045);
        }

        .camera-frame::before {
            position: absolute;
            z-index: 2;
            inset: 0;
            content: "";
            pointer-events: none;
            background:
                linear-gradient(var(--cyan), var(--cyan)) left 15px top 15px / 22px 2px no-repeat,
                linear-gradient(var(--cyan), var(--cyan)) left 15px top 15px / 2px 22px no-repeat,
                linear-gradient(var(--cyan), var(--cyan)) right 15px top 15px / 22px 2px no-repeat,
                linear-gradient(var(--cyan), var(--cyan)) right 15px top 15px / 2px 22px no-repeat,
                linear-gradient(var(--cyan), var(--cyan)) left 15px bottom 15px / 22px 2px no-repeat,
                linear-gradient(var(--cyan), var(--cyan)) left 15px bottom 15px / 2px 22px no-repeat,
                linear-gradient(var(--cyan), var(--cyan)) right 15px bottom 15px / 22px 2px no-repeat,
                linear-gradient(var(--cyan), var(--cyan)) right 15px bottom 15px / 2px 22px no-repeat;
            opacity: .9;
        }

        video {
            display: block;
            width: 100%;
            min-height: 280px;
            aspect-ratio: 16 / 10;
            object-fit: cover;
            background:
                radial-gradient(ellipse at center, rgba(0, 229, 255, .09), transparent 48%),
                #020810;
        }

        .scan-line {
            position: absolute;
            z-index: 1;
            top: 12%;
            right: 8%;
            left: 8%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            box-shadow: 0 0 15px var(--cyan);
            opacity: 0;
            pointer-events: none;
        }

        .camera-frame.scanning .scan-line {
            opacity: .9;
            animation: scan 2.8s ease-in-out infinite alternate;
        }

        @keyframes scan {
            from { top: 12%; }
            to { top: 86%; }
        }

        .camera-guide {
            position: absolute;
            z-index: 3;
            right: 30px;
            bottom: 29px;
            left: 30px;
            padding: 12px;
            color: #e9fbff;
            background: rgba(4, 13, 23, .86);
            border: 1px solid rgba(0, 229, 255, .19);
            border-radius: 10px;
            font-size: 11px;
            line-height: 1.6;
            text-align: center;
            pointer-events: none;
            backdrop-filter: blur(8px);
        }

        .camera-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 11px;
            margin-top: 15px;
        }

        .camera-controls button {
            min-height: 45px;
            padding: 12px 16px;
            border: 1px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-size: 11px;
            font-weight: 850;
            transition: transform .2s, background .2s, border-color .2s;
        }

        .camera-controls button:hover {
            transform: translateY(-1px);
        }

        #startCamera {
            color: #03111b;
            background: linear-gradient(110deg, #00e5ff, #75f2ff);
            box-shadow: 0 5px 20px rgba(0, 229, 255, .12);
        }

        #startCamera:hover {
            background: #9af6ff;
        }

        #stopCamera {
            color: #dce9f4;
            background: rgba(140, 164, 188, .08);
            border-color: rgba(140, 164, 188, .18);
        }

        #stopCamera:hover {
            background: rgba(140, 164, 188, .15);
        }

        button:focus-visible, a:focus-visible {
            outline: 3px solid rgba(0, 229, 255, .55);
            outline-offset: 3px;
        }

        #status {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 14px;
            padding: 13px 14px;
            color: #b9cde0;
            background: rgba(5, 11, 20, .7);
            border: 1px solid rgba(140, 164, 188, .14);
            border-radius: 10px;
            font-size: 11px;
            font-weight: 650;
            line-height: 1.7;
        }

        #status::before {
            flex: 0 0 7px;
            width: 7px;
            height: 7px;
            margin-top: 5px;
            content: "";
            border-radius: 50%;
            background: var(--cyan);
            box-shadow: 0 0 9px rgba(0, 229, 255, .65);
        }

        .result {
            display: none;
            margin-top: 15px;
            padding: 17px;
            border: 1px solid transparent;
            border-radius: 12px;
            animation: reveal .25s ease-out;
        }

        @keyframes reveal {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result.success {
            color: #d6fff0;
            background: rgba(48, 242, 163, .075);
            border-color: rgba(48, 242, 163, .35);
        }

        .result.warning {
            color: #fff0c5;
            background: rgba(255, 209, 102, .075);
            border-color: rgba(255, 209, 102, .35);
        }

        .result.error {
            color: #ffe0e5;
            background: rgba(255, 100, 124, .075);
            border-color: rgba(255, 100, 124, .35);
        }

        .result h2 {
            margin: 0 0 12px;
            font-size: 14px;
            line-height: 1.5;
        }

        .details {
            display: grid;
            gap: 8px;
            color: #c8d8e7;
            font-size: 11px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .result.success .details {
            color: #d6fff0;
        }

        .result.warning .details {
            color: #fff0c5;
        }

        .result.error .details {
            color: #ffe0e5;
        }

        .side-panel {
            display: grid;
            gap: 16px;
        }

        .info-card {
            padding: 19px;
            background: linear-gradient(145deg, rgba(16, 31, 51, .84), rgba(8, 19, 33, .94));
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .info-card h3 {
            margin: 0 0 14px;
            font-size: 12px;
            font-weight: 850;
        }

        .process-list {
            display: grid;
            gap: 16px;
        }

        .process-item {
            display: flex;
            align-items: flex-start;
            gap: 11px;
        }

        .process-icon {
            flex: 0 0 31px;
            width: 31px;
            height: 31px;
            display: grid;
            place-items: center;
            color: var(--cyan);
            background: rgba(0, 229, 255, .075);
            border: 1px solid var(--border);
            border-radius: 9px;
            font-size: 12px;
            font-weight: 850;
        }

        .process-item strong {
            display: block;
            margin: 1px 0 4px;
            font-size: 11px;
        }

        .process-item p {
            margin: 0;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.7;
        }

        .security-card {
            position: relative;
            overflow: hidden;
            border-color: rgba(52, 120, 246, .28);
        }

        .security-card::after {
            position: absolute;
            top: -45px;
            right: -45px;
            width: 110px;
            height: 110px;
            content: "";
            border: 1px solid rgba(0, 229, 255, .12);
            border-radius: 50%;
            box-shadow: 0 0 0 18px rgba(0, 229, 255, .025), 0 0 0 38px rgba(0, 229, 255, .018);
            pointer-events: none;
        }

        .security-symbol {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            margin-bottom: 13px;
            color: var(--cyan);
            background: rgba(0, 229, 255, .08);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 18px;
        }

        .security-card p {
            margin: 0;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.9;
        }

        .privacy-note {
            margin-top: 14px;
            padding-top: 13px;
            color: #a7c0d8;
            border-top: 1px solid rgba(140, 164, 188, .12);
            font-size: 10px;
            line-height: 1.8;
        }

        .footer {
            padding: 8px 18px 28px;
            color: #637b93;
            font-size: 10px;
            text-align: center;
            letter-spacing: .025em;
        }

        .footer span {
            color: var(--cyan);
        }

        @media (max-width: 850px) {
            .workspace {
                grid-template-columns: 1fr;
            }

            .side-panel {
                grid-template-columns: 1fr 1fr;
                align-items: stretch;
            }
        }

        @media (max-width: 600px) {
            .topbar-inner {
                width: calc(100% - 26px);
                min-height: 72px;
            }

            .brand {
                gap: 9px;
            }

            .brand-mark {
                width: 39px;
                height: 39px;
                font-size: 18px;
            }

            .brand h1 {
                max-width: 190px;
                font-size: 11px;
                line-height: 1.5;
            }

            .brand p {
                font-size: 9px;
            }

            .system-badge {
                display: none;
            }

            .header-link {
                font-size: 11px;
                white-space: nowrap;
            }

            .page {
                width: calc(100% - 24px);
                margin: 27px auto 23px;
            }

            .page-heading {
                margin-bottom: 19px;
            }

            .page-heading p.description {
                font-size: 12px;
            }

            .panel-header {
                padding: 15px;
            }

            .panel-body {
                padding: 14px;
            }

            .action-options {
                gap: 9px;
            }

            .action-choice {
                min-height: 100px;
                padding: 12px;
            }

            .action-choice strong {
                font-size: 12px;
            }

            .action-choice small {
                font-size: 9px;
            }

            video {
                min-height: 215px;
            }

            .camera-guide {
                right: 25px;
                bottom: 26px;
                left: 25px;
                padding: 9px;
                font-size: 10px;
            }

            .camera-controls {
                gap: 8px;
            }

            .camera-controls button {
                padding: 11px 5px;
                font-size: 10px;
            }

            .side-panel {
                grid-template-columns: 1fr;
            }

            .info-card {
                padding: 16px;
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
                <h1>University Lecturer Attendance System</h1>
                <p>Intelligent attendance capture</p>
            </div>
        </div>

        <div class="header-actions">
            <div class="system-badge">
                <span class="system-dot"></span>
                Attendance portal
            </div>
            <a class="header-link" href="../index.php">← Home</a>
        </div>
    </div>
</header>

<main class="page">
    <section class="page-heading">
        <p class="eyebrow">Biometric verification · Secure access</p>
        <h2>Lecturer Check In / Check Out</h2>
        <p class="description">
            Verify your identity using facial recognition and record your
            arrival or departure for the current day.
        </p>
    </section>

    <div class="workspace">
        <section class="panel" aria-label="Attendance camera and controls">
            <div class="panel-header">
                <div class="panel-heading">
                    <span class="step-number">01</span>
                    <div>
                        <h3>Identity verification</h3>
                        <p>Choose an action before scanning</p>
                    </div>
                </div>
                <div class="live-label">
                    <span></span>
                    CAMERA MODULE
                </div>
            </div>

            <div class="panel-body">
                <p class="eyebrow">Select attendance action</p>

                <div class="action-options">
                    <button type="button" class="action-choice"
                            id="checkInButton" aria-pressed="false">
                        <span class="action-icon" aria-hidden="true">↘</span>
                        <strong>Check In</strong>
                        <small>Record your arrival time for today.</small>
                    </button>

                    <button type="button" class="action-choice"
                            id="checkOutButton" aria-pressed="false">
                        <span class="action-icon" aria-hidden="true">↗</span>
                        <strong>Check Out</strong>
                        <small>Record your departure after arrival.</small>
                    </button>
                </div>

                <div class="panel-header" style="padding:0 0 14px; border-bottom:0;">
                    <div class="panel-heading">
                        <span class="step-number">02</span>
                        <div>
                            <h3>Facial scan</h3>
                            <p>Position your face within the camera view</p>
                        </div>
                    </div>
                </div>

                <div class="camera-frame" id="cameraFrame">
                    <video id="video" autoplay muted playsinline></video>
                    <div class="scan-line"></div>
                    <div class="camera-guide" id="cameraGuide">
                        Select Check In or Check Out, then start the camera.
                    </div>
                </div>

                <div class="camera-controls">
                    <button id="startCamera" type="button">▶ &nbsp; Start camera</button>
                    <button id="stopCamera" type="button">■ &nbsp; Stop camera</button>
                </div>

                <div id="status" role="status" aria-live="polite">
                    Loading face recognition models...
                </div>

                <div id="result" class="result" aria-live="polite">
                    <h2 id="resultTitle"></h2>
                    <div class="details" id="resultDetails"></div>
                </div>
            </div>
        </section>

        <aside class="side-panel">
            <section class="info-card">
                <h3>How it works</h3>
                <div class="process-list">
                    <div class="process-item">
                        <span class="process-icon">01</span>
                        <div>
                            <strong>Choose your action</strong>
                            <p>Select Check In when arriving or Check Out when leaving.</p>
                        </div>
                    </div>

                    <div class="process-item">
                        <span class="process-icon">02</span>
                        <div>
                            <strong>Verify your face</strong>
                            <p>Face the camera in good lighting so the system can compare your face descriptor.</p>
                        </div>
                    </div>

                    <div class="process-item">
                        <span class="process-icon">03</span>
                        <div>
                            <strong>Confirm attendance</strong>
                            <p>Review the result panel to see whether the selected action was recorded.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="info-card security-card">
                <div class="security-symbol" aria-hidden="true">⌁</div>
                <h3>Recognition system</h3>
                <p>
                    The system compares a detected face descriptor against
                    registered lecturer records before requesting an attendance
                    action.
                </p>
                <div class="privacy-note">
                    <strong>For best results</strong><br>
                    Keep your face clearly visible, look toward the camera,
                    and avoid strong backlighting.
                </div>
            </section>
        </aside>
    </div>
</main>

<footer class="footer">
    UNIVERSITY LECTURER ATTENDANCE MANAGEMENT SYSTEM &nbsp;·&nbsp;
    <span>FACIAL RECOGNITION</span>
</footer>

<script>
    const video = document.getElementById("video");
    const startButton = document.getElementById("startCamera");
    const stopButton = document.getElementById("stopCamera");
    const checkInButton = document.getElementById("checkInButton");
    const checkOutButton = document.getElementById("checkOutButton");
    const cameraGuide = document.getElementById("cameraGuide");
    const cameraFrame = document.getElementById("cameraFrame");
    const statusBox = document.getElementById("status");
    const resultBox = document.getElementById("result");
    const resultTitle = document.getElementById("resultTitle");
    const resultDetails = document.getElementById("resultDetails");

    let stream = null;
    let recognitionRunning = false;
    let recognitionInterval = null;
    let processing = false;
    let selectedAction = null;
    let modelsLoaded = false;

    function setStatus(message) {
        statusBox.textContent = message;
    }

    function showResult(type, title, lines) {
        resultBox.style.display = "block";
        resultBox.className = "result " + type;
        resultTitle.textContent = title;
        resultDetails.replaceChildren();

        lines.forEach(line => {
            const row = document.createElement("div");
            row.textContent = line;
            resultDetails.appendChild(row);
        });
    }

    function selectAction(action) {
        if (recognitionRunning || processing) {
            setStatus("Stop the camera before changing the attendance action.");
            return;
        }

        selectedAction = action;

        checkInButton.classList.toggle("selected", action === "check_in");
        checkOutButton.classList.toggle("selected", action === "check_out");

        checkInButton.setAttribute("aria-pressed", String(action === "check_in"));
        checkOutButton.setAttribute("aria-pressed", String(action === "check_out"));

        cameraGuide.textContent = action === "check_in"
            ? "Selected: CHECK IN. Position your face in the centre."
            : "Selected: CHECK OUT. Position your face in the centre.";

        setStatus(action === "check_in"
            ? "Check In selected. Start the camera when ready."
            : "Check Out selected. Start the camera when ready.");

        resultBox.style.display = "none";
    }

    async function loadModels() {
        try {
            setStatus("Loading face recognition models...");

            await faceapi.nets.tinyFaceDetector.loadFromUri("../models");
            await faceapi.nets.faceLandmark68Net.loadFromUri("../models");
            await faceapi.nets.faceRecognitionNet.loadFromUri("../models");

            modelsLoaded = true;
            setStatus("Face recognition system ready. Select Check In or Check Out.");
        } catch (error) {
            console.error(error);
            setStatus("Failed to load face recognition models.");

            showResult("error", "System Error", [
                "The facial recognition models could not be loaded.",
                "Check that the models folder and files are present."
            ]);
        }
    }

    async function startCamera() {
        if (!modelsLoaded) {
            setStatus("Please wait until the face recognition models finish loading.");
            return;
        }

        if (!selectedAction) {
            setStatus("Please select Check In or Check Out first.");
            return;
        }

        try {
            stopCamera();

            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: 720,
                    height: 480,
                    facingMode: "user"
                },
                audio: false
            });

            video.srcObject = stream;
            await video.play();

            recognitionRunning = true;
            cameraFrame.classList.add("scanning");

            cameraGuide.textContent = selectedAction === "check_in"
                ? "CHECK IN selected — look directly at the camera."
                : "CHECK OUT selected — look directly at the camera.";

            setStatus("Camera active. Looking for lecturer...");
            resultBox.style.display = "none";

            recognitionInterval = setInterval(recognizeLecturer, 1500);
        } catch (error) {
            console.error(error);
            setStatus("Camera could not be started.");

            showResult("error", "Camera Error", [
                "Please allow camera access and try again.",
                "Ensure another application is not using the camera."
            ]);
        }
    }

    function stopCamera() {
        recognitionRunning = false;
        cameraFrame.classList.remove("scanning");

        if (recognitionInterval) {
            clearInterval(recognitionInterval);
            recognitionInterval = null;
        }

        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }

        video.srcObject = null;

        cameraGuide.textContent = selectedAction
            ? "Camera stopped. You can restart when ready."
            : "Select Check In or Check Out, then start the camera.";
    }

    async function getLecturers() {
        const response = await fetch("get_lecturers.php", {
            cache: "no-store"
        });

        if (!response.ok) {
            throw new Error("Could not retrieve lecturers.");
        }

        return await response.json();
    }

    function calculateDistance(descriptor1, descriptor2) {
        if (descriptor1.length !== descriptor2.length) {
            return Infinity;
        }

        let sum = 0;

        for (let i = 0; i < descriptor1.length; i++) {
            const difference = descriptor1[i] - descriptor2[i];
            sum += difference * difference;
        }

        return Math.sqrt(sum);
    }

    async function recordAttendance(lecturerId, action) {
        const formData = new FormData();
        formData.append("lecturer_id", lecturerId);
        formData.append("action", action);

        const response = await fetch("record_attendance.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (!response.ok && !data.message) {
            throw new Error("Attendance request failed.");
        }

        return data;
    }

    async function recognizeLecturer() {
        if (!recognitionRunning || processing || video.readyState !== 4) {
            return;
        }

        processing = true;

        try {
            const detection = await faceapi
                .detectSingleFace(
                    video,
                    new faceapi.TinyFaceDetectorOptions()
                )
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                setStatus("No face detected. Please face the camera.");
                return;
            }

            setStatus("Face detected. Identifying lecturer...");

            const lecturers = await getLecturers();

            if (!Array.isArray(lecturers)) {
                throw new Error("Invalid lecturer data received.");
            }

            let bestMatch = null;
            let bestDistance = Infinity;

            for (const lecturer of lecturers) {
                if (!lecturer["Face_data"]) {
                    continue;
                }

                let storedDescriptor;

                try {
                    storedDescriptor = new Float32Array(
                        JSON.parse(lecturer["Face_data"])
                    );
                } catch (error) {
                    console.warn(
                        "Invalid face data for lecturer:",
                        lecturer["Lecturer id"]
                    );
                    continue;
                }

                if (storedDescriptor.length !== 128) {
                    continue;
                }

                const distance = calculateDistance(
                    detection.descriptor,
                    storedDescriptor
                );

                if (distance < bestDistance) {
                    bestDistance = distance;
                    bestMatch = lecturer;
                }
            }

            const recognitionThreshold = 0.6;

            if (!bestMatch || bestDistance > recognitionThreshold) {
                setStatus("Face not recognized.");

                showResult("error", "Lecturer Not Recognized", [
                    "The detected face did not match a registered lecturer.",
                    "Please try again with good lighting."
                ]);
                return;
            }

            // Stop repeated automatic submissions after a successful match.
            stopCamera();
            setStatus("Lecturer recognized. Recording selected action...");

            const actionBeingRecorded = selectedAction;

            const attendance = await recordAttendance(
                bestMatch["Lecturer id"],
                actionBeingRecorded
            );

            const lecturerId = String(bestMatch["Lecturer id"] ?? "");
            const fullName = String(bestMatch["Full name"] ?? "");
            const department = String(bestMatch["Department"] ?? "");

            if (attendance.success) {
                const isCheckIn = actionBeingRecorded === "check_in";
                const eventTime = isCheckIn
                    ? attendance.arrival_time
                    : attendance.departure_time;

                showResult(
                    "success",
                    isCheckIn
                        ? "Check In Successful ✓"
                        : "Check Out Successful ✓",
                    [
                        "Lecturer ID: " + lecturerId,
                        "Name: " + fullName,
                        "Department: " + department,
                        "Date: " + (attendance.date || ""),
                        (isCheckIn ? "Arrival time: " : "Departure time: ")
                            + (eventTime || ""),
                        isCheckIn
                            ? "Your arrival has been recorded."
                            : "Your departure has been recorded."
                    ]
                );

                setStatus("Attendance action completed successfully.");
            } else {
                const isDuplicate = attendance.already_marked ||
                    attendance.already_checked_out;

                showResult(
                    isDuplicate ? "warning" : "error",
                    isDuplicate
                        ? "Action Already Recorded"
                        : "Attendance Not Recorded",
                    [
                        "Lecturer ID: " + lecturerId,
                        "Name: " + fullName,
                        attendance.message ||
                            "The requested action could not be recorded."
                    ]
                );

                setStatus("Attendance action was not recorded.");
            }
        } catch (error) {
            console.error(error);
            setStatus("Recognition or attendance error.");

            showResult("error", "System Error", [
                error.message || "An unexpected error occurred."
            ]);
        } finally {
            processing = false;
        }
    }

    checkInButton.addEventListener("click", () => selectAction("check_in"));
    checkOutButton.addEventListener("click", () => selectAction("check_out"));
    startButton.addEventListener("click", startCamera);

    stopButton.addEventListener("click", () => {
        stopCamera();
        setStatus("Camera stopped.");
    });

    loadModels();
</script>
</body>
</html>
