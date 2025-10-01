<?php
session_start();

// Ensure the user is logged in (or handle 'guest' as before, but a challenge usually requires login)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

$mcqData = [];
$numQuestions = 10; // Define how many questions to use in the battle (5 for P1, 5 for P2)
$totalQuestions = $numQuestions * 2; // We need 20 questions total for alternating turns

try {
    // 1. Get the MongoDB collection for MCQs
    $mcqColl = getCollection('coding_platform', 'mcq');
    
    // 2. Fetch a large batch of MCQs
    $query = new MongoDB\Driver\Query([], ['limit' => 100]); // Fetch a large set to shuffle
    $cursor = $mcqColl['manager']->executeQuery($mcqColl['db'] . ".mcq", $query);
    
    foreach ($cursor as $document) {
        $mcqData[] = [
            'question' => $document->question,
            'options' => $document->options, 
            'answer' => $document->answer,   
            'language' => $document->language ?? 'General',
        ];
    }
    
    // 3. Shuffle the entire set and select the required number of questions (TOTAL_QUESTIONS = 20)
    // This ensures randomization every time the page loads.
    shuffle($mcqData);
    $mcqData = array_slice($mcqData, 0, $totalQuestions);

} catch (Throwable $e) {
    error_log("MCQ fetch error: " . $e->getMessage());
    $mcqData = [];
}

// Convert PHP data to a JSON string for JavaScript
$jsonMcqData = json_encode($mcqData);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillForge — Two-Player Quiz Battle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    
    <style>
        /* Reusing your dark theme styles for consistency */
        body { 
            background: linear-gradient(135deg, #171b30, #20254a 55%, #3c467b);
            color: white; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px; /* Reduced top padding to make room for the link */
        }
        /* Style for the back link container */
        .back-link-container {
            width: 90%;
            max-width: 900px;
            margin-bottom: 30px;
            text-align: left;
        }
        .back-link {
            color: #7aa2ff; /* Light blue color from your primary button gradient */
            text-decoration: none;
            font-size: 1.1em;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #a8b0ff;
            text-decoration: underline;
        }

        .container { 
            background: rgba(60,70,123,0.25);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
            max-width: 900px;
        }
        .question-box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            min-height: 250px;
            
            /* --- Centering the Content using Flexbox --- */
            display: flex; 
            flex-direction: column;
            justify-content: center; /* Center vertically */
            align-items: center; /* Center horizontally for loading messages */
            text-align: center; /* Ensures text itself is centered */
        }
        .option-btn {
            width: 100%;
            margin-bottom: 10px;
            text-align: left;
            padding: 12px;
            border: 1px solid rgba(109,124,255,0.4);
            transition: background-color 0.2s;
            color: white;
            background-color: rgba(109,124,255,0.2);
        }
        .option-btn:hover {
            background-color: rgba(109,124,255,0.4);
            color: white;
        }
        .score-board {
            font-size: 1.5em;
            font-weight: bold;
        }
        .winner-screen {
            background: rgba(255, 255, 255, 0.1);
            padding: 50px;
            border-radius: 15px;
            text-align: center;
        }
        .winner-screen h1 {
            color: #5efc8d;
        }
        /* Style for the Lottie component */
        dotlottie-wc {
            display: block; /* Ensures centering works */
            margin: 20px auto;
        }
        
        /* New rule to override horizontal centering when options are displayed */
        .question-box:not(.initial-load) {
            align-items: stretch; /* Stretch content back to full width for questions/options */
            text-align: left; /* Text alignment back to default left */
        }

        /* Style for the name input fields to fit the dark theme */
        .name-input {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(109,124,255,0.4);
            color: white;
            transition: border-color 0.2s;
        }
        .name-input:focus {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: #6d7cff;
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(109, 124, 255, 0.25);
        }
    </style>
</head>
<body>
    <div class="back-link-container">
        <a href="dashboard.php" class="back-link">
            &larr; Back to Dashboard
        </a>
    </div>

    <div class="container">
        <h2 class="text-center mb-4">Two-Player Quiz Battle</h2>
        
        <div class="row mb-4 score-board">
            <div class="col text-start text-info" id="player1Score">Player 1: 0</div>
            <div class="col text-center text-warning" id="timerDisplay">Time: 30s</div>
            <div class="col text-end text-danger" id="player2Score">Player 2: 0</div>
        </div>

        <div id="nameInputArea" class="question-box initial-load">
            <h3 class="mb-3">Enter Player Names</h3>
            <div class="row w-100 mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="p1Name" class="form-label text-info">Player 1 Name:</label>
                    <input type="text" id="p1Name" class="form-control name-input" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Player 1') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="p2Name" class="form-label text-danger">Player 2 Name:</label>
                    <input type="text" id="p2Name" class="form-control name-input" value="Player 2" required>
                </div>
            </div>
            
            <div id="statusMessage" class="text-info mt-2">
                Ready for battle! <?= $totalQuestions ?> questions loaded (<?= $numQuestions ?> per player).
            </div>

            <div class="text-center mt-4">
                <button id="startButton" class="btn btn-lg btn-primary">Start Challenge</button>
            </div>
        </div>


        <div id="game-area" style="display:none;">
            <div class="question-box" id="questionBox">
                <p id="questionText" class="fs-4">Loading question...</p>
                <div id="optionsContainer" style="width: 100%;">
                    </div>
            </div>
        </div>
        
        <div id="results-area" class="winner-screen" style="display:none;">
            <dotlottie-wc 
                id="winnerAnimation"
                src="https://lottie.host/f815c872-4aa5-46a1-b11a-0e64b309345f/y2cRbp2Mo4.lottie" 
                style="width: 300px; height: 300px;" 
                autoplay loop
            ></dotlottie-wc>
            
            <h1 id="winnerName"></h1>
            <p id="finalScore" class="fs-5"></p>
            <button class="btn btn-primary mt-3" onclick="window.location.reload()">Play Again</button>
        </div>
    </div>

    <script>
        const mcqQuestions = <?php echo $jsonMcqData; ?>;
        const totalQuestions = mcqQuestions.length; // Will be 20

        // Game State Variables
        let player1Score = 0;
        let player2Score = 0;
        let currentPlayer = 1; 
        let currentQuestionIndex = 0; // The question index for the *current* player's turn
        let gameActive = false;
        let questionTimer = 30; 
        let timerInterval;

        // Player Name Variables
        let player1Name = 'Player 1';
        let player2Name = 'Player 2';

        // DOM Elements
        const startButton = document.getElementById('startButton');
        const nameInputArea = document.getElementById('nameInputArea');
        const p1NameInput = document.getElementById('p1Name');
        const p2NameInput = document.getElementById('p2Name');
        const statusMessage = document.getElementById('statusMessage');
        const questionBox = document.getElementById('questionBox');
        const questionText = document.getElementById('questionText');
        const optionsContainer = document.getElementById('optionsContainer');
        const player1ScoreDisplay = document.getElementById('player1Score');
        const player2ScoreDisplay = document.getElementById('player2Score');
        const timerDisplay = document.getElementById('timerDisplay');
        const gameArea = document.getElementById('game-area');
        const resultsArea = document.getElementById('results-area');
        const winnerName = document.getElementById('winnerName');
        const finalScore = document.getElementById('finalScore');
        const winnerAnimation = document.getElementById('winnerAnimation');

        // --- Core Game Functions ---

        function updateScoreDisplays() {
            player1ScoreDisplay.textContent = `${player1Name}: ${player1Score}`;
            player2ScoreDisplay.textContent = `${player2Name}: ${player2Score}`;
            
            // Highlight the current player
            player1ScoreDisplay.classList.toggle('text-success', currentPlayer === 1);
            player1ScoreDisplay.classList.toggle('text-info', currentPlayer !== 1);
            player2ScoreDisplay.classList.toggle('text-success', currentPlayer === 2);
            player2ScoreDisplay.classList.toggle('text-danger', currentPlayer !== 2);
        }

        function displayQuestion() {
            // Remove the centering class once the game starts
            questionBox.classList.remove('initial-load');
            
            // Check if we have run out of questions
            if (currentQuestionIndex >= totalQuestions) {
                endGame();
                return;
            }

            const currentPlayerDisplayName = currentPlayer === 1 ? player1Name : player2Name;
            
            // The question is determined by the constantly increasing index
            const q = mcqQuestions[currentQuestionIndex]; 
            
            questionText.className = 'fs-4'; 
            questionText.textContent = `[${currentPlayerDisplayName}'s Turn: Question ${Math.floor(currentQuestionIndex / 2) + 1} of ${totalQuestions/2}] ${q.question}`;
            optionsContainer.innerHTML = '';
            
            q.options.forEach((option, index) => {
                const btn = document.createElement('button');
                btn.className = 'option-btn btn';
                btn.textContent = option;
                btn.setAttribute('data-index', index);
                btn.onclick = () => handleAnswer(index, q.answer);
                optionsContainer.appendChild(btn);
            });
            
            // Reset and start the timer for the current question
            questionTimer = 30; // 30 seconds per question
            timerDisplay.textContent = `Time: ${questionTimer}s`;
            clearInterval(timerInterval);
            timerInterval = setInterval(handleTimer, 1000);
        }

        function handleAnswer(selectedIndex, correctAnswer) {
            if (!gameActive) return;
            
            clearInterval(timerInterval);
            const isCorrect = (selectedIndex == correctAnswer);
            
            // Disable all buttons immediately
            optionsContainer.querySelectorAll('.option-btn').forEach(btn => btn.disabled = true);

            const currentPlayerDisplayName = currentPlayer === 1 ? player1Name : player2Name;

            if (isCorrect) {
                const points = 10 + Math.floor(questionTimer / 3); // Bonus for time remaining
                if (currentPlayer === 1) {
                    player1Score += points;
                } else {
                    player2Score += points;
                }
                // Visual feedback
                questionText.textContent = `Correct! ${currentPlayerDisplayName} scored +${points} points.`;
                questionText.classList.add('text-success');
            } else {
                // Visual feedback
                questionText.textContent = `Incorrect! ${currentPlayerDisplayName} missed the question. The answer was ${mcqQuestions[currentQuestionIndex].options[correctAnswer]}.`;
                questionText.classList.add('text-danger');
            }
            
            updateScoreDisplays();
            
            // Pause briefly then move to next turn/question
            setTimeout(nextTurn, 2000);
        }
        
        function handleTimer() {
            questionTimer--;
            timerDisplay.textContent = `Time: ${questionTimer}s`;
            
            if (questionTimer <= 10) {
                timerDisplay.classList.add('text-danger');
            } else {
                 timerDisplay.classList.remove('text-danger');
            }

            if (questionTimer <= 0) {
                clearInterval(timerInterval);
                
                const currentPlayerDisplayName = currentPlayer === 1 ? player1Name : player2Name;

                // Penalize for timeout
                questionText.textContent = `Time's up! ${currentPlayerDisplayName} missed the question.`;
                questionText.classList.add('text-danger');
                
                // Pause briefly then move to next turn/question
                setTimeout(nextTurn, 2000);
            }
        }

        function nextTurn() {
            // Advance the global question index first
            currentQuestionIndex++;
            
            // Switch player for the next question
            currentPlayer = currentPlayer === 1 ? 2 : 1;
            
            // Start the next round
            displayQuestion();
        }

        function startGame() {
            // 1. Validate Names
            player1Name = p1NameInput.value.trim();
            player2Name = p2NameInput.value.trim();

            if (!player1Name || !player2Name) {
                alert("Please enter names for both players to start the challenge.");
                return;
            }

            if (mcqQuestions.length < totalQuestions) {
                alert(`Error: Could only load ${mcqQuestions.length} questions. Need ${totalQuestions}. Please check MongoDB.`);
                return;
            }
            
            // 2. Hide name input, show game area
            nameInputArea.style.display = 'none';
            gameArea.style.display = 'block';
            
            // 3. Start Game
            gameActive = true;
            // Ensure we start with P1 and Q1 (index 0)
            currentPlayer = 1; 
            currentQuestionIndex = 0; 
            
            displayQuestion(); 
            updateScoreDisplays(); // Display initial names/scores
        }

        function endGame() {
            gameActive = false;
            clearInterval(timerInterval);
            gameArea.style.display = 'none';
            resultsArea.style.display = 'block';

            let winnerDisplayName, finalScoreText;

            if (player1Score > player2Score) {
                winnerDisplayName = player1Name;
                finalScoreText = `Final Score: ${player1Name} (${player1Score} points) vs. ${player2Name} (${player2Score} points)`;
            } else if (player2Score > player1Score) {
                winnerDisplayName = player2Name;
                finalScoreText = `Final Score: ${player1Name} (${player1Score} points) vs. ${player2Name} (${player2Score} points)`;
            } else {
                winnerDisplayName = "It's a Tie!";
                finalScoreText = `Final Score: Both players scored ${player1Score} points!`;
            }
            
            winnerName.innerHTML = winnerDisplayName === "It's a Tie!" ? winnerDisplayName : `🏆 ${winnerDisplayName} is the Winner! 🏆`;
            finalScore.textContent = finalScoreText;

            // Hide animation if it's a tie
            winnerAnimation.style.display = (winnerDisplayName === "It's a Tie!") ? 'none' : 'block';
        }

        // --- Event Listener ---
        startButton.addEventListener('click', startGame);

        // Initial setup on load
        if (mcqQuestions.length < totalQuestions) {
             statusMessage.classList.remove('text-info');
             statusMessage.classList.add('text-danger');
             statusMessage.textContent = `Error: Could only load ${mcqQuestions.length} questions. Need ${totalQuestions} for a full game.`;
             startButton.disabled = true;
        } else {
             // Set the initial name displays
             player1ScoreDisplay.textContent = `${p1NameInput.value || 'Player 1'}: 0`;
             player2ScoreDisplay.textContent = `${p2NameInput.value || 'Player 2'}: 0`;
        }

    </script>
</body>
</html>