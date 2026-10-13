<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Facial Recognition Test</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background: #111;
            color: white;
            padding-top: 30px;
        }

        video {
            width: 640px;
            height: 480px;
            border: 3px solid white;
            border-radius: 10px;
        }

        button {
            margin-top: 20px;
            padding: 12px 25px;
            font-size: 16px;
            cursor: pointer;
        }

        #status {
            margin-top: 20px;
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h1>Lecturer Facial Recognition System</h1>

    <p>Testing Facial Recognition Models</p>

    <video id="video" autoplay muted></video>

    <br>

    <button onclick="startCamera()">
        Start Camera
    </button>

    <div id="status">
        Loading models...
    </div>


    <script src="js/face-api.min.js"></script>


    <script>

        const video = document.getElementById("video");
        const status = document.getElementById("status");


        // =====================================
        // LOAD ALL THREE MODELS
        // =====================================

        async function loadModels() {

            try {

                status.innerText =
                    "Loading Tiny Face Detector...";

                await faceapi.nets.tinyFaceDetector.loadFromUri(
                    "models"
                );


                status.innerText =
                    "Loading Face Landmark 68...";

                await faceapi.nets.faceLandmark68Net.loadFromUri(
                    "models"
                );


                status.innerText =
                    "Loading Face Recognition...";

                await faceapi.nets.faceRecognitionNet.loadFromUri(
                    "models"
                );


                status.innerText =
                    "ALL MODELS LOADED ✓";

                console.log("All models loaded successfully.");

            }

            catch (error) {

                console.error(error);

                status.innerText =
                    "ERROR: A model failed to load.";

            }

        }


        // =====================================
        // START CAMERA
        // =====================================

        async function startCamera() {

            try {

                const stream =
                    await navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: false
                    });

                video.srcObject = stream;

                status.innerText =
                    "Camera started. Looking for face...";

            }

            catch (error) {

                console.error(error);

                status.innerText =
                    "ERROR: Camera could not start.";

            }

        }


        // =====================================
        // DETECT FACE + LANDMARKS + DESCRIPTOR
        // =====================================

        video.addEventListener("play", () => {

            setInterval(async () => {

                try {

                    const result =
                        await faceapi
                            .detectSingleFace(
                                video,
                                new faceapi.TinyFaceDetectorOptions()
                            )
                            .withFaceLandmarks()
                            .withFaceDescriptor();


                    if (result) {

                        console.log(
                            "Face detected!"
                        );


                        console.log(
                            "Landmarks:",
                            result.landmarks
                        );


                        console.log(
                            "Face descriptor:",
                            result.descriptor
                        );


                        status.innerText =
                            "FACE + LANDMARKS + DESCRIPTOR DETECTED ✓";

                    }

                    else {

                        status.innerText =
                            "Looking for face...";

                    }

                }

                catch (error) {

                    console.error(error);

                    status.innerText =
                        "Recognition error.";

                }

            }, 1000);

        });


        // =====================================
        // LOAD MODELS
        // =====================================

        loadModels();

    </script>

</body>
</html>