<?php
session_start();
// NOTE: Assuming config/db_mysql.php exists and establishes $conn
require_once "config/db_mysql.php";

$message = "";
$email = ""; 

// --- PHP: "Remember Me" Login Logic ---
if (isset($_COOKIE['remember_user_id']) && !isset($_SESSION['user_id'])) {
    $user_id = $_COOKIE['remember_user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Set role/admin flag
        $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';
        $_SESSION['is_admin'] = (isset($user['role']) && $user['role'] === 'admin');

        // Redirect to loading page after "remember me" login
        $_SESSION['show_preloader'] = true;
        header("Location: loading.php");
        exit;
    }
}

// --- PHP: Guest Login Logic ---
if (isset($_POST['guest_login'])) {
    $_SESSION['user_id'] = 'guest'; 
    $_SESSION['username'] = 'Guest';
    $_SESSION['role'] = 'guest'; 
    $_SESSION['is_admin'] = false;
    
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit;
}

// --- PHP: Form Submission Login Logic ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']); 
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']);

    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                
                // SUCCESSFUL LOGIN: SET SESSION
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                $role = isset($user['role']) ? $user['role'] : 'user';
                $_SESSION['role'] = $role;
                $_SESSION['is_admin'] = ($role === 'admin');

                if ($remember_me) {
                    $expiry = time() + (86400 * 30); 
                    setcookie('remember_user_id', $user['id'], $expiry, "/");
                }
                
                // NEW: Redirect to loading page
                $_SESSION['show_preloader'] = true;
                header("Location: loading.php");
                exit;
                
            } else {
                $message = "Incorrect password!";
            }
        } else {
            $message = "Email not found!";
        }
    }
}
$prompt_register = isset($_GET['prompt_register']) && $_GET['prompt_register'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillForge — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets\css\login.css">
</head>
<body>
    <div class="stars"></div>
    <canvas id="webLogin" class="web"></canvas>
    <div class="orb o1"></div>
    <div class="orb o2"></div>

    <div class="auth-card mx-3">
        <span class="brand">SkillForge</span>
        <h2 class="title" align="center">Welcome</h2>
        <p class="subtitle">Log in to continue your learning journey.</p>
        <form method="POST" action="" id="loginForm" novalidate>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" autocomplete="username" autofocus value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" autocomplete="current-password" required>
            </div>
            <div class="form-check text-start">
                <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe">
                <label class="form-check-label" for="rememberMe">
                    Remember me
                </label>
            </div>
            <button type="submit" class="btn btn-primary-glow">Login</button>
            <p class="mt-3 text-center alt">
                <a href="forgot_password.php">Forgot Password?</a>
            </p>
            <p class="mt-3 text-center alt">Don't have an account? <a href="register.php">Create one</a></p>
        </form>
        <form method="POST" action="" style="margin-top: 10px;">
            <button type="submit" name="guest_login" class="btn btn-guest">Continue as Guest</button>
        </form>
    </div>

    <script>
        const PHP_MESSAGE = '<?= $message ? htmlspecialchars($message, ENT_QUOTES) : '' ?>';
        const PROMPT_REGISTER = <?= $prompt_register ? 'true' : 'false' ?>;
    </script>
    
    <script src="assets\js\script.js"></script>
</body>
</html>