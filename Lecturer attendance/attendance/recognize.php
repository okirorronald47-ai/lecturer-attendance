<?php

session_start();

if (!isset($_SESSION["admin_id"])) {

    header("Location: ../admin/login.php");
    exit();

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Lecturer Attendance</title>

</head>

<body>

    <h1>Lecturer Attendance</h1>

    <p>
        Position the lecturer's face clearly in front of the camera.
    </p>

    <hr>


    <h2>Attendance Camera</h2>


    <video
        id="camera"
        width="600"
        height="450"
        autoplay>
    </video>

    <br><br>


    <button
        type="button"
        onclick="startCamera()">

        Start Camera

    </button>


    <button
        type="button"
        onclick="captureFace()">

        Scan Lecturer

    </button>


    <br><br>


    <h2>Scanned Face</h2>


    <canvas
        id="canvas"
        width="600"
        height="450">
    </canvas>


    <hr>


    <h2>Recognition Result</h2>


    <p id="result">

        No lecturer scanned yet.

    </p>


    <h2>Attendance Status</h2>


    <p id="attendance">

        Attendance has not been recorded.

    </p>


    <br>


    <a href="../admin/dashboard.php">
        Back to Dashboard
    </a>


    <script>

        let cameraStream;


        function startCamera() {

            navigator.mediaDevices.getUserMedia({

                video: true,

                audio: false

            })

            .then(function(stream) {

                cameraStream = stream;

                document.getElementById("camera").srcObject = stream;

            })

            .catch(function(error) {

                alert(
                    "Camera could not be accessed: "
                    + error.name
                    + ": "
                    + error.message
                );

            });

        }


        function captureFace() {

            let video =
                document.getElementById("camera");

            let canvas =
                document.getElementById("canvas");

            let context =
                canvas.getContext("2d");


            context.drawImage(

                video,

                0,

                0,

                canvas.width,

                canvas.height

            );


            document.getElementById("result").innerHTML =
                "Face captured. Recognition will be performed here.";


            document.getElementById("attendance").innerHTML =
                "Waiting for lecturer recognition...";

        }

    </script>

</body>

</html>