<?php
session_start();

// Redirect logged-in users to dashboard unless explicitly viewing public landing
if (isset($_SESSION['user_id']) && !isset($_GET['public'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Skill Forge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets\css\style.css">
</head>
<body>
    <div class="stars"></div>
    <canvas id="web"></canvas>
    <div class="orb o1"></div>
    <div class="orb o2"></div>
    <div class="orb o3"></div>

    <div id="splash" class="splash" style="display:none;">
        <div class="brand">SkillForge</div>
        
        <div style="position:absolute; bottom: 16%; display:flex; gap:8px;">
            <span style="width:8px;height:8px;border-radius:50%;background:#a777e3;opacity:.6;animation: dots 1.2s infinite"></span>
            <span style="width:8px;height:8px;border-radius:50%;background:#6e8efb;opacity:.6;animation: dots 1.2s infinite .2s"></span>
            <span style="width:8px;height:8px;border-radius:50%;background:#36d1dc;opacity:.6;animation: dots 1.2s infinite .4s"></span>
        </div>
    </div>

    <section class="hero container">
        <a href="index.php" class="logo">SkillForge</a>
        <h1 class="title">Level up your coding skills with interactive challenges</h1>
        <p class="subtitle">Practice HTML, CSS, JavaScript and more with real problems, instant feedback, and a delightful editor. Learn by doing and build confidence one challenge at a time.</p>
        <div class="cta-wrap">
            <a href="login.php" class="btn btn-primary-glow btn-animated">Start Solving</a>
            <a href="register.php" class="btn btn-ghost">Create Account</a>
        </div>

        <div class="features mt-4">
            <div class="feature f1">
                <h5>Live Code Editor</h5>
                <p>Write, run, and iterate with syntax highlighting and instant feedback.</p>
            </div>
            <div class="feature f2">
                <h5>Curated Tracks</h5>
                <p>Follow language tracks and progress through bite-sized challenges.</p>
            </div>
            <div class="feature f3">
                <h5>Progress Saving</h5>
                <p>Pick up where you left off. Your submissions are stored securely.</p>
            </div>
            <div class="feature f4">
                <h5>Beautiful UI</h5>
                <p>Clean, modern interface with subtle motion and pleasing colors.</p>
            </div>
        </div>
    </section>

    <script src="assets\js\script.js"></script>
</body>
</html>