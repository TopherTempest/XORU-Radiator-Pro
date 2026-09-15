<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

$customerId = requireCustomer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}

$type = $_POST['type'] ?? 'Inconvenience';
if (!in_array($type, ['Inconvenience', 'Review'], true)) {
    $$type = 'Inconvenience';
}

$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$ratingInput = trim((string)($_POST['rating'] ?? ''));
$rating = $ratingInput === '' ? null : (int)$ratingInput;

if ($subject === '' || $message === '') {
    header('Location: dashboard.php');
    exit;
}

if ($type === 'Review') {
    if ($rating === null || $rating < 1 || $rating > 5) {
        header('Location: dashboard.php');
        exit;
    }
} else {
    $rating = null;
}

$stmt = $pdo->prepare('INSERT INTO reports (customer_id, type, rating, subject, message) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$customerId, $type, $rating, $subject, $message]);

header('Location: dashboard.php?reported=1');
exit;
