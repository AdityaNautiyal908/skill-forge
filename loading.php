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
    
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    
    <style>
        /* Minimalist dark theme for the loading screen */
        body { 
            background: linear-gradient(135deg, #171b30, #20254a 55%, #3c467b);
            color: white; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        h1 {
            font-size: 1.5rem;
            margin-top: 20px;
            color: #7aa2ff;
        }
        /* Style for the Lottie component */
        dotlottie-wc {
            display: block;
            margin: 0 auto;
        }
    </style>
    
    <script>
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, <?= $redirect_delay_ms ?>);
    </script>
</head>
<body>
    <div class="d-flex flex-column align-items-center">
        <dotlottie-wc 
            src="https://lottie.host/1668d9ca-22e5-457b-9221-31e634e220e3/bnte5yZc73.lottie" 
            style="width: 300px; height: 300px;" 
            autoplay loop
        ></dotlottie-wc>

        <h1>Loading your personalized dashboard...</h1>
        <p class="text-muted">Preparing your coding environment.</p>
    </div>
</body>
</html>