<?php
session_start();

// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CodeLearn - Learn & Practice Programming</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    color: white;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}
.card {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: none;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
}
.btn-custom {
    background-color: #ff6f61;
    border: none;
    color: white;
}
.btn-custom:hover {
    background-color: #ff4b3a;
}
</style>
</head>
<body>
<div class="card shadow">
    <h1 class="mb-3">Welcome to CodeLearn</h1>
    <p class="mb-4">Learn and practice programming languages like HTML, CSS, JavaScript, and more by solving interactive problems!</p>
    <a href="login.php" class="btn btn-custom btn-lg me-2">Login</a>
    <a href="register.php" class="btn btn-custom btn-lg">Register</a>
</div>
</body>
</html>
