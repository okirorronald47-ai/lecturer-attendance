<?php
session_start();

require_once __DIR__ . "/../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set("Africa/Kampala");

$message = "";
$message_type = "success";

function h($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* =========================================
   CREATE ADMINISTRATOR
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_admin"])
) {
    $username = trim($_POST["username"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $username === "" ||
        $full_name === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {
        $message = "Please complete all fields.";
        $message_type = "error";

    } elseif (
        strlen($username) > 100 ||
        strlen($full_name) > 150
    ) {
        $message = "Username or full name is too long.";
        $message_type = "error";

    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
        $message_type = "error";

    } elseif (strlen($password) > 72) {
        $message = "Please use a password of 72 characters or fewer.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {
        $message = "The passwords do not match.";
        $message_type = "error";

    } else {
        $check_sql = "
            SELECT `ID`
            FROM admins
            WHERE `Username` = ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        if (!$check_stmt) {
            $message = "Could not check the username.";
            $message_type = "error";

        } else {
            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (
                $check_result &&
                mysqli_num_rows($check_result) > 0
            ) {
                $message = "That username already exists.";
                $message_type = "error";

            } else {
                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insert_sql = "
                    INSERT INTO admins
                        (`Username`, `Full name`, `Password`)
                    VALUES (?, ?, ?)
                ";

                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_sql
                );

                if ($insert_stmt) {
                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "sss",
                        $username,
                        $full_name,
                        $password_hash
                    );

                    if (mysqli_stmt_execute($insert_stmt)) {
                        $message = "Administrator account created successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Could not create the account. Please try again.";
                        $message_type = "error";
                    }

                    mysqli_stmt_close($insert_stmt);

                } else {
                    $message = "Could not prepare the account creation request.";
                    $message_type = "error";
                }
            }

            mysqli_stmt_close($check_stmt);
        }
    }
}

/* =========================================
   FETCH ADMINISTRATORS
========================================= */

$admins = [];

$list_sql = "
    SELECT `ID`, `Username`, `Full name`, `Created at`
    FROM admins
    ORDER BY `ID` DESC
";

$list_result = mysqli_query($conn, $list_sql);

if ($list_result) {
    while ($row = mysqli_fetch_assoc($list_result)) {
        $admins[] = $row;
    }

    mysqli_free_result($list_result);
} else {
    if ($message === "") {
        $message = "Unable to load administrator accounts.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Administrators | Lecturer Attendance System</title>

    <style>
        :root {
            --bg: #050b18;
            --panel: rgba(13, 25, 47, .95);
            --panel-light: #101f38;
            --cyan: #00e5ff;
            --cyan-soft: rgba(0, 229, 255, .10);
            --blue: #367cff;
            --green: #00e6a0;
            --gold: #ffc857;
            --red: #ff7185;
            --text: #eaf6ff;
            --muted: #8fa7c5;
            --border: rgba(0, 229, 255, .19);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(
                    ellipse at 10% 0%,
                    rgba(0, 229, 255, .12),
                    transparent 35%
                ),
                radial-gradient(
                    ellipse at 95% 20%,
                    rgba(54, 124, 255, .12),
                    transparent 33%
                ),
                var(--bg);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            opacity: .23;
            background-image:
                linear-gradient(
                    rgba(0, 229, 255, .045) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(0, 229, 255, .045) 1px,
                    transparent 1px
                );
            background-size: 38px 38px;
        }

        header {
            border-bottom: 1px solid var(--border);
            background: rgba(5, 11, 24, .92);
            box-shadow: 0 8px 35px rgba(0, 0, 0, .25);
        }

        .header-inner {
            width: min(1400px, calc(100% - 40px));
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
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(0, 229, 255, .48);
            border-radius: 13px;
            background: linear-gradient(145deg, #173452, #07101f);
            color: var(--cyan);
            box-shadow:
                0 0 22px rgba(0, 229, 255, .13),
                inset 0 0 15px rgba(0, 229, 255, .06);
            font-size: 20px;
        }

        .brand small {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 15px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #10213a;
            color: #d5eaff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 750;
            transition: .2s ease;
        }

        .back-link:hover {
            border-color: var(--cyan);
            color: var(--cyan);
            transform: translateY(-2px);
        }

        main {
            width: min(1400px, calc(100% - 40px));
            margin: 32px auto 50px;
        }

        .intro {
            position: relative;
            overflow: hidden;
            margin-bottom: 23px;
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background:
                linear-gradient(
                    120deg,
                    rgba(14, 31, 56, .97),
                    rgba(7, 17, 34, .94)
                );
            box-shadow:
                0 15px 40px rgba(0, 0, 0, .20),
                inset 0 1px rgba(255, 255, 255, .025);
        }

        .intro::after {
            content: "";
            position: absolute;
            top: -100px;
            right: -35px;
            width: 240px;
            height: 240px;
            border: 1px solid rgba(0, 229, 255, .14);
            border-radius: 50%;
            box-shadow:
                0 0 0 25px rgba(0, 229, 255, .025),
                0 0 0 55px rgba(0, 229, 255, .018);
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

        .intro h2 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-size: clamp(26px, 4vw, 36px);
            letter-spacing: -.6px;
        }

        .intro p {
            position: relative;
            z-index: 1;
            max-width: 760px;
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .message {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
        }

        .message.success {
            border: 1px solid rgba(0, 230, 160, .28);
            background: rgba(0, 230, 160, .08);
            color: var(--green);
        }

        .message.error {
            border: 1px solid rgba(255, 113, 133, .30);
            background: rgba(255, 113, 133, .08);
            color: var(--red);
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(290px, 370px) minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }

        .panel {
            min-width: 0;
            padding: 24px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background:
                linear-gradient(
                    145deg,
                    rgba(14, 28, 51, .97),
                    rgba(8, 18, 35, .98)
                );
            box-shadow:
                0 15px 35px rgba(0, 0, 0, .20),
                inset 0 1px rgba(255, 255, 255, .025);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 9px;
        }

        .panel h3 {
            margin: 0;
            font-size: 17px;
        }

        .heading-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--cyan-soft);
            color: var(--cyan);
            font-size: 17px;
        }

        .panel-description {
            margin: 0 0 23px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .field {
            margin-bottom: 17px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #bdd0e6;
            font-size: 11px;
            font-weight: 750;
            letter-spacing: .4px;
        }

        input {
            width: 100%;
            min-height: 45px;
            padding: 11px 12px;
            border: 1px solid rgba(143, 167, 197, .25);
            border-radius: 9px;
            outline: none;
            background: #071326;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            transition: .2s ease;
        }

        input::placeholder {
            color: #647c9a;
        }

        input:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(0, 229, 255, .09);
        }

        .help {
            display: block;
            margin-top: 7px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 45px;
            padding: 12px 16px;
            border: 1px solid rgba(0, 229, 255, .35);
            border-radius: 9px;
            background: linear-gradient(110deg, #007eaa, #00a8b8);
            color: white;
            cursor: pointer;
            font: inherit;
            font-size: 12px;
            font-weight: 800;
            transition: .2s ease;
        }

        .button:hover {
            box-shadow: 0 7px 22px rgba(0, 229, 255, .18);
            transform: translateY(-2px);
        }

        .button-full {
            width: 100%;
        }

        .security-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 19px;
            padding: 13px;
            border: 1px solid rgba(0, 229, 255, .14);
            border-radius: 10px;
            background: rgba(0, 229, 255, .045);
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        .security-note span {
            color: var(--cyan);
            font-size: 16px;
        }

        .count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 1px 0 18px;
            padding: 8px 11px;
            border: 1px solid rgba(0, 229, 255, .22);
            border-radius: 20px;
            background: var(--cyan-soft);
            color: var(--cyan);
            font-size: 11px;
            font-weight: 800;
        }

        .count::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 8px currentColor;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 11px;
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #101f37;
        }

        th {
            padding: 14px 11px;
            border-bottom: 1px solid var(--border);
            color: #91abc9;
            font-size: 10px;
            letter-spacing: .8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 14px 11px;
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

        .admin-id {
            color: var(--cyan);
            font-weight: 800;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border: 1px solid rgba(0, 230, 160, .22);
            border-radius: 20px;
            background: rgba(0, 230, 160, .08);
            color: var(--green);
            font-size: 10px;
            font-weight: 800;
        }

        .admin-badge::before {
            content: "";
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .empty {
            padding: 35px 12px;
            color: var(--muted);
            text-align: center;
            white-space: normal;
            line-height: 1.7;
        }

        .empty-icon {
            display: block;
            margin-bottom: 10px;
            color: var(--cyan);
            font-size: 28px;
        }

        .table-note {
            margin-top: 14px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        @media (max-width: 1000px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .header-inner {
                align-items: flex-start;
                flex-direction: column;
                padding: 16px 0;
            }

            main {
                width: calc(100% - 24px);
                margin-top: 20px;
            }

            .intro,
            .panel {
                padding: 18px;
            }

            .intro::after {
                opacity: .4;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="header-inner">

        <div class="brand">
            <div class="brand-icon">⌘</div>
            <div>
                University Lecturer Attendance System
                <small>Administrator control centre</small>
            </div>
        </div>

        <a class="back-link" href="dashboard.php">
            ← Back to Dashboard
        </a>

    </div>
</header>

<main>

    <section class="intro">
        <div class="eyebrow">System access management</div>
        <h2>Manage Administrators</h2>
        <p>
            Create and review administrator accounts authorized to manage
            the lecturer attendance system. Account passwords are hashed
            before being stored in the database.
        </p>
    </section>

    <?php if ($message !== ""): ?>
        <div
            class="message <?= h($message_type) ?>"
            role="status"
        >
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <div class="layout">

        <section class="panel">

            <div class="panel-heading">
                <h3>Add Administrator</h3>
                <div class="heading-icon">＋</div>
            </div>

            <p class="panel-description">
                Create an additional administrator account with a unique
                username and a strong password.
            </p>

            <form method="POST" action="manage_admins.php">

                <div class="field">
                    <label for="full_name">FULL NAME</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="150"
                        autocomplete="name"
                        placeholder="Enter administrator's name"
                        required
                    >
                </div>

                <div class="field">
                    <label for="username">USERNAME</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        maxlength="100"
                        autocomplete="username"
                        placeholder="Choose a username"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">PASSWORD</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
                        required
                    >
                    <span class="help">
                        Use a unique password that is difficult to guess.
                    </span>
                </div>

                <div class="field">
                    <label for="confirm_password">CONFIRM PASSWORD</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        placeholder="Enter the password again"
                        required
                    >
                </div>

                <button
                    type="submit"
                    name="add_admin"
                    value="1"
                    class="button button-full"
                >
                    ＋ Create Administrator
                </button>

            </form>

            <div class="security-note">
                <span>⛨</span>
                <div>
                    <strong>Security notice</strong><br>
                    Passwords are stored as hashes and are not shown
                    in the administrator list.
                </div>
            </div>

        </section>

        <section class="panel">

            <div class="panel-heading">
                <h3>Registered Administrators</h3>
                <div class="heading-icon">◎</div>
            </div>

            <p class="panel-description">
                Review administrator accounts registered in the system.
                Passwords are deliberately excluded from this list.
            </p>

            <span class="count">
                Total administrators: <?= count($admins) ?>
            </span>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full name</th>
                            <th>Username</th>
                            <th>Created</th>
                            <th>Account type</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($admins) > 0): ?>

                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td class="admin-id">
                                    <?= h($admin["ID"]) ?>
                                </td>

                                <td>
                                    <?= h($admin["Full name"]) ?>
                                </td>

                                <td>
                                    <?= h($admin["Username"]) ?>
                                </td>

                                <td>
                                    <?= h($admin["Created at"] ?? "—") ?>
                                </td>

                                <td>
                                    <span class="admin-badge">
                                        Administrator
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty">
                                <span class="empty-icon">◎</span>
                                No administrator accounts found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <p class="table-note">
                Account records are displayed from your existing MySQL
                database. This page does not display or modify passwords.
            </p>

        </section>

    </div>

</main>

</body>
</html>
