<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

$adminId = requireAdmin();

$adminStmt = $pdo->prepare(
    'SELECT id, username FROM admins WHERE id = ? LIMIT 1'
);

$adminStmt->execute([$adminId]);

$admin = $adminStmt->fetch();

if (!$admin) {
    $_SESSION = [];
    session_destroy();

    header('Location: ../auth/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>XORU Admin - Settings</title>

    <link rel="stylesheet" href="../style.css">

    <script>
        (function () {
            if (localStorage.getItem('xoruAdminTheme') === 'dark') {
                document.documentElement.classList.add('admin-dark');
            }
        })();
    </script>
</head>

<body class="admin-page">

<div class="admin-shell">

    <aside class="admin-sidebar">

        <div class="admin-logo">
            <img
                src="../assets/xoru-blue.png"
                alt="XORU Radiator Pro Logo"
            >
        </div>

        <div class="admin-profile">
            <div class="admin-avatar">♙</div>

            <div>
                <strong><?= e($admin['username']) ?></strong>
                <span>Administrator</span>
            </div>
        </div>

        <nav class="admin-nav">

            <a href="dashboard.php">
                <img src="../assets/dashboard.png" class="nav-icon" alt="">
                Dashboard
            </a>

            <a href="dashboard.php#calendar">
                <img src="../assets/calendar.png" class="nav-icon" alt="">
                Calendar
            </a>

            <a href="dashboard.php#bookings">
                <img src="../assets/book.png" class="nav-icon" alt="">
                Bookings
            </a>

            <a href="customers.php">
                <img src="../assets/rook.png" class="nav-icon" alt="">
                Customers
            </a>

            <a href="dashboard.php#services">
                <img src="../assets/add-friend.png" class="nav-icon" alt="">
                Services
            </a>

            <a href="reports.php">
                <img src="../assets/bar-chart.png" class="nav-icon" alt="">
                Reports
            </a>

            <a class="active" href="settings.php">
                <img src="../assets/settings.png" class="nav-icon" alt="">
                Settings
            </a>

        </nav>

        <div class="admin-sidebar-bottom">
            <a href="../auth/logout.php">
                <span>⇥</span>
                Logout
            </a>
        </div>

    </aside>

    <main class="admin-main">

        <header class="admin-topbar">

            <div class="admin-topbar-title">
                <span class="admin-menu-icon">☰</span>
                Settings
            </div>

            <div class="admin-account">

                <div class="admin-account-x">
                    <img
                        src="../assets/xoru-blue.png"
                        alt="XORU Logo"
                    >
                </div>

                <div>
                    <strong>Xoru Radiator Pro</strong>
                    <small>Administrator</small>
                </div>

                <span>⌄</span>

            </div>

        </header>

        <div class="admin-content">

            <div class="admin-settings-page">

                <div class="admin-settings-header">
                    <h1>Settings</h1>
                    <p>Manage your administrator interface preferences.</p>
                </div>

                <section class="admin-settings-card">

                    <div class="admin-settings-card-header">
                        <h2>Appearance</h2>
                        <p>Choose how the XORU Admin Dashboard looks.</p>
                    </div>

                    <div class="admin-theme-options">

                        <button
                            type="button"
                            class="admin-theme-option"
                            id="lightThemeButton"
                            onclick="setAdminTheme('light')"
                        >

                            <div class="admin-theme-preview light-preview">

                                <div class="preview-top"></div>

                                <div class="preview-content">

                                    <div class="preview-sidebar"></div>

                                    <div class="preview-lines">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>

                                </div>

                            </div>

                            <div class="admin-theme-info">
                                <strong>☀ Light Mode</strong>
                                <span>Use the light admin interface.</span>
                            </div>

                        </button>

                        <button
                            type="button"
                            class="admin-theme-option"
                            id="darkThemeButton"
                            onclick="setAdminTheme('dark')"
                        >

                            <div class="admin-theme-preview dark-preview">

                                <div class="preview-top"></div>

                                <div class="preview-content">

                                    <div class="preview-sidebar"></div>

                                    <div class="preview-lines">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>

                                </div>

                            </div>

                            <div class="admin-theme-info">
                                <strong>🌙 Dark Mode</strong>
                                <span>Use the dark admin interface.</span>
                            </div>

                        </button>

                    </div>

                    <div class="admin-theme-status">
                        Current theme:
                        <strong id="currentTheme">Light Mode</strong>
                    </div>

                </section>

                <section class="admin-settings-card">

                    <div class="admin-settings-card-header">
                        <h2>Administrator Account</h2>
                        <p>
                            Information about the currently logged-in administrator.
                        </p>
                    </div>

                    <div class="admin-settings-account">

                        <div class="settings-account-row">
                            <span>Username</span>
                            <strong><?= e($admin['username']) ?></strong>
                        </div>

                        <div class="settings-account-row">
                            <span>Account Type</span>
                            <strong>Administrator</strong>
                        </div>

                    </div>

                </section>

                <div class="admin-footer-actions">

                    <a
                        class="admin-btn outline"
                        href="dashboard.php"
                    >
                        ← Back to Dashboard
                    </a>

                </div>

            </div>

        </div>

    </main>

</div>

<script>

function setAdminTheme(theme) {

    document.documentElement.classList.remove('admin-dark');

    if (theme === 'dark') {
        document.documentElement.classList.add('admin-dark');
    }

    localStorage.setItem('xoruAdminTheme', theme);

    updateThemeButtons();
}

function updateThemeButtons() {

    const theme =
        localStorage.getItem('xoruAdminTheme') || 'light';

    document
        .getElementById('lightThemeButton')
        ?.classList
        .toggle('selected', theme === 'light');

    document
        .getElementById('darkThemeButton')
        ?.classList
        .toggle('selected', theme === 'dark');

    const current =
        document.getElementById('currentTheme');

    if (current) {
        current.textContent =
            theme === 'dark'
                ? 'Dark Mode'
                : 'Light Mode';
    }
}

document.addEventListener(
    'DOMContentLoaded',
    updateThemeButtons
);

</script>

</body>
</html>