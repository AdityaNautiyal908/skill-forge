// mcqQuestions and totalQuestions are defined globally in the script tag in challenge.php

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
    timerDisplay.classList.remove('text-danger');
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
        questionText.classList.remove('text-danger');
    } else {
        // Visual feedback
        questionText.textContent = `Incorrect! ${currentPlayerDisplayName} missed the question. The answer was ${mcqQuestions[currentQuestionIndex].options[correctAnswer]}.`;
        questionText.classList.add('text-danger');
        questionText.classList.remove('text-success');
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
        questionText.classList.remove('text-success');
        
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
document.addEventListener('DOMContentLoaded', () => {
    startButton.addEventListener('click', startGame);

    // Initial setup on load (sets name/score displays and handles question load errors)
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
});