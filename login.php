<?php
session_start();
include("includes/db.php");

// Check for remember me cookie
if (!isset($_SESSION['student_id']) && !isset($_SESSION['admin_id'])) {
    if (isset($_COOKIE['meddemy_remember'])) {
        $cookie_data = json_decode($_COOKIE['meddemy_remember'], true);
        
        if ($cookie_data && isset($cookie_data['user_type'], $cookie_data['user_id'], $cookie_data['token'])) {
            if ($cookie_data['user_type'] === 'student') {
                $stmt = $conn->prepare("SELECT * FROM student WHERE student_id = ? AND remember_token = ?");
                $stmt->bind_param("is", $cookie_data['user_id'], $cookie_data['token']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['student_name'] = $user['name'];
                    header("Location: students/dashboard.php");
                    exit();
                }
            } elseif ($cookie_data['user_type'] === 'admin' && $cookie_data['user_id'] === 1) {
                $admin_token = hash('sha256', 'meddemy_admin_1_secret_key_2026');
                if ($cookie_data['token'] === $admin_token) {
                    $_SESSION['admin_id'] = 1;
                    $_SESSION['admin_name'] = "Sheraz";
                    header("Location: admin/dashboard.php");
                    exit();
                }
            }
        }
        setcookie('meddemy_remember', '', time() - 3600, '/', '', true, true);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $fixed_admin_email = "meddemy.sheraz12@gmail.com";
    $fixed_admin_pass  = "@123Admin+-";

    if ($email === $fixed_admin_email && $password === $fixed_admin_pass) { 
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = "Sheraz";
        
        if ($remember) {
            $admin_token = hash('sha256', 'meddemy_admin_1_secret_key_2026');
            $cookie_value = json_encode([
                'user_type' => 'admin',
                'user_id' => 1,
                'token' => $admin_token
            ]);
            setcookie('meddemy_remember', $cookie_value, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        header("Location: admin/dashboard.php");
        exit();
    }

    $sql = "SELECT * FROM student WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['student_id'] = $row['student_id'];
            $_SESSION['student_name'] = $row['name'];
            
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $update = $conn->prepare("UPDATE student SET remember_token = ?, token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE student_id = ?");
                $update->bind_param("si", $token, $row['student_id']);
                $update->execute();
                
                $cookie_value = json_encode([
                    'user_type' => 'student',
                    'user_id' => $row['student_id'],
                    'token' => $token
                ]);
                setcookie('meddemy_remember', $cookie_value, [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }
            
            header("Location: students/dashboard.php");
            exit();
        }
    }

    $error = "Invalid email or password";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MEDDEMY</title>
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
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: -200px; left: 50%;
            transform: translateX(-50%);
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(255,215,0,0.07) 0%, transparent 65%);
            pointer-events: none;
        }

        .auth-card {
            width: 100%;
            max-width: 380px;
            background: #141414;
            border: 1px solid rgba(255,215,0,0.18);
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
            position: relative;
            z-index: 1;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .auth-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 24px;
        }

        .auth-logo img { height: 45px; margin-bottom: 8px; }

        .auth-logo-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 900;
            color: #FFD700;
            letter-spacing: 1px;
        }

        .auth-heading { text-align: center; margin-bottom: 22px; }

        .auth-heading h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: #fff;
            margin-bottom: 5px;
        }

        .auth-heading p { font-size: 0.85rem; color: rgba(255,255,255,0.45); }

        .auth-heading p a {
            color: #FFD700;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-heading p a:hover { text-decoration: underline; }

        .auth-error {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(220,53,69,0.12);
            border: 1px solid rgba(220,53,69,0.35);
            color: #ff6b6b;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255,255,255,0.65);
            margin-bottom: 6px;
        }

        .input-wrap { position: relative; }

        .input-wrap .icon-left {
            position: absolute;
            left: 13px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.25);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            padding: 11px 12px 11px 38px;
            background: #1e1e1e;
            border: 1.5px solid #2a2a2a;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
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
            right: 12px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255,255,255,0.25);
            font-size: 0.82rem;
            transition: color 0.25s;
        }
        .toggle-pw:hover { color: rgba(255,255,255,0.55); }

        .form-extras {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .remember {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.78rem; color: rgba(255,255,255,0.45);
            cursor: pointer;
        }
        .remember input { accent-color: #FFD700; cursor: pointer; width: 14px; height: 14px; }

        .forgot {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
        }
        .forgot:hover { color: #FFD700; }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #FFD700, #ffed4e);
            border: none;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            font-weight: 700;
            color: #111;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(255,215,0,0.25);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,215,0,0.4);
        }

        .btn-submit i { transition: transform 0.25s; font-size: 0.9rem; }
        .btn-submit:hover i { transform: translateX(3px); }

        .back-home {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.3);
            text-decoration: none;
        }
        .back-home:hover { color: #FFD700; }

        /* Responsive */
        @media (max-width: 480px) {
            .auth-card { padding: 28px 22px; max-width: 100%; }
            .auth-heading h2 { font-size: 1.35rem; }
            .form-extras { flex-direction: column; align-items: flex-start; gap: 8px; }
        }

        @media (max-width: 380px) {
            .auth-card { padding: 24px 18px; }
            .auth-logo img { height: 40px; }
            .auth-logo-name { font-size: 1.2rem; }
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
        <h2>Welcome Back</h2>
        <p>New here? <a href="register.php">Create account</a></p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="auth-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">

        <div class="form-group">
            <label>Email</label>
            <div class="input-wrap">
                <input type="email" name="email" placeholder="your@email.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required autocomplete="email">
                <i class="fas fa-envelope icon-left"></i>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-wrap">
                <input type="password" id="pw" name="password"
                       placeholder="Enter password"
                       required autocomplete="current-password">
                <i class="fas fa-lock icon-left"></i>
                <i class="fas fa-eye toggle-pw" id="togglePw"></i>
            </div>
        </div>

        <div class="form-extras">
            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me</span>
            </label>
            <a href="forgot_password.php" class="forgot">Forgot?</a>
        </div>

        <button type="submit" class="btn-submit">
            Login <i class="fas fa-arrow-right"></i>
        </button>

    </form>

    <a href="index.php" class="back-home">← Back to Home</a>

</div>

<script>
    document.getElementById('togglePw').addEventListener('click', function () {
        const pw = document.getElementById('pw');
        pw.type = pw.type === 'password' ? 'text' : 'password';
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>
</body>
</html>