<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/validation.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request token. Please try again.';
    } else {
        $validation = validateCustomerRegistration($_POST);
        $name = $validation['data']['name'];
        $email = $validation['data']['email'];
        $password = $validation['data']['password'];
        $error = implode(' ', $validation['errors']);

        if ($error === '') {
        $check = $pdo->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO customers (name, email, password, provider) VALUES (?, ?, ?, "email")');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['customer_id'] = (int)$pdo->lastInsertId();
            $_SESSION['customer_name'] = $name;
            header('Location: dashboard.php'); exit;
        }
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Create Customer Account - XORU</title><link rel="stylesheet" href="../style.css"></head>
<body><main class="wrap" style="min-height:100vh;display:grid;place-items:center"><section class="modal-box" style="margin:auto"><h2>Create Account</h2><p>Create your XORU customer account.</p><?php if($error): ?><p style="color:#ff8f8f"><?=htmlspecialchars($error)?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8')?>"><input name="name" placeholder="Full Name" required><input type="email" name="email" placeholder="Email Address" required><input type="password" name="password" placeholder="Password (8+ characters)" required><input type="password" name="confirm_password" placeholder="Confirm Password" required><button class="btn primary" type="submit">Create Account</button></form><p>Already have an account? <a href="login.php" style="color:#5da9ff">Login</a></p><a class="btn outline" href="../index.php">Back to XORU</a></section></main></body></html>
