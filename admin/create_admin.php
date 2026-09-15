<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

// Only logged-in administrators can access this page.
$adminId = requireAdmin();

$csrf = ensureCsrfToken();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = (string) ($_POST['csrf_token'] ?? '');

    if (!verifyCsrfToken($token)) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        // Validate username
        if ($username === '') {
            $error = 'Please enter a username.';
        } elseif (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters long.';
        } elseif (strlen($username) > 50) {
            $error = 'Username must not exceed 50 characters.';
        }

        // Validate password
        elseif ($password === '') {
            $error = 'Please enter a password.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        }

        if ($error === '') {

            // Check whether username already exists.
            $stmt = $pdo->prepare(
                'SELECT id FROM admins WHERE username = ? LIMIT 1'
            );
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $error = 'That admin username already exists.';
            } else {

                // Securely hash the password before saving it.
                $hash = password_hash($password, PASSWORD_DEFAULT);

                if ($hash === false) {
                    $error = 'Could not securely create the password.';
                } else {

                    $stmt = $pdo->prepare(
                        'INSERT INTO admins (username, password) VALUES (?, ?)'
                    );

                    try {
                        $stmt->execute([$username, $hash]);

                        $message = 'Admin account created successfully for "' . e($username) . '".';

                        // Clear form values after successful creation.
                        $username = '';
                        $password = '';
                        $confirmPassword = '';

                    } catch (PDOException $e) {
                        $error = 'Unable to create the admin account. Please try again.';
                    }
                }
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

    <title>Create Admin | XORU Radiator Pro</title>

    <link rel="stylesheet" href="../style.css">

    <style>
        .create-admin-page {
            min-height: 100vh;
            padding: 50px 20px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            background: var(--bg, #0e0f14);
        }

        .create-admin-card {
            width: min(520px, 100%);
            background: #ffffff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
        }

        .create-admin-logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .create-admin-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .create-admin-card h1 {
            margin: 0;
            text-align: center;
            color: #202127;
            font-size: 26px;
        }

        .create-admin-subtitle {
            margin: 8px 0 28px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .create-admin-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .create-admin-form label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #202127;
            font-size: 14px;
        }

        .create-admin-form input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #d4d5d8;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        .create-admin-form input:focus {
            border-color: #1265bd;
            box-shadow: 0 0 0 3px rgba(18, 101, 189, 0.12);
        }

        .create-admin-button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 13px 18px;
            background: #1265bd;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .create-admin-button:hover {
            background: #0b4f96;
        }

        .admin-message {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #e8f7ed;
            color: #176b35;
            font-size: 14px;
        }

        .admin-error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #fdecec;
            color: #a32121;
            font-size: 14px;
        }

        .create-admin-back {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #1265bd;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .create-admin-back:hover {
            text-decoration: underline;
        }

        .password-note {
            margin-top: 6px;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>

<body>

<div class="create-admin-page">

    <div class="create-admin-card">

        <div class="create-admin-logo">
            <img
                src="../assets/xoru-blue.png"
                alt="XORU Radiator Pro Logo"
            >
        </div>

        <h1>Create New Admin</h1>

        <p class="create-admin-subtitle">
            Only logged-in administrators can create another administrator.
        </p>

        <?php if ($message !== ''): ?>
            <div class="admin-message">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="admin-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form
            class="create-admin-form"
            method="post"
            action=""
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrf) ?>"
            >

            <div>
                <label for="username">Admin Username</label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= e($username ?? '') ?>"
                    minlength="3"
                    maxlength="50"
                    autocomplete="username"
                    required
                >
            </div>

            <div>
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <div class="password-note">
                    Password must be at least 8 characters long.
                </div>
            </div>

            <div>
                <label for="confirm_password">Confirm Password</label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button
                type="submit"
                class="create-admin-button"
            >
                Create Admin
            </button>

        </form>

        <a
            class="create-admin-back"
            href="dashboard.php"
        >
            ← Back to Dashboard
        </a>

    </div>

</div>

</body>
</html>