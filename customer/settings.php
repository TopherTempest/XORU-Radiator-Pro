<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

$customerId = requireCustomer();
$customerStmt = $pdo->prepare('SELECT id, name, email, provider FROM customers WHERE id = ? LIMIT 1');
$customerStmt->execute([$customerId]);
$customer = $customerStmt->fetch();
if (!$customer) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Customer Settings - XORU</title>
<link rel="stylesheet" href="../style.css">
<script>
(function () {
  const theme = localStorage.getItem('xoruCustomerTheme') || 'dark';
  document.documentElement.classList.add(theme === 'light' ? 'customer-light' : 'customer-dark');
})();
</script>
</head>
<body class="customer-page">
<main class="wrap" style="padding:40px 0">
<section class="modal-box customer-settings-page" style="width:min(900px,100%);margin:auto">
  <h2>Customer Settings</h2>
  <p>Manage your account appearance and preferences.</p>

  <section class="customer-settings-card">
    <h3>Appearance</h3>
    <p>Choose how your customer account looks.</p>
    <div class="customer-theme-options">
      <button type="button" class="customer-theme-option" id="customerLightButton" onclick="setCustomerTheme('light')">
        <strong>☀ Light Mode</strong>
        <span>Use a bright white interface.</span>
      </button>
      <button type="button" class="customer-theme-option" id="customerDarkButton" onclick="setCustomerTheme('dark')">
        <strong>🌙 Dark Mode</strong>
        <span>Use the current dark interface.</span>
      </button>
    </div>
    <div class="customer-theme-status">Current theme: <strong id="customerCurrentTheme">Dark Mode</strong></div>
  </section>

  <section class="customer-settings-card">
    <h3>My Account</h3>
    <div class="customer-account-row"><span>Name</span><strong><?= e($customer['name']) ?></strong></div>
    <div class="customer-account-row"><span>Email</span><strong><?= e($customer['email']) ?></strong></div>
    <div class="customer-account-row"><span>Login Method</span><strong><?= e(ucfirst($customer['provider'])) ?></strong></div>
  </section>

  <div class="customer-settings-actions">
    <a class="btn outline" href="dashboard.php">← Back to My Account</a>
    <a class="btn outline" href="logout.php">Logout</a>
  </div>
</section>
</main>
<script>
function setCustomerTheme(theme) {
  document.documentElement.classList.remove('customer-light', 'customer-dark');
  document.documentElement.classList.add(theme === 'light' ? 'customer-light' : 'customer-dark');
  localStorage.setItem('xoruCustomerTheme', theme);
  updateCustomerThemeButtons();
}
function updateCustomerThemeButtons() {
  const theme = localStorage.getItem('xoruCustomerTheme') || 'dark';
  document.getElementById('customerLightButton')?.classList.toggle('selected', theme === 'light');
  document.getElementById('customerDarkButton')?.classList.toggle('selected', theme === 'dark');
  const current = document.getElementById('customerCurrentTheme');
  if (current) current.textContent = theme === 'light' ? 'Light Mode' : 'Dark Mode';
}
document.addEventListener('DOMContentLoaded', updateCustomerThemeButtons);
</script>
</body>
</html>
