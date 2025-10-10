// --- SweetAlert2 logic based on PHP flash messages ---
function handleServerAlerts() {
    // PHP-triggered SweetAlert2 success message
    if (SHOW_SUCCESS_ALERT) {
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
    }

    // PHP-triggered SweetAlert2 error message
    if (PHP_ERROR_MESSAGE) {
        Swal.fire({
            icon: 'error',
            title: 'Registration Error',
            text: PHP_ERROR_MESSAGE,
            background: '#3c467b',
            color: '#fff',
            confirmButtonColor: '#6d7cff'
        });
    }
}
handleServerAlerts();


// --- Real-time validation and password generation logic ---
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


    // Client-side form submission handler
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


// --- Password toggle functionality ---
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

// --- Theme and Animation Toggles ---
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

// --- Canvas Web Animation ---
(function(){
    var canvas = document.getElementById('webReg'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
    function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
    var nodes=[], NUM=40, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
    function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); var isLight=document.body.classList.contains('light'); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); if(isLight){grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)');} else {grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)');} ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();
    