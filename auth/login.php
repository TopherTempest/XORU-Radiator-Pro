<?php

session_start();

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/validation.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['admin_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid request token. Please try again.';
    } else {

        $validation = validateAdminLogin($_POST);

        $username = $validation['data']['username'];
        $password = $validation['data']['password'];

        $error = implode(' ', $validation['errors']);
    }

    if ($error === '') {

        $stmt = $pdo->prepare(
            'SELECT id, username, password
             FROM admins
             WHERE username = ?
             LIMIT 1'
        );

        $stmt->execute([$username]);

        $admin = $stmt->fetch();

        if (
            !$admin ||
            !password_verify($password, $admin['password'])
        ) {

            $error = 'Invalid admin username or password.';

        } else {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: ../admin/dashboard.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Admin Login - XORU Radiator Pro</title>

<link rel="stylesheet" href="../style.css">

</head>

<body class="admin-page">

<div class="admin-login-page">

    <!-- LEFT SIDE -->
    <section class="admin-login-brand">

        <div class="admin-login-main-logo">
            <img
                src="../assets/xoru-blue.png"
                alt="XORU Radiator Pro Logo"
            >
        </div>

        <p>
            Administrator portal for managing customer bookings,
            schedules, services, and repair requests.
        </p>

    </section>


    <!-- RIGHT SIDE -->
    <section class="admin-login-side">

        <div class="admin-login-card">

            <!-- SMALL LOGO -->
            <div class="lock admin-login-small-logo">
                <img
                    src="../assets/xoru-blue.png"
                    alt="XORU Logo"
                >
            </div>

            <h2>Admin Login</h2>

            <p>
                Sign in to access the XORU Radiator Pro dashboard.
            </p>

            <?php if ($error): ?>

                <div class="admin-login-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>


            <form method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <label for="username">
                    Username
                </label>

                <input
                    id="username"
                    name="username"
                    placeholder="Enter admin username"
                    autocomplete="username"
                    required
                >


                <label for="password">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Enter password"
                    autocomplete="current-password"
                    required
                >


                <button
                    class="login-submit"
                    type="submit"
                >
                    Login to Dashboard
                </button>

            </form>


            <a
                class="admin-login-back"
                href="../index.php"
            >
                ← Back to XORU website
            </a>

        </div>

    </section>

</div>


<style>

/* =========================================
   ADMIN LOGIN LOGOS
   ========================================= */

.admin-login-main-logo {
    width: 270px;
    margin: 0 auto 40px;
}

.admin-login-main-logo img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: contain;
}


/* Small logo above Admin Login */

.admin-login-small-logo {
    width: 65px;
    height: 65px;
    margin: 0 auto 20px;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;
}

.admin-login-small-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

</style>

</body>
</html>