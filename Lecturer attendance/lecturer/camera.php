<!DOCTYPE html>
<html>

<head>

    <title>Lecturer Face Capture</title>

</head>

<body>

    <h1>Lecturer Face Capture</h1>
    <h2>NEW VERSION TEST 123</h2>

    <p>Use the camera to capture the lecturer's face.</p>

    <video id="camera" width="500" height="375" autoplay></video>

    <br><br>

    <button onclick="startCamera()">
        Start Camera
    </button>

    <button onclick="captureFace()">
        Capture Face
    </button>

    <br><br>

    <h3>Captured Face</h3>

    <canvas id="canvas" width="500" height="375"></canvas>
    
    <p id="result"></p>

    <br><br>

    <button onclick="retakeFace()">
        Retake
    </button>

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
                    "Camera could not be accessed: " +
                    error.name +
                    ": " +
                    error.message
                );

            });

        }
function captureFace() {

    let video = document.getElementById("camera");

    let canvas = document.getElementById("canvas");

    let context = canvas.getContext("2d");

    context.drawImage(
        video,
        0,
        0,
        canvas.width,
        canvas.height
    );

    let faceData = canvas.toDataURL("image/jpeg");

    alert("Face data size: " + faceData.length + " characters");
    alert("Face captured and converted to data!");

}


        function retakeFace() {

            let canvas = document.getElementById("canvas");

            let context = canvas.getContext("2d");

            context.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );

        }

    </script>

</body>

</html>