<?php

require_once "../config/database.php";

header("Content-Type: application/json");

date_default_timezone_set("Africa/Kampala");

$lecturer_id = trim($_POST["lecturer_id"] ?? "");
$action = trim($_POST["action"] ?? "");

if ($lecturer_id === "" || !in_array($action, ["check_in", "check_out"], true)) {
    echo json_encode([
        "success" => false,
        "message" => "Lecturer ID or attendance action is missing or invalid."
    ]);
    exit;
}

$attendance_date = date("Y-m-d");
$current_time = date("H:i:s");

/*
|--------------------------------------------------------------------------
| STEP 1: Find today's attendance record for this lecturer
|--------------------------------------------------------------------------
*/

$check_sql = "SELECT `ID`, `Attendance time`, `Departure time`
              FROM attendance
              WHERE `Lecturer id` = ?
              AND `Attendance date` = ?
              LIMIT 1";

$check_stmt = mysqli_prepare($conn, $check_sql);

if (!$check_stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not prepare attendance lookup."
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $check_stmt,
    "ss",
    $lecturer_id,
    $attendance_date
);

mysqli_stmt_execute($check_stmt);

mysqli_stmt_bind_result(
    $check_stmt,
    $attendance_id,
    $arrival_time,
    $departure_time
);

$record_exists = mysqli_stmt_fetch($check_stmt);

mysqli_stmt_close($check_stmt);

/*
|--------------------------------------------------------------------------
| STEP 2: CHECK IN
|--------------------------------------------------------------------------
*/

if ($action === "check_in") {

    if ($record_exists) {
        echo json_encode([
            "success" => false,
            "already_marked" => true,
            "message" => "Check-in has already been recorded for today.",
            "arrival_time" => $arrival_time,
            "departure_time" => $departure_time
        ]);
        exit;
    }

    $status = "Present";

    $insert_sql = "INSERT INTO attendance
                   (`Lecturer id`,
                    `Attendance date`,
                    `Attendance time`,
                    `Departure time`,
                    `Status`,
                    `Created at`)
                   VALUES (?, ?, ?, NULL, ?, NOW())";

    $insert_stmt = mysqli_prepare($conn, $insert_sql);

    if (!$insert_stmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Could not prepare check-in."
        ]);
        exit;
    }

    mysqli_stmt_bind_param(
        $insert_stmt,
        "ssss",
        $lecturer_id,
        $attendance_date,
        $current_time,
        $status
    );

    if (mysqli_stmt_execute($insert_stmt)) {

        echo json_encode([
            "success" => true,
            "action" => "check_in",
            "message" => "Check-in recorded successfully.",
            "lecturer_id" => $lecturer_id,
            "date" => $attendance_date,
            "arrival_time" => $current_time,
            "departure_time" => null,
            "status" => $status
        ]);

    } else {

        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Check-in failed. Please try again."
        ]);
    }

    mysqli_stmt_close($insert_stmt);
    exit;
}

/*
|--------------------------------------------------------------------------
| STEP 3: CHECK OUT
|--------------------------------------------------------------------------
*/

if (!$record_exists) {
    echo json_encode([
        "success" => false,
        "message" => "Check-in must be recorded before check-out."
    ]);
    exit;
}

if ($departure_time !== null) {
    echo json_encode([
        "success" => false,
        "already_checked_out" => true,
        "message" => "Check-out has already been recorded for today.",
        "arrival_time" => $arrival_time,
        "departure_time" => $departure_time
    ]);
    exit;
}

$update_sql = "UPDATE attendance
               SET `Departure time` = ?
               WHERE `ID` = ?
               AND `Departure time` IS NULL";

$update_stmt = mysqli_prepare($conn, $update_sql);

if (!$update_stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not prepare check-out."
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $update_stmt,
    "si",
    $current_time,
    $attendance_id
);

if (mysqli_stmt_execute($update_stmt) &&
    mysqli_stmt_affected_rows($update_stmt) === 1) {

    echo json_encode([
        "success" => true,
        "action" => "check_out",
        "message" => "Check-out recorded successfully.",
        "lecturer_id" => $lecturer_id,
        "date" => $attendance_date,
        "arrival_time" => $arrival_time,
        "departure_time" => $current_time
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Check-out could not be recorded. Please check today's attendance record."
    ]);
}

mysqli_stmt_close($update_stmt);

?>
