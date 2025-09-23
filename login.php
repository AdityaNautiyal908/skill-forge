<?php
session_start();
require_once "config/db_mysql.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $message = "Incorrect password!";
        }
    } else {
        $message = "Email not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillForge — Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    color: white;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%),
                linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a);
    overflow: hidden;
}
.stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.8), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.5), transparent 60%); opacity: .5; pointer-events: none; }
.orb { position: absolute; border-radius: 50%; filter: blur(20px); opacity: .5; animation: float 12s ease-in-out infinite; }
.o1{ width: 200px; height: 200px; background:#6e8efb; top:-60px; left:-60px; }
.o2{ width: 260px; height: 260px; background:#a777e3; bottom:-80px; right:10%; animation-delay:2s; }
@keyframes float { 0%,100%{ transform:translateY(0)} 50%{ transform:translateY(-14px)} }

.auth-card {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 440px;
    padding: 28px;
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 10px 40px rgba(0,0,0,0.35);
}
.brand { display:inline-block; padding:8px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.06); font-weight:600; margin-bottom:10px; }
.title { font-weight:800; margin:0 0 6px 0; }
.subtitle { color: rgba(255,255,255,0.8); margin-bottom: 18px; }
.form-label { color: rgba(255,255,255,0.9); }
.form-control { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); color: #fff; }
.form-control:focus { background: rgba(255,255,255,0.12); color: #fff; border-color: #6e8efb; box-shadow: 0 0 0 0.2rem rgba(110,142,251,0.25); }
.btn-primary-glow { background: linear-gradient(135deg, #6e8efb, #a777e3); border:none; width:100%; padding: 10px 16px; border-radius: 10px; box-shadow: 0 8px 30px rgba(110,142,251,0.35); }
.alt { color: rgba(255,255,255,0.8); }
.alt a { color: #cfd8ff; text-decoration: none; }
.alt a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="stars"></div>
<div class="orb o1"></div>
<div class="orb o2"></div>

<div class="auth-card mx-3">
    <span class="brand">SkillForge</span>
    <h2 class="title">Welcome back</h2>
    <p class="subtitle">Log in to continue your learning journey.</p>
    <?php if($message) echo "<div class='alert alert-danger'>$message</div>"; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary-glow">Login</button>
        <p class="mt-3 text-center alt">Don't have an account? <a href="register.php">Create one</a></p>
    </form>
</div>

</body>
</html>
