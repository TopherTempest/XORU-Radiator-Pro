<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

$adminId = requireAdmin();
$csrf = ensureCsrfToken();
$adminStmt = $pdo->prepare('SELECT id, username FROM admins WHERE id = ? LIMIT 1');
$adminStmt->execute([$adminId]);
$admin = $adminStmt->fetch();
if (!$admin) {
    $_SESSION = [];
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$reports = $pdo->query('SELECT r.*, c.name AS customer_name, c.email AS customer_email FROM reports r JOIN customers c ON c.id = r.customer_id ORDER BY r.created_at DESC')->fetchAll();
$message = $_SESSION['report_message'] ?? '';
$error = $_SESSION['report_error'] ?? '';
unset($_SESSION['report_message'], $_SESSION['report_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>XORU Admin - Reports</title>
<link rel="stylesheet" href="../style.css">
<script>(function(){if(localStorage.getItem('xoruAdminTheme')==='dark')document.documentElement.classList.add('admin-dark');})();</script>
</head>
<body class="admin-page">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-logo">
    <img src="../assets/xoru-blue.png" alt="XORU Radiator Pro Logo">
</div>
    <div class="admin-profile"><div class="admin-avatar">♙</div><div><strong><?= e($admin['username']) ?></strong><span>Administrator</span></div></div>
    <nav class="admin-nav">
      <a href="dashboard.php"><img src="../assets/dashboard.png" class="nav-icon" alt="">Dashboard</a>
      <a href="dashboard.php#calendar"> <img src="../assets/calendar.png" class="nav-icon" alt="">Calendar</a>
      <a href="dashboard.php#bookings"><img src="../assets/book.png" class="nav-icon" alt="">Bookings</a>
      <a href="customers.php"> <img src="../assets/rook.png" class="nav-icon" alt="">Customers</a>
      <a href="dashboard.php#services"><img src="../assets/add-friend.png" class="nav-icon" alt="">Services</a>
      <a class="active" href="reports.php"> <img src="../assets/bar-chart.png" class="nav-icon" alt="">Reports</a>
      <a href="settings.php"><img src="../assets/settings.png" class="nav-icon" alt="">Settings</a>
    </nav>
    <div class="admin-sidebar-bottom"><a href="../auth/logout.php"><span>⇥</span>Logout</a></div>
  </aside>

  <main class="admin-main">
    <header class="admin-topbar">
      <div class="admin-topbar-title"><span class="admin-menu-icon">☰</span>Reports</div>
      <div class="admin-account"><div class="admin-account-x">
    <img src="../assets/xoru-blue.png" alt="XORU Logo">
</div>
<div><strong>Xoru Radiator Pro</strong><small>Administrator</small></div><span>⌄</span></div>
    </header>
    <div class="admin-content">
      <?php if ($message): ?><div class="admin-alert"><?= e($message) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="admin-login-error"><?= e($error) ?></div><?php endif; ?>
      <div class="admin-settings-header"><h1>Customer Reports &amp; Reviews</h1><p>View inconveniences reported by customers and their reviews.</p></div>
      <section class="admin-card">
        <div class="admin-card-header"><h2>Submitted Reports</h2><span class="admin-link"><?= count($reports) ?> total</span></div>
        <div class="admin-table-wrap"><table class="admin-table reports-table"><thead><tr><th>Customer</th><th>Type</th><th>Rating</th><th>Subject / Message</th><th>Status</th><th>Submitted</th><th>Admin Reply</th><th>Action</th></tr></thead><tbody>
        <?php if (!$reports): ?><tr><td colspan="8">No customer reports or reviews yet.</td></tr><?php endif; ?>
        <?php foreach ($reports as $report): ?><tr>
          <td><strong><?= e($report['customer_name']) ?></strong><br><small><a class="admin-link" href="mailto:<?= e($report['customer_email']) ?>"><?= e($report['customer_email']) ?></a></small></td>
          <td><?= e($report['type']) ?></td>
          <td><?= $report['rating'] ? e(str_repeat('★', (int)$report['rating']) . str_repeat('☆', 5-(int)$report['rating'])) : '—' ?></td>
          <td><strong><?= e($report['subject']) ?></strong><br><span><?= nl2br(e($report['message'])) ?></span></td>
          <td><span class="report-status <?= strtolower($report['status']) ?>"><?= e($report['status']) ?></span></td>
          <td><?= e(date('M d, Y h:i A', strtotime($report['created_at']))) ?></td>
          <td><?= e($report['admin_reply'] ?: '—') ?></td>
          <td style="min-width:260px">
            <form action="report_action.php" method="post" class="admin-report-form">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="report_id" value="<?= (int)$report['id'] ?>">
              <select name="status" required><option value="New"<?= $report['status']==='New'?' selected':'' ?>>New</option><option value="Read"<?= $report['status']==='Read'?' selected':'' ?>>Read</option><option value="Resolved"<?= $report['status']==='Resolved'?' selected':'' ?>>Resolved</option></select>
              <textarea name="admin_reply" rows="2" placeholder="Reply to customer..."><?= e($report['admin_reply'] ?? '') ?></textarea>
              <button class="admin-btn primary" type="submit">Save</button>
            </form>
          </td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
      </section>
      <div class="admin-footer-actions"><a class="admin-btn outline" href="dashboard.php">← Back to Dashboard</a></div>
    </div>
  </main>
</div>
</body>
</html>
