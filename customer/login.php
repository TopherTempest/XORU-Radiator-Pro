<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/validation.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['customer_id'])) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request token. Please try again.';
    } else {
        $validation = validateCustomerLogin($_POST);
        $email = $validation['data']['email'];
        $password = $validation['data']['password'];
        $error = implode(' ', $validation['errors']);
    }
    if ($error === '') {
    $stmt = $pdo->prepare('SELECT id, name, email, password FROM customers WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $customer = $stmt->fetch();
    if (!$customer || !$customer['password'] || !password_verify($password, $customer['password'])) {
        $error = 'Invalid email or password.';
    } else {
        session_regenerate_id(true);
        $_SESSION['customer_id'] = (int)$customer['id'];
        $_SESSION['customer_name'] = $customer['name'];
        header('Location: dashboard.php'); exit;
    }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Customer Login - XORU</title><link rel="stylesheet" href="../style.css"></head>
<body><main class="wrap" style="min-height:100vh;display:grid;place-items:center"><section class="modal-box" style="margin:auto"><h2>Customer Login</h2><p>Login to book a repair and view your requests.</p><?php if($error): ?><p style="color:#ff8f8f"><?=htmlspecialchars($error)?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8')?>"><input type="email" name="email" placeholder="Email Address" required><input type="password" name="password" placeholder="Password" required><button class="btn primary" type="submit">Login</button></form><div style="display:grid;gap:10px;margin-top:14px"><a class="btn outline" href="google_login.php">Continue with Google</a><a class="btn outline" href="facebook_login.php">Continue with Facebook</a></div><p>Don't have an account? <a href="register.php" style="color:#5da9ff">Create Account</a></p><a class="btn outline" href="../index.php">Back to XORU</a></section></main></body></html>
