<?php
session_start();

// Check if the preloader flag is set (meaning a successful action just occurred)
if (!isset($_SESSION['show_preloader']) || $_SESSION['show_preloader'] !== true) {
    // If no flag, redirect immediately to prevent direct access
    header("Location: dashboard.php");
    exit;
}

// Clear the flag so it only runs once
unset($_SESSION['show_preloader']);

// Set a redirect delay (in milliseconds)
$redirect_delay_ms = 2500; // Display animation for 2.5 seconds
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillForge — Loading Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets\css\loading.css">
    
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    
</head>
<body>
    <div class="d-flex flex-column align-items-center">
        <dotlottie-wc 
            src="https://lottie.host/875b45ac-4512-42f0-a049-e893704b1984/pMXeAbEXw7.lottie" 
            style="width: 300px; height: 300px;" 
            autoplay loop
        ></dotlottie-wc>

        <h1>Loading your personalized dashboard...</h1>
        <p class="text-muted">Preparing your coding environment.</p>
    </div>
    
    <script>
        // Pass PHP variable to JavaScript
        const REDIRECT_DELAY_MS = <?= $redirect_delay_ms ?>;
    </script>
    
    <script src="assets\js\loading.js"></script>
</body>
</html>