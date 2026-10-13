<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../admin/login.php");
    exit();
}

require_once "../config/database.php";

$sql = "SELECT * FROM attendance ORDER BY attendance_date DESC, attendance_time DESC";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Attendance Records</title>

</head>

<body>

    <h1>Attendance Records</h1>

    <p>
        Welcome, <?php echo $_SESSION["admin_name"]; ?>
    </p>

    <hr>

    <table border="1" cellpadding="10">

        <tr>
            <th>ID</th>
            <th>Lecturer ID</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
        </tr>

        <?php

        if (mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {

        ?>

                <tr>

                    <td><?php echo $row["id"]; ?></td>

                    <td><?php echo $row["lecturer_id"]; ?></td>

                    <td><?php echo $row["attendance_date"]; ?></td>

                    <td><?php echo $row["attendance_time"]; ?></td>

                    <td><?php echo $row["status"]; ?></td>

                </tr>

        <?php

            }

        } else {

            echo "<tr>";
            echo "<td colspan='5'>No attendance records found.</td>";
            echo "</tr>";

        }

        ?>

    </table>

    <br>

    <a href="../admin/dashboard.php">
        Back to Dashboard
    </a>

</body>

</html>