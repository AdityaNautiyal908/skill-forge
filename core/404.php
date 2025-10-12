<?php
// Since this is a standalone error page, we don't necessarily need the session or MongoDB, 
// but we include the necessary setup if the site structure requires it.
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && $_SESSION['user_id'] !== 'guest';
$username = $_SESSION['username'] ?? 'Guest';

// Ensure the HTTP status code is set to 404
http_response_code(404); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | Page Not Found - SkillForge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets\css\404.css">
    
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
</head>
<body>

    <div class="error-container">
        
        <dotlottie-wc 
            src="https://lottie.host/fbea2342-5ecf-405a-aa5f-a25fb0487664/hsYqfvelXc.lottie" 
            autoplay loop
        ></dotlottie-wc>

        <h1 class="error-text">PAGE NOT FOUND</h1>
        <p class="lead mb-4">
            It looks like we can't locate that page on SkillForge.
            Let's get you back on track!
        </p>
        
        <div class="d-grid gap-3 col-8 mx-auto">
            <a href="dashboard.php" class="btn btn-lg btn-primary">
                Return to Dashboard
            </a>
        </div>
    </div>

</body>
</html>