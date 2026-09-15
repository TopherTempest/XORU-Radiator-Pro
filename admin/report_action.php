<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}

$reportId = (int)($_POST['report_id'] ?? 0);
$status = $_POST['status'] ?? 'New';
$reply = trim((string)($_POST['admin_reply'] ?? ''));

if (!in_array($status, ['New', 'Read', 'Resolved'], true)) {
    $status = 'New';
}

$stmt = $pdo->prepare('UPDATE reports SET status = ?, admin_reply = ? WHERE id = ?');
$stmt->execute([$status, $reply !== '' ? $reply : null, $reportId]);

$_SESSION['report_message'] = 'Report updated successfully.';
header('Location: reports.php');
exit;
