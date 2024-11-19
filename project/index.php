<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gate_quiz";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch questions
$sql = "SELECT id, question, option_a, option_b, option_c, option_d, correct_option FROM questions";
$result = $conn->query($sql);

$questions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GATE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #0073e6;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .container {
            display: flex;
            height: 90vh;
        }
        .question-section {
            flex: 3;
            padding: 20px;
            background: white;
            border-right: 1px solid #ccc;
        }
        .palette-section {
            flex: 1;
            background: #f4f4f4;
            padding: 20px;
        }
        .question {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .options {
            margin-bottom: 20px;
        }
        .options input {
            margin-right: 10px;
        }
        .palette {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .palette button {
            width: 40px;
            height: 40px;
            background: #fff;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        .palette button.active {
            background-color: #0073e6;
            color: white;
        }
        .palette button.skipped {
            background-color: #f39c12;
            color: white;
        }
        .timer {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>GATE</h1>
    </div>
    <div class="container">
        <!-- Question Section -->
        <div class="question-section">
            <div class="timer" id="timer">Time Left: 1:00</div>
            <div class="question" id="question-text"></div>
            <div class="options" id="options"></div>
            <button onclick="submitAnswer()">Next</button>
            <button onclick="skipQuestion()">Skip</button>
        </div>

        <!-- Palette Section -->
        <div class="palette-section">
            <div class="palette" id="palette"></div>
        </div>
    </div>

    <script>
        const questions = <?php echo json_encode($questions); ?>;
        let currentQuestion = 0;
        let score = 0;
        const skippedQuestions = new Set();

        function startTimer() {
    let timer = 60; // 1 minute in seconds
    const timerElement = document.getElementById("timer");

    const interval = setInterval(() => {
        if (timer > 0) {
            timer--;
            const hours = Math.floor(timer / 3600);
            const minutes = Math.floor((timer % 3600) / 60);
            const seconds = timer % 60;
            timerElement.textContent = `Time Left: ${hours}:${minutes}:${seconds}`;
        } else {
            clearInterval(interval); // Stop the timer
            showTimeUpNotification(); // Show a custom notification
        }
    }, 1000);
}

function showTimeUpNotification() {
    // Create a modal or notification
    const notification = document.createElement("div");
    notification.id = "time-up-notification";
    notification.style.position = "fixed";
    notification.style.top = "50%";
    notification.style.left = "50%";
    notification.style.transform = "translate(-50%, -50%)";
    notification.style.backgroundColor = "#ff4d4d";
    notification.style.color = "#fff";
    notification.style.padding = "20px";
    notification.style.borderRadius = "10px";
    notification.style.boxShadow = "0 4px 8px rgba(0, 0, 0, 0.2)";
    notification.style.zIndex = "1000";
    notification.innerHTML = `
        <h2>Time's Up!</h2>
        <p>Your session has ended. Click below to view the result.</p>
        <button onclick="dismissNotification()">OK</button>
    `;

    // Add the notification to the body
    document.body.appendChild(notification);
}

function dismissNotification() {
    const notification = document.getElementById("time-up-notification");
    if (notification) {
        notification.remove(); // Remove the notification
    }
    showResult(); // Call the function to show the result
}

        function loadQuestion(index) {
            if (index >= questions.length) {
                showResult();
                return;
            }

            currentQuestion = index;

            const questionData = questions[index];
            document.getElementById("question-text").textContent = questionData.question;

            const optionsDiv = document.getElementById("options");
            optionsDiv.innerHTML = `
                <label><input type="radio" name="option" value="A"> ${questionData.option_a}</label><br>
                <label><input type="radio" name="option" value="B"> ${questionData.option_b}</label><br>
                <label><input type="radio" name="option" value="C"> ${questionData.option_c}</label><br>
                <label><input type="radio" name="option" value="D"> ${questionData.option_d}</label>
            `;

            highlightPalette(index);
        }

        function submitAnswer() {
            const selectedOption = document.querySelector('input[name="option"]:checked');
            if (selectedOption) {
                const answer = selectedOption.value;
                const correctAnswer = questions[currentQuestion].correct_option;

                if (answer === correctAnswer) {
                    score += 1; // Correct answer
                } else {
                    score -= 1 / 3; // Wrong answer
                }

                skippedQuestions.delete(currentQuestion); // Remove from skipped if answered
            }

            loadQuestion(currentQuestion + 1);
        }

        function skipQuestion() {
            skippedQuestions.add(currentQuestion); // Mark current question as skipped
            markSkipped(currentQuestion); // Highlight in the palette
            loadQuestion(currentQuestion + 1);
        }

        function highlightPalette(index) {
            const paletteButtons = document.querySelectorAll('.palette button');
            paletteButtons.forEach((button, i) => {
                if (i === index) {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            });
        }

        function markSkipped(index) {
            const paletteButtons = document.querySelectorAll('.palette button');
            paletteButtons[index].classList.add('skipped');
        }

        function renderPalette() {
            const paletteDiv = document.getElementById("palette");
            questions.forEach((_, index) => {
                const button = document.createElement("button");
                button.textContent = index + 1;
                button.onclick = () => loadQuestion(index);
                paletteDiv.appendChild(button);
            });
        }

        function showResult() {
            document.querySelector('.container').innerHTML = `
                <h2>Your Score: ${score.toFixed(2)}</h2>
            `;
        }

        // Initialize
        renderPalette();
        loadQuestion(0);
        startTimer();
    </script>
</body>
</html>
