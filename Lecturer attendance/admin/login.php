
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once "../config/database.php";

date_default_timezone_set("Africa/Kampala");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $message = "Please enter your username and password.";

    } else {

        $sql = "SELECT * FROM admins WHERE `Username` = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $admin = $result ? mysqli_fetch_assoc($result) : null;

            mysqli_stmt_close($stmt);

            $authenticated = false;

            if ($admin) {

                $stored_password = (string) $admin["Password"];
                $password_info = password_get_info($stored_password);

                if (!empty($password_info["algo"])) {

                    $authenticated = password_verify(
                        $password,
                        $stored_password
                    );

                } else {

                    // Support older accounts with plain-text passwords.
                    $authenticated = hash_equals(
                        $stored_password,
                        (string) $password
                    );
                }

                // Upgrade old passwords or refresh outdated hashes.
                if (
                    $authenticated &&
                    (
                        empty($password_info["algo"]) ||
                        password_needs_rehash(
                            $stored_password,
                            PASSWORD_DEFAULT
                        )
                    )
                ) {

                    $new_hash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $update_sql = "UPDATE admins
                                   SET `Password` = ?
                                   WHERE `ID` = ?";

                    $update_stmt = mysqli_prepare($conn, $update_sql);

                    if ($update_stmt) {
                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "si",
                            $new_hash,
                            $admin["ID"]
                        );

                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                    }
                }
            }

            if ($authenticated) {

                session_regenerate_id(true);

                $_SESSION["admin_id"] = $admin["ID"];
                $_SESSION["admin_username"] = $admin["Username"];
                $_SESSION["admin_name"] = $admin["Full name"];

                header("Location: dashboard.php");
                exit();

            } else {

                $message = "Invalid username or password.";
            }

        } else {

            $message = "Login could not be processed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#06111f">

    <title>Admin Login | Lecturer Attendance System</title>

    <style>
        :root {
            color-scheme: dark;
            --bg: #06111f;
            --panel: #0b1a2b;
            --panel-light: #10243a;
            --cyan: #22d3ee;
            --cyan-light: #67e8f9;
            --text: #edfaff;
            --muted: #91a8bb;
            --line: rgba(103, 232, 249, .16);
            --danger: #fda4af;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 28px;
            display: grid;
            place-items: center;
            overflow-x: hidden;
            color: var(--text);
            background:
                radial-gradient(ellipse at 12% 10%,
                    rgba(34, 211, 238, .12), transparent 35%),
                radial-gradient(ellipse at 90% 85%,
                    rgba(37, 99, 235, .12), transparent 38%),
                var(--bg);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        body::before {
            position: fixed;
            inset: 0;
            z-index: -1;
            content: "";
            pointer-events: none;
            opacity: .15;
            background-image:
                linear-gradient(rgba(103,232,249,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(103,232,249,.08) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, black, transparent 90%);
        }

        button, input {
            font: inherit;
        }

        .login-shell {
            width: min(100%, 1020px);
            min-height: 620px;
            display: grid;
            grid-template-columns: 1.02fr .98fr;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 26px;
            background: rgba(9, 24, 40, .94);
            box-shadow:
                0 30px 90px rgba(0, 0, 0, .38),
                0 0 45px rgba(34, 211, 238, .05);
        }

        /* LEFT WELCOME PANEL */

        .welcome-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 42px;
            background:
                radial-gradient(circle at 78% 25%,
                    rgba(34,211,238,.16), transparent 32%),
                linear-gradient(145deg, #0d263d, #071421 85%);
            border-right: 1px solid var(--line);
        }

        .welcome-panel::before,
        .welcome-panel::after {
            position: absolute;
            content: "";
            border: 1px solid rgba(34, 211, 238, .18);
            border-radius: 50%;
            pointer-events: none;
        }

        .welcome-panel::before {
            width: 390px;
            height: 390px;
            right: -180px;
            bottom: -100px;
        }

        .welcome-panel::after {
            width: 280px;
            height: 280px;
            right: -125px;
            bottom: -45px;
            box-shadow: 0 0 55px rgba(34,211,238,.04);
        }

        .brand,
        .welcome-copy,
        .panel-footer {
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .brand-mark {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            color: var(--cyan);
            border: 1px solid rgba(34,211,238,.35);
            border-radius: 15px;
            background: rgba(34,211,238,.08);
            box-shadow: inset 0 0 18px rgba(34,211,238,.05);
            font-size: 23px;
            font-weight: 800;
        }

        .brand-name {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .09em;
        }

        .brand-caption {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .welcome-copy {
            max-width: 390px;
            margin: 58px 0;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 18px;
            color: var(--cyan);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--cyan);
            box-shadow: 0 0 12px var(--cyan);
        }

        .welcome-copy h1 {
            margin: 0;
            font-size: clamp(34px, 4vw, 49px);
            line-height: 1.1;
            letter-spacing: -.045em;
        }

        .welcome-copy h1 span {
            color: var(--cyan);
            text-shadow: 0 0 25px rgba(34,211,238,.2);
        }

        .welcome-copy p {
            max-width: 345px;
            margin: 20px 0 0;
            color: #a8bdce;
            font-size: 14px;
            line-height: 1.85;
        }

        .feature-list {
            display: grid;
            gap: 13px;
            margin-top: 30px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 11px;
            color: #d2e5f1;
            font-size: 12px;
        }

        .feature-icon {
            width: 27px;
            height: 27px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            color: var(--cyan);
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(34,211,238,.06);
        }

        .panel-footer {
            color: #70889c;
            font-size: 11px;
            line-height: 1.7;
        }

        /* RIGHT LOGIN PANEL */

        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }

        .form-content {
            width: 100%;
            max-width: 370px;
        }

        .form-heading {
            margin-bottom: 31px;
        }

        .form-heading .mini-label {
            margin: 0 0 10px;
            color: var(--cyan);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .form-heading h2 {
            margin: 0;
            font-size: 29px;
            letter-spacing: -.04em;
        }

        .form-heading p {
            margin: 10px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .error-message {
            margin: 0 0 20px;
            padding: 13px 14px;
            color: var(--danger);
            background: rgba(244,63,94,.09);
            border: 1px solid rgba(251,113,133,.28);
            border-radius: 11px;
            font-size: 13px;
            line-height: 1.6;
        }

        .field {
            margin-bottom: 21px;
        }

        .field label {
            display: block;
            margin-bottom: 9px;
            color: #d7e7f1;
            font-size: 12px;
            font-weight: 700;
        }

        .field input {
            width: 100%;
            min-height: 50px;
            padding: 13px 15px;
            color: var(--text);
            background: #071522;
            border: 1px solid rgba(145,168,187,.2);
            border-radius: 11px;
            outline: none;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s;
        }

        .field input::placeholder {
            color: #5f778b;
        }

        .field input:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 4px rgba(34,211,238,.09);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 53px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            transform: translateY(-50%);
            color: #8ba6ba;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .toggle-password:hover {
            color: var(--cyan);
            background: rgba(34,211,238,.08);
        }

        .toggle-password:focus-visible,
        .forgot-link:focus-visible,
        .login-button:focus-visible,
        .modal-close:focus-visible {
            outline: 2px solid var(--cyan);
            outline-offset: 3px;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .password-options {
            display: flex;
            justify-content: flex-end;
            margin-top: -7px;
            margin-bottom: 22px;
        }

        .forgot-link {
            padding: 4px 0;
            color: var(--cyan);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
        }

        .forgot-link:hover {
            color: var(--cyan-light);
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            min-height: 51px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            color: #04131d;
            background: linear-gradient(100deg, #22d3ee, #67e8f9);
            border: 0;
            border-radius: 11px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 850;
            box-shadow: 0 7px 25px rgba(34,211,238,.12);
            transition: transform .2s, box-shadow .2s, filter .2s;
        }

        .login-button:hover {
            transform: translateY(-2px);
            filter: brightness(1.04);
            box-shadow: 0 10px 30px rgba(34,211,238,.2);
        }

        .login-button .arrow {
            font-size: 19px;
            line-height: 1;
        }

        .secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin: 23px 0 0;
            color: #849bad;
            font-size: 11px;
        }

        .secure-symbol {
            width: 19px;
            height: 19px;
            display: grid;
            place-items: center;
            color: var(--cyan);
            border: 1px solid rgba(34,211,238,.2);
            border-radius: 50%;
            font-size: 11px;
        }

        .back-home {
            display: block;
            margin-top: 24px;
            color: #8198aa;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
        }

        .back-home:hover {
            color: var(--cyan);
        }

        /* PASSWORD RECOVERY DIALOG */

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(2,8,15,.82);
            backdrop-filter: blur(8px);
        }

        .modal-backdrop.open {
            display: flex;
        }

        .recovery-modal {
            width: min(100%, 430px);
            padding: 27px;
            background: #0b1a2b;
            border: 1px solid var(--line);
            border-radius: 17px;
            box-shadow: 0 25px 80px rgba(0,0,0,.45);
        }

        .modal-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            margin-bottom: 18px;
            color: var(--cyan);
            background: rgba(34,211,238,.08);
            border: 1px solid var(--line);
            border-radius: 13px;
            font-size: 20px;
        }

        .recovery-modal h2 {
            margin: 0 0 12px;
            font-size: 22px;
        }

        .recovery-modal p {
            color: #a2b6c7;
            font-size: 13px;
            line-height: 1.8;
        }

        .recovery-warning {
            padding: 12px;
            color: #f6d58b !important;
            background: rgba(234,179,8,.07);
            border: 1px solid rgba(234,179,8,.2);
            border-radius: 10px;
            font-size: 12px !important;
        }

        .modal-close {
            width: 100%;
            min-height: 45px;
            margin-top: 12px;
            color: #04131d;
            background: var(--cyan);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
        }

        .modal-close:hover {
            background: var(--cyan-light);
        }

        @media (max-width: 780px) {
            body {
                padding: 18px;
            }

            .login-shell {
                width: min(100%, 510px);
                min-height: auto;
                grid-template-columns: 1fr;
            }

            .welcome-panel {
                min-height: 290px;
                padding: 28px;
                border-right: none;
                border-bottom: 1px solid var(--line);
            }

            .welcome-copy {
                margin: 37px 0 10px;
            }

            .welcome-copy h1 {
                font-size: 36px;
            }

            .welcome-copy p {
                margin-top: 13px;
            }

            .feature-list,
            .panel-footer {
                display: none;
            }

            .form-panel {
                padding: 35px 28px 38px;
            }

            .form-heading {
                margin-bottom: 26px;
            }
        }

        @media (max-width: 380px) {
            body {
                padding: 9px;
            }

            .welcome-panel,
            .form-panel {
                padding-right: 21px;
                padding-left: 21px;
            }

            .welcome-copy h1 {
                font-size: 31px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

<main class="login-shell">

    <section class="welcome-panel" aria-label="Administration portal">

        <div class="brand">
            <div class="brand-mark" aria-hidden="true">U</div>

            <div>
                <p class="brand-name">UNIVERSITY ATTENDANCE</p>
                <p class="brand-caption">Lecturer management system</p>
            </div>
        </div>

        <div class="welcome-copy">

            <p class="eyebrow">
                <span class="status-dot" aria-hidden="true"></span>
                Administration portal
            </p>

            <h1>
                Attendance,<br>
                made <span>intelligent.</span>
            </h1>

            <p>
                Manage lecturer attendance, review records and monitor
                university attendance activity from one secure dashboard.
            </p>

            <div class="feature-list">

                <div class="feature">
                    <span class="feature-icon" aria-hidden="true">⌁</span>
                    Facial recognition attendance
                </div>

                <div class="feature">
                    <span class="feature-icon" aria-hidden="true">▤</span>
                    Attendance records and reports
                </div>

                <div class="feature">
                    <span class="feature-icon" aria-hidden="true">◈</span>
                    Administrator management
                </div>

            </div>
        </div>

        <div class="panel-footer">
            UNIVERSITY LECTURER ATTENDANCE SYSTEM<br>
            Secure administration access
        </div>

    </section>

    <section class="form-panel" aria-labelledby="login-title">

        <div class="form-content">

            <div class="form-heading">
                <p class="mini-label">Administrator access</p>

                <h2 id="login-title">Welcome back</h2>

                <p>
                    Enter your credentials to access your dashboard.
                </p>
            </div>

            <?php if ($message !== "") { ?>
                <p class="error-message" role="alert">
                    <?php
                    echo htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>
            <?php } ?>

            <form method="POST" action="">

                <div class="field">
                    <label for="username">Username</label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Show password"
                            aria-pressed="false"
                            title="Show password"
                        >
                            <svg id="eyeIcon"
                                 viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="password-options">
                    <button
                        type="button"
                        class="forgot-link"
                        id="forgotPassword"
                    >
                        Forgot Password?
                    </button>
                </div>

                <button
                    class="login-button"
                    type="submit"
                    name="login"
                >
                    Sign in to dashboard
                    <span class="arrow" aria-hidden="true">→</span>
                </button>

            </form>

            <p class="secure-note">
                <span class="secure-symbol" aria-hidden="true">✓</span>
                Protected administrator access
            </p>

            <a class="back-home" href="../index.php">
                ← Back to home page
            </a>

        </div>
    </section>

</main>

<!-- Password recovery information dialog -->
<div
    class="modal-backdrop"
    id="recoveryBackdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="recoveryTitle"
    aria-hidden="true"
>
    <div class="recovery-modal">

        <div class="modal-icon" aria-hidden="true">⌑</div>

        <h2 id="recoveryTitle">Forgot your password?</h2>

        <p>
            Password recovery must be handled through an authorized
            administrator recovery procedure.
        </p>

        <p class="recovery-warning">
            Email-based password recovery is not configured for this
            installation. No reset email has been sent.
        </p>

        <p>
            Do not share your current password with anyone.
        </p>

        <button
            type="button"
            class="modal-close"
            id="closeRecovery"
        >
            Understood
        </button>

    </div>
</div>

<script>
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
    const eyeIcon = document.getElementById("eyeIcon");

    const forgotPassword = document.getElementById("forgotPassword");
    const recoveryBackdrop = document.getElementById("recoveryBackdrop");
    const closeRecovery = document.getElementById("closeRecovery");

    // Show or hide the password.
    togglePassword.addEventListener("click", function () {

        const shouldShow = passwordInput.type === "password";

        passwordInput.type = shouldShow ? "text" : "password";

        togglePassword.setAttribute(
            "aria-label",
            shouldShow ? "Hide password" : "Show password"
        );

        togglePassword.setAttribute(
            "title",
            shouldShow ? "Hide password" : "Show password"
        );

        togglePassword.setAttribute(
            "aria-pressed",
            String(shouldShow)
        );

        if (shouldShow) {
            eyeIcon.innerHTML = `
                <path d="M3 3l18 18"></path>
                <path d="M10.6 5.2A10.8 10.8 0 0 1 12 5
                         c6.4 0 10 7 10 7a17 17 0 0 1-3.1 3.9"></path>
                <path d="M6.2 6.2C3.5 8.1 2 12 2 12
                         s3.6 7 10 7a10 10 0 0 0 4-.8"></path>
                <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path d="M2 12s3.6-7 10-7 10 7 10 7
                         -3.6 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
        }
    });

    // Open password recovery instructions.
    forgotPassword.addEventListener("click", function () {
        recoveryBackdrop.classList.add("open");
        recoveryBackdrop.setAttribute("aria-hidden", "false");
        closeRecovery.focus();
    });

    function closeRecoveryDialog() {
        recoveryBackdrop.classList.remove("open");
        recoveryBackdrop.setAttribute("aria-hidden", "true");
        forgotPassword.focus();
    }

    closeRecovery.addEventListener("click", closeRecoveryDialog);

    // Close the dialog by clicking outside it.
    recoveryBackdrop.addEventListener("click", function (event) {
        if (event.target === recoveryBackdrop) {
            closeRecoveryDialog();
        }
    });

    // Allow Escape to close the dialog.
    document.addEventListener("keydown", function (event) {
        if (
            event.key === "Escape" &&
            recoveryBackdrop.classList.contains("open")
        ) {
            closeRecoveryDialog();
        }
    });
</script>

</body>
</html>
