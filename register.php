<?php
session_start();
require_once "config/db_mysql.php";
require_once "includes/mailer.php"; // You'll need a mailer class or function

// --- 1. CHECK FOR REGISTRATION SUCCESS (FLASH MESSAGE) ---
$show_success_alert = false;
if (isset($_SESSION['registration_success'])) {
    $show_success_alert = true;
    unset($_SESSION['registration_success']); // Unset it so it doesn't show again on refresh
}

$message = "";

// Check if the guest button was clicked
if (isset($_POST['guest_register'])) {
    $_SESSION['user_id'] = 'guest'; // Use the same guest identifier
    $_SESSION['username'] = 'Guest';
    $_SESSION['role'] = 'guest';
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirmPassword']); // Assuming you've added this to your form

    // Server-side password strength validation
    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasLower = preg_match('/[a-z]/', $password);
    $hasDigit = preg_match('/[0-9]/', $password);
    $hasSymbol = preg_match('/[^A-Za-z0-9]/', $password);

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "Please fill in all the details.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 8 || !$hasUpper || !$hasLower || !$hasDigit || !$hasSymbol) {
        $message = "Password must be at least 8 chars and include uppercase, lowercase, number, and symbol.";
    }

    if ($message === "") {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Check if email or username already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? OR username=?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Username or Email already exists!";
        } else {
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                // --- NEW WELCOME EMAIL LOGIC ---
                $welcome_subject = "Welcome to SkillForge, " . $username . "!";
                $welcome_body = "
                    <h2>Welcome to SkillForge!</h2>
                    <p>Hello **" . htmlspecialchars($username) . "**,</p>
                    <p>Thank you for signing up and starting your coding journey with us. Your account is now active.</p>
                    <p>You can start practicing immediately by visiting your dashboard:</p>
                    <p><a href='http://" . $_SERVER['HTTP_HOST'] . "/skill-forge/dashboard.php' style='padding: 10px 20px; background-color: #6d7cff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Dashboard</a></p>
                    <p>Happy Coding!</p>
                    <p>— The SkillForge Team</p>
                ";
                
                // Send the welcome email
                send_mail($email, $welcome_subject, $welcome_body);

                // --- 2. SET SESSION FOR SUCCESS ALERT AND REDIRECT ---
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['show_preloader'] = true; // For the loading page
                $_SESSION['registration_success'] = true; // Our new flash message trigger
                header("Location: " . $_SERVER['PHP_SELF']); // Redirect back to this same page
                exit;

            } else {
                $message = "Registration failed. Try again!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillForge — Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* (All your CSS styles remain unchanged here) */
         /* Main Layout Adjustments for Horizontal View */
        body { 
            margin:0; color:white; min-height:100vh; display:flex; align-items:center; justify-content:center;
            background: radial-gradient(1200px 600px at 10% 10%, rgba(76,91,155,0.35), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(60,70,123,0.35), transparent 60%), linear-gradient(135deg, #171b30, #20254a 55%, #3c467b);
            overflow:hidden;
            flex-direction: column; /* Start with vertical stacking for smaller screens */
        }

        .auth-card { 
            position:relative; z-index:1000; width:100%; max-width:800px; padding:24px; border-radius:16px; 
            background: linear-gradient(180deg, rgba(60,70,123,0.42), rgba(60,70,123,0.18)); 
            border:1px solid rgba(255,255,255,0.14); box-shadow:0 10px 40px rgba(0,0,0,0.45); 
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
        }

        .auth-card .intro-section {
            flex: 1;
            min-width: 250px;
            display: flex;
            flex-direction: column;
            align-items: center; /* Center content horizontally */
            text-align: center; /* Center text content */
        }
        
        .auth-card .form-section {
            flex: 1;
            min-width: 300px;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 768px) {
            .auth-card {
                flex-direction: column;
            }
            .intro-section {
                margin-bottom: 20px; /* Add some space below the intro section */
            }
        }
        
        /* New styles for the GIF */
        .register-gif {
            max-width: 80%; /* Adjust size as needed */
            height: auto;
            margin-top: 20px; /* Space from the text above */
            border-radius: 8px; /* Slightly rounded corners for aesthetics */
        }

        /* Other styles from your original code */
        .light { color:#2d3748 !important; background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%), linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0) !important; }
        .light .title, .light h1, .light h2, .light h3, .light h4, .light h5, .light h6 { color: #1a202c !important; }
        .light .subtitle, .light p, .light .desc, .light .card-text { color: #4a5568 !important; }
        .stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.55), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.45), transparent 60%); opacity: .45; pointer-events: none; }
        .web { position: fixed; inset:0; z-index:0; pointer-events:none; }
        .orb { position:absolute; border-radius:50%; filter: blur(20px); opacity:.45; animation: float 12s ease-in-out infinite; }
        .o1{ width: 200px; height: 200px; background:#6d7cff; top:-60px; left:-60px; }
        .o2{ width: 260px; height: 260px; background:#7aa2ff; bottom:-80px; right:10%; animation-delay:2s; }
        @keyframes float { 0%,100%{ transform:translateY(0)} 50%{ transform:translateY(-14px)} }
        
        .brand { display:inline-block; padding:8px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.14); background:rgba(60,70,123,0.35); font-weight:600; margin-bottom:10px; }
        .title { font-weight:800; margin:0 0 6px 0; }
        .subtitle { color: rgba(255,255,255,0.88); margin-bottom: 18px; }
        .form-label { color: rgba(255,255,255,0.9); }
        .form-control { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #fff; }
        .form-control:focus { background: rgba(255,255,255,0.12); color: #fff; border-color: #6d7cff; box-shadow: 0 0 0 0.2rem rgba(109,124,255,0.25); }
        .hint { font-size: 12px; color: rgba(255,255,255,0.75); }
        .strength { height: 8px; background: rgba(255,255,255,0.15); border-radius: 6px; overflow:hidden; }
        .strength-bar { height:100%; width:0%; background: linear-gradient(90deg, #ff4d4d, #ffc107, #28a745); transition: width .2s ease; }
        .btn-primary-glow { background: linear-gradient(135deg, #6d7cff, #7aa2ff); border:none; width:100%; padding: 10px 16px; border-radius: 10px; box-shadow: 0 8px 30px rgba(109,124,255,0.35); }
        .alt { color: rgba(255,255,255,0.88); }
        .alt a { color: #cfd8ff; text-decoration: none; }
        .alt a:hover { text-decoration: underline; }
        .password-input-wrapper { position: relative; display: flex; align-items: center; }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: rgba(255,255,255,0.85); cursor: pointer; padding: 4px; border-radius: 4px; transition: color 0.2s ease, background 0.2s ease; z-index: 10; }
        .password-toggle:hover { color: rgba(255,255,255,1); background: rgba(255,255,255,0.12); }
        .password-toggle.active .eye-svg { transform: scale(1.05); }
        .password-input-wrapper .form-control { padding-right: 45px; }

        .password-requirements { list-style: none; padding: 0; margin-top: 10px; }
        .password-requirements li { color: #ccc; margin-bottom: 5px; }
        .password-requirements .valid::before { content: '✓ '; color: #28a745; }
        .password-requirements .invalid::before { content: '✗ '; color: #dc3545; }
        
        .btn-guest { background: none; border: 1px solid rgba(255,255,255,0.14); color: rgba(255,255,255,0.88); padding: 10px 16px; width: 100%; border-radius: 10px; margin-top: 10px; }
        .btn-guest:hover { background: rgba(255,255,255,0.08); }
        .btn-generate-password { display: none; margin-top: 10px; }
    </style>
</head>
<body>
<div class="stars"></div>
<canvas id="webReg" class="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>

<div class="auth-card mx-3">
    <div class="intro-section">
        <span class="brand">SkillForge</span>
        <h2 class="title">Create your account</h2>
        <p class="subtitle">Join SkillForge and start building your coding superpowers.</p>
        <img src="https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExOWgxOHlnN3ZnamFidTIybTZmZnN3NmM4NXNxbTFubmNld3BicWtweCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/cKKilkePjBAh0tbmBU/giphy.gif" alt="Registration GIF" class="register-gif">
    </div>
    
    <div class="form-section">
        <form method="POST" action="" id="registerForm" novalidate>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                    <button type="button" class="password-toggle" id="togglePassword" title="Show/Hide Password">
                        <span class="eye-svg" aria-hidden="true"></span>
                    </button>
                </div>
                <ul class="password-requirements">
                    <li id="lengthReq">At least 8 characters</li>
                    <li id="uppercaseReq">At least 1 uppercase letter</li>
                    <li id="lowercaseReq">At least 1 lowercase letter</li>
                    <li id="numberReq">At least 1 number</li>
                    <li id="symbolReq">At least 1 symbol</li>
                </ul>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" required>
                    <button type="button" class="password-toggle" id="toggleConfirmPassword" title="Show/Hide Password">
                        <span class="eye-svg" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary-glow" id="submitBtn">Create account</button>
            <button type="button" class="btn btn-guest btn-generate-password" id="generatePasswordBtn">Generate Strong Password</button>
            <p class="mt-3 text-center alt">Already have an account? <a href="login.php">Login</a></p>
        </form>
        <form method="POST" action="">
            <button type="submit" name="guest_register" class="btn btn-guest">Continue as Guest</button>
        </form>
    </div>
</div>

<script>
// --- 3. ADDED PHP-triggered SweetAlert2 success message ---
<?php if ($show_success_alert): ?>
Swal.fire({
    icon: 'success',
    title: 'Registration Successful!',
    text: 'Welcome to SkillForge. Preparing your dashboard...',
    background: '#3c467b',
    color: '#fff',
    timer: 2500, // Show the alert for 2.5 seconds
    showConfirmButton: false, // Hide the "OK" button
    timerProgressBar: true,
}).then(() => {
    // This function runs after the alert closes
    window.location.href = 'loading.php';
});
<?php endif; ?>

// PHP-triggered SweetAlert2 error message
<?php if ($message): ?>
Swal.fire({
    icon: 'error',
    title: 'Registration Error',
    text: '<?= htmlspecialchars($message) ?>',
    background: '#3c467b',
    color: '#fff',
    confirmButtonColor: '#6d7cff'
});
<?php endif; ?>

// The rest of the JavaScript remains unchanged
// (Password strength and validation logic here...)
(function(){
    var form = document.getElementById('registerForm');
    var usernameField = document.getElementById('username');
    var emailField = document.getElementById('email');
    var passwordField = document.getElementById('password');
    var confirmPasswordField = document.getElementById('confirmPassword');
    var submitBtn = document.getElementById('submitBtn');
    var generatePasswordBtn = document.getElementById('generatePasswordBtn');

    // Get requirement list items
    const lengthReq = document.getElementById('lengthReq');
    const uppercaseReq = document.getElementById('uppercaseReq');
    const lowercaseReq = document.getElementById('lowercaseReq');
    const numberReq = document.getElementById('numberReq');
    const symbolReq = document.getElementById('symbolReq');

    function validatePassword() {
        const password = passwordField.value;
        const hasLength = password.length >= 8;
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        const hasDigit = /[0-9]/.test(password);
        const hasSymbol = /[^A-Za-z0-9]/.test(password);
        
        // Check if the password is weak
        const isPasswordWeak = !(hasLength && hasUpper && hasLower && hasDigit && hasSymbol);

        // Update classes based on validation
        lengthReq.className = hasLength ? 'valid' : 'invalid';
        uppercaseReq.className = hasUpper ? 'valid' : 'invalid';
        lowercaseReq.className = hasLower ? 'valid' : 'invalid';
        numberReq.className = hasDigit ? 'valid' : 'invalid';
        symbolReq.className = hasSymbol ? 'valid' : 'invalid';
        
        // Show or hide the generate button based on password strength
        if (password.length > 0 && isPasswordWeak) {
            generatePasswordBtn.style.display = 'block';
        } else {
            generatePasswordBtn.style.display = 'none';
        }
        
        // Check if all fields are filled and password is valid
        const isFormValid = usernameField.value.trim() !== '' &&
                              emailField.value.trim() !== '' &&
                              passwordField.value.trim() !== '' &&
                              confirmPasswordField.value.trim() !== '' &&
                              !isPasswordWeak &&
                              passwordField.value === confirmPasswordField.value;
        
        submitBtn.disabled = !isFormValid;
    }
    
    // Function to generate a strong password
    function generateStrongPassword() {
        const length = 12; // Password length
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
        let retVal = "";
        const charTypes = {
            'lower': 'abcdefghijklmnopqrstuvwxyz',
            'upper': 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'digit': '0123456789',
            'symbol': '!@#$%^&*()_+~`|}{[]:;?><,./-='
        };
        
        // Ensure at least one of each character type
        retVal += charTypes.lower[Math.floor(Math.random() * charTypes.lower.length)];
        retVal += charTypes.upper[Math.floor(Math.random() * charTypes.upper.length)];
        retVal += charTypes.digit[Math.floor(Math.random() * charTypes.digit.length)];
        retVal += charTypes.symbol[Math.floor(Math.random() * charTypes.symbol.length)];

        for (let i = retVal.length; i < length; i++) {
            retVal += charset.charAt(Math.floor(Math.random() * charset.length));
        }

        // Shuffle the string
        retVal = retVal.split('').sort(() => 0.5 - Math.random()).join('');
        
        return retVal;
    }

    // Event listener for the new button
    generatePasswordBtn.addEventListener('click', function() {
        const newPassword = generateStrongPassword();
        passwordField.value = newPassword;
        confirmPasswordField.value = newPassword;
        validatePassword(); // Re-run validation to update UI
    });

    // Add event listeners for real-time validation
    usernameField.addEventListener('input', validatePassword);
    emailField.addEventListener('input', validatePassword);
    passwordField.addEventListener('input', validatePassword);
    confirmPasswordField.addEventListener('input', validatePassword);
    
    // Initial validation check
    validatePassword();


    form && form.addEventListener('submit', function(e){
        var p = passwordField.value;
        var c = confirmPasswordField.value;
        
        // Client-side validation for SweetAlert2
        if (!p || !c || !document.querySelector('input[name="username"]').value || !document.querySelector('input[name="email"]').value) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please fill in all the details.',
                background: '#3c467b',
                color: '#fff',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        if (p !== c) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Passwords do not match.',
                background: '#3c467b',
                color: '#fff',
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        var ok = p.length >= 8 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p);
        if (!ok){
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Password Weak',
                text: 'Password is not strong enough.',
                background: '#3c467b',
                color: '#fff',
                confirmButtonColor: '#dc3545'
            });
        }
    });
})();

// Password toggle functionality (unchanged)
(function(){
    var EYE = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5C7 5 3.1 8.1 1.5 12c1.6 3.9 5.5 7 10.5 7s8.9-3.1 10.5-7C20.9 8.1 17 5 12 5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.6"/></svg>';
    var EYE_OFF = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M9.88 9.88A3.5 3.5 0 0012 15.5c1.93 0 3.5-1.57 3.5-3.5 0-.54-.12-1.05-.34-1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M4.2 7.51C6.02 6.03 8.79 5 12 5c5 0 8.9 3.1 10.5 7-.65 1.59-1.73 3.03-3.11 4.22M7.5 16.5C5.86 15.6 4.54 14.38 3.5 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    
    function togglePasswordVisibility(inputId, toggleId) {
        var input = document.getElementById(inputId);
        var toggle = document.getElementById(toggleId);
        var icon = toggle.querySelector('.eye-svg');
        
        if (!input || !toggle) return;
        icon.innerHTML = EYE;

        toggle.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = EYE_OFF;
                toggle.classList.add('active');
            } else {
                input.type = 'password';
                icon.innerHTML = EYE;
                toggle.classList.remove('active');
            }
        });
    }
    
    togglePasswordVisibility('password', 'togglePassword');
    togglePasswordVisibility('confirmPassword', 'toggleConfirmPassword');
    
    var password = document.getElementById('password');
    var confirmPassword = document.getElementById('confirmPassword');
    
    function checkPasswordMatch() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.style.borderColor = '#dc3545';
            confirmPassword.style.boxShadow = '0 0 0 0.2rem rgba(220,53,69,0.25)';
        } else {
            confirmPassword.style.borderColor = '';
            confirmPassword.style.boxShadow = '';
        }
    }
    
    password && password.addEventListener('input', checkPasswordMatch);
    confirmPassword && confirmPassword.addEventListener('input', checkPasswordMatch);
})();

// Other scripts (unchanged)
(function(){
    function apply(){ var theme=localStorage.getItem('sf_theme')||'dark'; var anim=localStorage.getItem('sf_anim')||'on'; document.body.classList.toggle('light', theme==='light'); document.body.classList.toggle('no-anim', anim==='off'); }
    apply();
    var box=document.createElement('div'); box.style.position='fixed'; box.style.right='14px'; box.style.bottom='14px'; box.style.zIndex='9998'; box.style.display='flex'; box.style.gap='8px';
    function mk(label){ var b=document.createElement('button'); b.textContent=label; b.style.border='1px solid rgba(255,255,255,0.4)'; b.style.background='rgba(0,0,0,0.35)'; b.style.color='#fff'; b.style.padding='8px 12px'; b.style.borderRadius='10px'; b.style.backdropFilter='blur(6px)'; return b; }
    var tBtn=mk((localStorage.getItem('sf_theme')||'dark')==='light'?'Dark Mode':'Light Mode');
    var aBtn=mk((localStorage.getItem('sf_anim')||'on')==='off'?'Enable Anim':'Disable Anim');
    tBtn.onclick=function(){ var cur=localStorage.getItem('sf_theme')||'dark'; var next=cur==='dark'?'light':'dark'; localStorage.setItem('sf_theme',next); tBtn.textContent=next==='light'?'Dark Mode':'Light Mode'; apply(); };
    aBtn.onclick=function(){ var cur=localStorage.getItem('sf_anim')||'on'; var next=cur==='on'?'off':'on'; localStorage.setItem('sf_anim',next); aBtn.textContent=next==='off'?'Enable Anim':'Disable Anim'; apply(); };
    document.body.appendChild(box); box.appendChild(tBtn); box.appendChild(aBtn);
})();
(function(){
    var canvas = document.getElementById('webReg'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
    function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
    var nodes=[], NUM=40, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
    function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); var isLight=document.body.classList.contains('light'); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); if(isLight){grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)');} else {grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)');} ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();
</script>

</body>
</html>