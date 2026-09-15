<?php

session_start();

require_once __DIR__ . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['subscribe_error'] = 'Please enter a valid email address.';
    header('Location: index.php#subscribe');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO subscribers (email) VALUES (?)');

try {
    $stmt->execute([$email]);
    $_SESSION['subscribe_message'] = 'Thanks! Your email has been added for maintenance tips.';
} catch (PDOException $e) {
    if ((int) $e->errorInfo[1] === 1062) {
        $_SESSION['subscribe_message'] = 'That email is already subscribed.';
    } else {
        $_SESSION['subscribe_error'] = 'We could not save your subscription right now.';
    }
}

header('Location: index.php#subscribe');
exit;
