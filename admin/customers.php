<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

$adminId = requireAdmin();
$adminStmt = $pdo->prepare('SELECT id, username FROM admins WHERE id = ? LIMIT 1');
$adminStmt->execute([$adminId]);
$admin = $adminStmt->fetch();
if (!$admin) {
    $_SESSION = [];
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$customers = $pdo->query('SELECT c.id, c.name, c.email, c.provider, c.created_at, COUNT(b.id) AS booking_count FROM customers c LEFT JOIN bookings b ON b.customer_id = c.id GROUP BY c.id, c.name, c.email, c.provider, c.created_at ORDER BY c.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>XORU Admin - Customers</title>
<link rel="stylesheet" href="../style.css">
<script>(function(){if(localStorage.getItem('xoruAdminTheme')==='dark')document.documentElement.classList.add('admin-dark');})();</script>
</head>
<body class="admin-page">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-logo"><div class="admin-logo-x">X</div><div class="admin-logo-name">XORU</div><div class="admin-logo-sub">RADIATOR PRO</div></div>
    <div class="admin-profile"><div class="admin-avatar">♙</div><div><strong><?= e($admin['username']) ?></strong><span>Administrator</span></div></div>
    <nav class="admin-nav">
      <a href="dashboard.php"><img src="../assets/dashboard.png" class="nav-icon" alt="">Dashboard</a>
      <a href="dashboard.php#calendar"><img src="../assets/calendar.png" class="nav-icon" alt="">Calendar</a>
      <a href="dashboard.php#bookings"><img src="../assets/book.png" class="nav-icon" alt="">Bookings</a>
      <a class="active" href="customers.php"> <img src="../assets/rook.png" class="nav-icon" alt="">Customers</a>
      <a href="dashboard.php#services"><img src="../assets/add-friend.png" class="nav-icon" alt="">Services</a>
      <a href="reports.php"><img src="../assets/bar-chart.png" class="nav-icon" alt="">Reports</a>
      <a href="settings.php"><span class="nav-icon">⚙</span>Settings</a>
    </nav>
    <div class="admin-sidebar-bottom"><a href="../auth/logout.php"><span>⇥</span>Logout</a></div>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar">
      <div class="admin-topbar-title"><span class="admin-menu-icon">☰</span>Customers</div>
      <div class="admin-account"><div class="admin-account-x">X</div><div><strong>Xoru Radiator Pro</strong><small>Administrator</small></div><span>⌄</span></div>
    </header>
    <div class="admin-content">
      <div class="admin-settings-header"><h1>Customers</h1><p>View the customers who have registered and their booking activity.</p></div>
      <section class="admin-card">
        <div class="admin-card-header"><h2>Customer Accounts</h2></div>
        <div class="admin-table-wrap"><table class="admin-table customers-table"><thead><tr><th>Customer</th><th>Email</th><th>Login Method</th><th>Bookings</th><th>Registered</th></tr></thead><tbody>
        <?php if (!$customers): ?><tr><td colspan="5">No customers registered yet.</td></tr><?php endif; ?>
        <?php foreach ($customers as $customer): ?><tr>
          <td><strong><?= e($customer['name']) ?></strong></td>
          <td><a class="admin-link" href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a></td>
          <td><?= e(ucfirst($customer['provider'])) ?></td>
          <td><?= (int)$customer['booking_count'] ?></td>
          <td><?= e(date('M d, Y', strtotime($customer['created_at']))) ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
      </section>
      <div class="admin-footer-actions"><a class="admin-btn outline" href="dashboard.php">← Back to Dashboard</a></div>
    </div>
  </main>
</div>
</body>
</html>
