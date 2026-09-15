<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function ensureCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCustomer(): int
{
    if (empty($_SESSION['customer_id'])) {
        header('Location: login.php');
        exit;
    }
    return (int) $_SESSION['customer_id'];
}

function requireAdmin(): int
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }
    return (int) $_SESSION['admin_id'];
}
