<?php
// Since this is a standalone error page, we don't necessarily need the session or MongoDB, 
// but we include the necessary setup if the site structure requires it.
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && $_SESSION['user_id'] !== 'guest';
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | Page Not Found - SkillForge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    
    <style>
        body {
            /* Matches the deep blue background gradient from your dashboard */
            background: linear-gradient(135deg, #171b30, #20254a 55%, #3c467b);
            color: white; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }
        .error-container {
            background: rgba(60, 70, 123, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px 50px; 
            max-width: 600px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }
        .error-code {
            font-size: 8rem;
            font-weight: 800;
            color: #6d7cff; /* Primary color highlight */
            text-shadow: 0 0 15px rgba(109, 124, 255, 0.5);
            margin-bottom: 0;
            line-height: 1;
        }
        .error-text {
            font-size: 1.8rem;
            margin-bottom: 20px; 
            color: #7aa2ff;
        }
        .btn-primary {
            /* Matches your primary button style */
            background: linear-gradient(135deg, #6d7cff, #7aa2ff);
            border: none;
            box-shadow: 0 4px 15px rgba(109,124,255,0.4);
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }
        .nav-links a {
            color: #ccc;
            margin: 0 10px;
            text-decoration: none;
            transition: color 0.2s;
        }
        .nav-links a:hover {
            color: #fff;
        }
        /* Style for the Lottie component */
        dotlottie-wc {
            display: block;
            margin: 15px auto 25px auto; 
            width: 250px; /* Kept the size consistent for a good fit */
            height: 250px;
        }
    </style>
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
            <a href="index.php" class="btn btn-lg btn-primary">
                Return to Dashboard
            </a>
        </div>
    </div>

</body>
</html>