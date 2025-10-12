<?php
session_start();

// Ensure the user is logged in (or handle 'guest' as before, but a challenge usually requires login)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db_mongo.php";

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
        // Ensure options and answer are extracted correctly
        $mcqData[] = [
            'question' => $document->question,
            'options' => $document->options, 
            'answer' => $document->answer, 
            'language' => $document->language ?? 'General',
        ];
    }
    
    // 3. Shuffle the entire set and select the required number of questions
    shuffle($mcqData);
    $mcqData = array_slice($mcqData, 0, $totalQuestions);

} catch (Throwable $e) {
    error_log("MCQ fetch error: " . $e->getMessage());
    $mcqData = [];
}

// Convert PHP data to a JSON string for JavaScript
$jsonMcqData = json_encode($mcqData);

// PHP variables for initial JS setup
$phpUsername = $_SESSION['username'] ?? 'Player 1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillForge — Two-Player Quiz Battle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets\css\challenge.css">
    
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    
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
                    <input type="text" id="p1Name" class="form-control name-input" value="<?= htmlspecialchars($phpUsername) ?>" required>
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
        // Injected PHP variables for JavaScript access
        const mcqQuestions = <?php echo $jsonMcqData; ?>;
        const totalQuestions = mcqQuestions.length;
    </script>

    <script src="assets\js\challenge.js"></script>
</body>
</html>