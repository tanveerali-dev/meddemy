<?php
include("includes/db.php");

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match. Please try again.";
    } else {
        $check = $conn->query("SELECT student_id FROM student WHERE email='$email'");
        if ($check->num_rows > 0) {
            $error = "This email is already registered. Please login instead.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO student (name, email, password) VALUES ('$name', '$email', '$hashed')";
            if ($conn->query($sql) === TRUE) {
                $success = "Account created successfully! Redirecting to login...";
                header("refresh:2;url=login.php");
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — MEDDEMY</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'DM Sans', sans-serif;
            background: #0a0a0a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -200px; left: 50%;
            transform: translateX(-50%);
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(255,215,0,0.07) 0%, transparent 65%);
            pointer-events: none;
        }

        .auth-card {
            width: 100%;
            max-width: 460px;
            background: #141414;
            border: 1px solid rgba(255,215,0,0.18);
            border-radius: 24px;
            padding: 44px 40px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.6);
            position: relative;
            z-index: 1;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .auth-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 28px;
        }

        .auth-logo img { height: 52px; margin-bottom: 10px; }

        .auth-logo-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: #FFD700;
            letter-spacing: 1px;
        }

        .auth-heading { text-align: center; margin-bottom: 26px; }

        .auth-heading h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 6px;
        }

        .auth-heading p { font-size: 0.88rem; color: rgba(255,255,255,0.45); }

        .auth-heading p a {
            color: #FFD700;
            font-weight: 600;
            text-decoration: none;
        }
        .auth-heading p a:hover { text-decoration: underline; }

        /* Alerts */
        .auth-alert {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 0.86rem;
            font-weight: 500;
            padding: 11px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .auth-alert.error {
            background: rgba(220,53,69,0.12);
            border: 1px solid rgba(220,53,69,0.35);
            color: #ff6b6b;
        }
        .auth-alert.success {
            background: rgba(46,204,113,0.1);
            border: 1px solid rgba(46,204,113,0.3);
            color: #4ade80;
        }

        /* Form */
        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: rgba(255,255,255,0.65);
            margin-bottom: 8px;
        }

        .input-wrap { position: relative; }

        .input-wrap .icon-left {
            position: absolute;
            left: 15px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.25);
            font-size: 0.88rem;
            pointer-events: none;
            transition: color 0.25s;
        }

        .input-wrap input {
            width: 100%;
            padding: 13px 14px 13px 42px;
            background: #1e1e1e;
            border: 1.5px solid #2a2a2a;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.93rem;
            color: #fff;
            outline: none;
            transition: all 0.25s;
        }

        .input-wrap input::placeholder { color: rgba(255,255,255,0.2); }

        .input-wrap input:focus {
            border-color: #FFD700;
            background: #1a1900;
            box-shadow: 0 0 0 3px rgba(255,215,0,0.1);
        }

        .toggle-pw {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255,255,255,0.25);
            font-size: 0.85rem;
            transition: color 0.25s;
        }
        .toggle-pw:hover { color: rgba(255,255,255,0.55); }

        /* Password strength */
        .pw-strength { margin-top: 7px; display: none; }

        .pw-bar {
            height: 3px;
            border-radius: 3px;
            background: #2a2a2a;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .pw-fill {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .pw-label {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.35);
        }

        /* Terms */
        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 22px;
        }
        .terms-row input {
            accent-color: #FFD700;
            cursor: pointer;
            margin-top: 3px;
            flex-shrink: 0;
        }
        .terms-row label {
            font-size: 0.81rem;
            color: rgba(255,255,255,0.45);
            line-height: 1.5;
            cursor: pointer;
        }
        .terms-row a {
            color: #FFD700;
            text-decoration: none;
            font-weight: 600;
        }

        /* Button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #FFD700, #ffed4e);
            border: none;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.97rem;
            font-weight: 700;
            color: #111;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            box-shadow: 0 4px 20px rgba(255,215,0,0.25);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(255,215,0,0.45);
        }

        .btn-submit i { transition: transform 0.25s; }
        .btn-submit:hover i { transform: translateX(4px); }

        .auth-trust {
            display: flex;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.25);
        }
        .trust-item i { color: #4ade80; font-size: 0.72rem; }

        .back-home {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.25);
            text-decoration: none;
        }
        .back-home:hover { color: #FFD700; }

        @media (max-width: 480px) {
            .auth-card { padding: 32px 20px; }
        }
    </style>
</head>
<body>

<div class="auth-card">

    <div class="auth-logo">
        <img src="assets/images/logo44.png" alt="MEDDEMY">
        <span class="auth-logo-name">MEDDEMY</span>
    </div>

    <div class="auth-heading">
        <h2>Create Account</h2>
        <p>Already registered? <a href="login.php">Sign in here</a></p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="auth-alert error">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    <div class="auth-alert success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">

        <div class="form-group">
            <label>Full Name</label>
            <div class="input-wrap">
                <input type="text" name="name" placeholder="Muhammad Ali"
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                       required autocomplete="name">
                <i class="fas fa-user icon-left"></i>
            </div>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <div class="input-wrap">
                <input type="email" name="email" placeholder="yourname@email.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required autocomplete="email">
                <i class="fas fa-envelope icon-left"></i>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-wrap">
                <input type="password" id="pw1" name="password"
                       placeholder="Min 6 characters"
                       required autocomplete="new-password">
                <i class="fas fa-lock icon-left"></i>
                <i class="fas fa-eye toggle-pw" id="togglePw1"></i>
            </div>
            <div class="pw-strength" id="pwStrength">
                <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
                <div class="pw-label" id="pwLabel">Enter password</div>
            </div>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <div class="input-wrap">
                <input type="password" id="pw2" name="confirm_password"
                       placeholder="Repeat password"
                       required autocomplete="new-password">
                <i class="fas fa-lock icon-left"></i>
                <i class="fas fa-eye toggle-pw" id="togglePw2"></i>
            </div>
        </div>

        <div class="terms-row">
            <input type="checkbox" id="terms" name="terms" required>
            <label for="terms">
                I agree to MEDDEMY's <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
            </label>
        </div>

        <button type="submit" class="btn-submit">
            Create My Account <i class="fas fa-arrow-right"></i>
        </button>

    </form>

    <div class="auth-trust">
        <div class="trust-item"><i class="fas fa-shield-alt"></i> Secure</div>
        <div class="trust-item"><i class="fas fa-lock"></i> SSL Encrypted</div>
        <div class="trust-item"><i class="fas fa-user-shield"></i> No Spam</div>
    </div>


</div>

<script>
    // Toggle password
    function makeToggle(btnId, inputId) {
        document.getElementById(btnId).addEventListener('click', function () {
            const el = document.getElementById(inputId);
            el.type = el.type === 'password' ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    makeToggle('togglePw1', 'pw1');
    makeToggle('togglePw2', 'pw2');

    // Strength meter
    const pw1 = document.getElementById('pw1');
    const strengthBox = document.getElementById('pwStrength');
    const fill = document.getElementById('pwFill');
    const label = document.getElementById('pwLabel');
    const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
    const labels = ['Weak','Fair','Good','Strong'];

    pw1.addEventListener('input', function () {
        const v = this.value;
        strengthBox.style.display = v.length ? 'block' : 'none';
        let s = 0;
        if (v.length >= 6) s++;
        if (/[A-Z]/.test(v)) s++;
        if (/[0-9]/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v)) s++;
        const i = Math.max(0, s - 1);
        fill.style.width = (s * 25) + '%';
        fill.style.background = colors[i];
        label.textContent = labels[i];
        label.style.color = colors[i];
    });
</script>
</body>
</html>