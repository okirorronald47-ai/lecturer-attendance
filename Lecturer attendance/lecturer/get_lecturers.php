<?php

require_once "../config/database.php";

header("Content-Type: application/json");

$sql = "SELECT
            `id`,
            `Lecturer id`,
            `Full name`,
            `Department`,
            `Email`,
            `Phone`,
            `Face_data`
        FROM lecturers";

$result = mysqli_query($conn, $sql);

$lecturers = [];

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $lecturers[] = $row;

    }

    echo json_encode($lecturers);

} else {

    echo json_encode([
        "error" => mysqli_error($conn)
    ]);

}

?>