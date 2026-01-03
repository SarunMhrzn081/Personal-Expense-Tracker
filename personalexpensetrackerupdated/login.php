<?php
require_once 'auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($email, $password);
    if ($result['success']) {
        header("Location: index.php");
        exit();
    } else {
        $message = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Expense Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container {
            background: #1e293b;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border: 1px solid #334155;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { font-size: 2rem; color: #f8fafc; margin-bottom: 8px; }
        .logo p { color: #94a3b8; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #cbd5e1; font-weight: 500; }
        .form-control {
            width: 100%; padding: 12px 16px; background: #0f172a;
            border: 2px solid #334155; border-radius: 8px; color: #e2e8f0;
            font-size: 16px; transition: border-color 0.3s ease;
        }
        .form-control:focus { outline: none; border-color: #3b82f6; }
        .btn {
            width: 100%; padding: 14px; background: #3b82f6; color: white;
            border: none; border-radius: 8px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease; margin-top: 10px;
        }
        .btn:hover { background: #2563eb; transform: translateY(-2px); }
        .auth-links { text-align: center; margin-top: 20px; }
        .auth-links a { color: #3b82f6; text-decoration: none; }
        .auth-links a:hover { text-decoration: underline; }
        .message { 
            padding: 12px; border-radius: 8px; margin-bottom: 20px;
            background: #ef4444; color: white; text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <h1><i class="fas fa-wallet"></i>  Personal Expense Tracker</h1>
            <p>Sign in to your account</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Sign up here</a></p>
        </div>
    </div>
</body>
</html>