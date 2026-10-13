<?php

require_once "../config/database.php";

$sql = "SELECT * FROM lecturers";

$result = mysqli_query($conn, $sql);

$lecturerCount = $result ? mysqli_num_rows($result) : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta name="theme-color" content="#102a43">

    <title>Registered Lecturers | University Attendance System</title>

    <style>
        :root {
            --navy: #102a43;
            --navy-deep: #0b1f33;
            --teal: #0f766e;
            --teal-dark: #0b5f59;
            --teal-pale: #e7f5f2;
            --gold: #d6a84f;
            --ink: #172b4d;
            --muted: #68788c;
            --line: #e1e8ef;
            --page: #f3f6f9;
            --white: #fff;
            --green: #16734a;
            --green-pale: #e8f6ee;
            --amber: #8a6519;
            --amber-pale: #fbf5e8;
            --shadow: 0 10px 28px rgba(16, 42, 67, 0.07);
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            background:
                radial-gradient(circle at 90% 0%, rgba(15, 118, 110, 0.07), transparent 25rem),
                var(--page);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        .topbar {
            color: var(--white);
            background: linear-gradient(110deg, var(--navy-deep), var(--navy));
            border-bottom: 3px solid var(--gold);
        }

        .topbar-inner {
            width: min(1180px, calc(100% - 40px));
            min-height: 82px;
            margin: 0 auto;
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
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 13px;
            font-size: 20px;
            font-weight: 800;
        }

        .brand h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 750;
        }

        .brand p {
            margin: 5px 0 0;
            color: rgba(255, 255, 255, 0.68);
            font-size: 12px;
        }

        .header-link {
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .header-link:hover {
            color: #c9e9e5;
        }

        .page {
            width: min(1180px, calc(100% - 40px));
            margin: 32px auto;
        }

        .page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }

        .eyebrow {
            margin: 0 0 7px;
            color: var(--teal);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.13em;
            text-transform: uppercase;
        }

        .page-heading h2 {
            margin: 0;
            color: var(--navy);
            font-size: clamp(25px, 3vw, 32px);
            letter-spacing: -0.035em;
        }

        .page-heading p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .button {
            display: inline-flex;
            min-height: 43px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 15px;
            color: #fff;
            background: var(--teal);
            border: 0;
            border-radius: 9px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 750;
            white-space: nowrap;
            transition: background 150ms ease, transform 150ms ease;
        }

        .button:hover {
            background: var(--teal-dark);
            transform: translateY(-1px);
        }

        .summary-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
            padding: 18px 20px;
            background: var(--white);
            border: 1px solid rgba(16, 42, 67, 0.06);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }

        .summary-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 750;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .summary-number {
            margin-top: 5px;
            color: var(--navy);
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .summary-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            color: var(--teal);
            background: var(--teal-pale);
            border-radius: 13px;
            font-size: 20px;
            font-weight: 800;
        }

        .table-card {
            overflow: hidden;
            background: var(--white);
            border: 1px solid rgba(16, 42, 67, 0.06);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .table-heading {
            padding: 20px 22px;
            border-bottom: 1px solid var(--line);
        }

        .table-heading h3 {
            margin: 0;
            color: var(--navy);
            font-size: 16px;
        }

        .table-heading p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: #f5f8fa;
        }

        th {
            padding: 13px 15px;
            color: #53677b;
            border-bottom: 1px solid var(--line);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 14px 15px;
            color: #30465d;
            border-bottom: 1px solid #edf1f5;
            font-size: 12px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr:hover {
            background: #f8fbfc;
        }

        .row-number {
            color: #8795a4;
            font-size: 11px;
        }

        .lecturer-id {
            color: var(--navy);
            font-weight: 750;
            white-space: nowrap;
        }

        .lecturer-name {
            color: var(--ink);
            font-weight: 700;
        }

        .face-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            color: var(--green);
            background: var(--green-pale);
            border-radius: 20px;
            font-size: 10px;
            font-weight: 750;
            white-space: nowrap;
        }

        .face-missing {
            color: var(--amber);
            background: var(--amber-pale);
        }

        .empty-state {
            padding: 45px 20px;
            color: var(--muted);
            text-align: center;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            margin: 0 auto 12px;
            color: var(--teal);
            background: var(--teal-pale);
            border-radius: 15px;
            font-size: 21px;
        }

        .empty-state h3 {
            margin: 0;
            color: var(--navy);
            font-size: 15px;
        }

        .empty-state p {
            margin: 7px 0 17px;
            font-size: 12px;
        }

        .bottom-links {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            margin-top: 18px;
        }

        .text-link {
            color: var(--teal);
            text-decoration: none;
            font-size: 12px;
            font-weight: 750;
        }

        .text-link:hover {
            color: var(--teal-dark);
            text-decoration: underline;
        }

        .footer {
            padding: 0 12px 26px;
            color: #8693a2;
            font-size: 11px;
            text-align: center;
        }

        a:focus-visible {
            outline: 3px solid rgba(15, 118, 110, 0.3);
            outline-offset: 3px;
        }

        @media (max-width: 650px) {
            .topbar-inner {
                width: calc(100% - 28px);
                min-height: 74px;
            }

            .brand-mark {
                width: 39px;
                height: 39px;
            }

            .brand h1 {
                max-width: 220px;
                font-size: 13px;
                line-height: 1.4;
            }

            .brand p {
                font-size: 11px;
            }

            .header-link {
                font-size: 12px;
                white-space: nowrap;
            }

            .page {
                width: calc(100% - 28px);
                margin: 23px auto;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .summary-card {
                padding: 15px 16px;
            }

            .table-heading {
                padding: 17px;
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
                <div class="brand-mark" aria-hidden="true">U</div>
                <div>
                    <h1>University Lecturer Attendance System</h1>
                    <p>Lecturer management</p>
                </div>
            </div>

            <a class="header-link" href="../admin/dashboard.php">
                ← Dashboard
            </a>

        </div>
    </header>


    <main class="page">

        <section class="page-heading">
            <div>
                <p class="eyebrow">Lecturer management</p>
                <h2>Registered lecturers</h2>
                <p>View lecturer details and face registration status.</p>
            </div>

            <a class="button" href="register.php">
                <span aria-hidden="true">＋</span>
                Register lecturer
            </a>
        </section>


        <section class="summary-card" aria-label="Lecturer count">
            <div>
                <div class="summary-label">Registered lecturers</div>
                <div class="summary-number">
                    <?php echo (int) $lecturerCount; ?>
                </div>
            </div>
            <div class="summary-icon" aria-hidden="true">♙</div>
        </section>


        <section class="table-card" aria-labelledby="lecturer-list-heading">
            <div class="table-heading">
                <h3 id="lecturer-list-heading">Lecturer directory</h3>
                <p>Contact details and face recognition registration.</p>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Lecturer ID</th>
                            <th>Full name</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Face data</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($lecturerCount > 0): ?>

                            <?php
                            $number = 1;

                            while ($row = mysqli_fetch_assoc($result)):
                            ?>

                                <tr>
                                    <td class="row-number">
                                        <?php echo $number++; ?>
                                    </td>

                                    <td class="lecturer-id">
                                        <?php
                                        echo htmlspecialchars(
                                            $row["Lecturer id"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td class="lecturer-name">
                                        <?php
                                        echo htmlspecialchars(
                                            $row["Full name"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row["Department"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row["Email"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row["Phone"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($row["Face_data"])): ?>
                                            <span class="face-badge">
                                                <span aria-hidden="true">✓</span>
                                                Registered
                                            </span>
                                        <?php else: ?>
                                            <span class="face-badge face-missing">
                                                Not registered
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-icon" aria-hidden="true">♙</div>
                                        <h3>No lecturers registered yet</h3>
                                        <p>Register a lecturer to see them listed here.</p>
                                        <a class="button" href="register.php">
                                            Register lecturer
                                        </a>
                                    </div>
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>


        <nav class="bottom-links" aria-label="Page navigation">
            <a class="text-link" href="register.php">
                ← Register another lecturer
            </a>

            <a class="text-link" href="../admin/dashboard.php">
                Back to dashboard →
            </a>
        </nav>

    </main>


    <footer class="footer">
        University Lecturer Attendance Management System
    </footer>

</body>

</html>