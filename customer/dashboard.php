<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

$customerId = requireCustomer();
$csrf = ensureCsrfToken();

$customerStmt = $pdo->prepare('SELECT id, name, email, provider FROM customers WHERE id = ? LIMIT 1');
$customerStmt->execute([$customerId]);
$customer = $customerStmt->fetch();
if (!$customer) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare('SELECT b.*, s.service_name FROM bookings b JOIN services s ON s.id=b.service_id WHERE b.customer_id=? ORDER BY b.created_at DESC');
$stmt->execute([$customerId]);
$bookings = $stmt->fetchAll();

$services = $pdo->query('SELECT id, service_name FROM services ORDER BY id')->fetchAll();
$slots = $pdo->query("SELECT id, available_date, available_time FROM availability WHERE status = 'Available' AND available_date >= CURDATE() ORDER BY available_date, available_time")->fetchAll();
$booked = isset($_GET['booked']) && $_GET['booked'] === '1';
$estimated = isset($_GET['estimated']) && $_GET['estimated'] === '1';
$reportSubmitted = isset($_GET['reported']) && $_GET['reported'] === '1';
$reportsStmt = $pdo->prepare('SELECT id, type, rating, subject, message, status, admin_reply, created_at FROM reports WHERE customer_id = ? ORDER BY created_at DESC');
$reportsStmt->execute([$customerId]);
$reports = $reportsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My XORU Account</title>
<link rel="stylesheet" href="../style.css">
<script>
(function () {
  const theme = localStorage.getItem('xoruCustomerTheme') || 'dark';
  document.documentElement.classList.add(theme === 'light' ? 'customer-light' : 'customer-dark');
})();
</script>
<style>
.portal-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:24px 0}
.portal-card{background:#22242a;border:1px solid #41444c;border-radius:16px;padding:20px}
.portal-card h3{margin:0 0 6px}.portal-card p{margin:0 0 14px;color:#bbb}
.portal-panel{display:none;margin:20px 0}.portal-panel.active{display:block}
.success-box{background:#12351e;border:1px solid #38a85a;color:#b8f3c8;padding:14px;border-radius:12px;margin:16px 0}
.status-booked{display:inline-block;color:#b8f3c8;background:#174c27;border:1px solid #38a85a;border-radius:999px;padding:5px 10px;font-weight:800}.status-danger{display:inline-block;color:#ffb3b3;background:#4b1c1c;border:1px solid #a94b4b;border-radius:999px;padding:5px 10px;font-weight:800}
.portal-table{width:100%;border-collapse:collapse}.portal-table th,.portal-table td{padding:10px;border-bottom:1px solid #3d3f46;text-align:left;vertical-align:top}
@media(max-width:700px){.portal-actions{grid-template-columns:1fr}}
</style>
</head>
<body class="customer-page">
<main class="wrap" style="padding:40px 0">
<section class="modal-box" style="width:min(1100px,100%);margin:auto">
  <h2>Welcome, <?= e($customer['name']) ?>!</h2>
  <p>Manage your repair requests, estimates, and schedules from your customer account.</p>

  <?php if ($booked): ?><div class="success-box"><strong>Booking submitted successfully!</strong> Your selected schedule is now marked as <strong>Booked</strong> and is waiting for admin approval.</div><?php endif; ?>
  <?php if ($estimated): ?><div class="success-box"><strong>Estimate request submitted!</strong> Your request has been saved to your account.</div><?php endif; ?>

  <div class="portal-actions">
    <button class="btn primary" type="button" data-panel="bookingPanel">Book a Repair</button>
    <button class="btn outline" type="button" data-panel="estimatePanel">Get an Estimate</button>
    <button class="btn outline" type="button" data-panel="schedulePanel">Available Date &amp; Time</button>
    <a class="btn outline" href="#bookings">My Bookings</a>
    <button class="btn outline" type="button" data-panel="reportPanel">Report / Review</button>
    <a class="btn outline" href="settings.php">Settings</a>
  </div>

  <section id="bookingPanel" class="portal-panel portal-card">
    <h3>Book a Repair</h3>
    <p>Choose one of the currently available schedules.</p>
    <form action="../process_booking.php" method="post" class="portal-form" style="display:grid;gap:12px">
      <input type="hidden" name="form_type" value="booking">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input name="name" value="<?= e($customer['name']) ?>" placeholder="Full Name" required>
      <input name="phone" placeholder="Phone Number" required>
      <input name="vehicle" placeholder="Vehicle / Model" required>
      <select name="service_id" required><option value="">Select Service</option><?php foreach($services as $service): ?><option value="<?= (int)$service['id'] ?>"><?= e($service['service_name']) ?></option><?php endforeach; ?></select>
      <select name="availability_id" id="dashboardAvailability" required>
        <option value="">Select Available Date &amp; Time</option>
        <?php foreach($slots as $slot): ?><option value="<?= (int)$slot['id'] ?>" data-date="<?= e($slot['available_date']) ?>" data-time="<?= e(substr($slot['available_time'],0,5)) ?>"><?= e(date('M d, Y',strtotime($slot['available_date']))) ?> - <?= e(date('h:i A',strtotime($slot['available_time']))) ?></option><?php endforeach; ?>
      </select>
      <input type="hidden" name="date" id="dashboardDate"><input type="hidden" name="time" id="dashboardTime">
      <textarea name="message" placeholder="What seems to be wrong?" required></textarea>
      <button class="btn primary" type="submit">Confirm Booking</button>
    </form>
  </section>

  <section id="estimatePanel" class="portal-panel portal-card">
    <h3>Get an Estimate</h3>
    <p>Request an estimate without leaving your customer account.</p>
    <form action="../process_booking.php" method="post" style="display:grid;gap:12px">
      <input type="hidden" name="form_type" value="estimate"><input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input name="name" value="<?= e($customer['name']) ?>" placeholder="Full Name" required>
      <input name="phone" placeholder="Phone Number" required>
      <input name="vehicle" placeholder="Vehicle / Model" required>
      <select name="service_id" required><option value="">Select Service</option><?php foreach($services as $service): ?><option value="<?= (int)$service['id'] ?>"><?= e($service['service_name']) ?></option><?php endforeach; ?></select>
      <textarea name="message" placeholder="Describe the problem" required></textarea>
      <button class="btn primary" type="submit">Request Estimate</button>
    </form>
  </section>

  <section id="schedulePanel" class="portal-panel portal-card">
    <h3>Available Dates &amp; Times</h3>
    <?php if (!$slots): ?><p>No available schedule slots have been added yet.</p><?php else: ?>
    <div style="overflow:auto"><table class="portal-table"><tr><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr>
    <?php foreach($slots as $slot): ?><tr><td><?= e(date('M d, Y',strtotime($slot['available_date']))) ?></td><td><?= e(date('h:i A',strtotime($slot['available_time']))) ?></td><td class="status-booked">Available</td><td><button class="btn primary choose-slot" type="button" data-panel="bookingPanel" data-slot="<?= (int)$slot['id'] ?>">Book</button></td></tr><?php endforeach; ?></table></div>
    <?php endif; ?>
  </section>



  <section id="reportPanel" class="portal-panel portal-card">
    <h3>Report an Inconvenience / Leave a Review</h3>
    <p>Tell us about an inconvenience you experienced or share your feedback.</p>
    <form action="submit_report.php" method="post" style="display:grid;gap:12px">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <select name="type" id="reportType" required>
        <option value="Inconvenience">Report an Inconvenience</option>
        <option value="Review">Leave a Review</option>
      </select>
      <div id="reportRatingWrap">
        <label for="reportRating">Rating (for reviews)</label>
        <select name="rating" id="reportRating">
          <option value="">Select rating</option>
          <option value="5">★★★★★ - 5</option>
          <option value="4">★★★★☆ - 4</option>
          <option value="3">★★★☆☆ - 3</option>
          <option value="2">★★☆☆☆ - 2</option>
          <option value="1">★☆☆☆☆ - 1</option>
        </select>
      </div>
      <input name="subject" maxlength="200" placeholder="Subject" required>
      <textarea name="message" maxlength="5000" placeholder="Describe what happened or tell us about your experience" required></textarea>
      <button class="btn primary" type="submit">Submit Report / Review</button>
    </form>
  </section>

  <h3 id="bookings" style="margin-top:30px">My Bookings &amp; Estimate Requests</h3>
  <?php if (!$bookings): ?><p>No requests yet.</p><?php else: ?>
  <div style="overflow:auto"><table class="portal-table"><tr><th>Type</th><th>Service</th><th>Vehicle</th><th>Schedule</th><th>Status</th><th>Admin Note</th></tr>
  <?php foreach($bookings as $b): ?><tr>
    <td><?= e(ucfirst($b['form_type'])) ?></td><td><?= e($b['service_name']) ?></td><td><?= e($b['vehicle']) ?></td>
    <td><?= e($b['date'] ? date('M d, Y',strtotime($b['date'])) : 'Not scheduled') ?><br><?= e($b['time'] ? date('h:i A',strtotime($b['time'])) : '—') ?></td>
    <td><?php if ($b['form_type'] === 'booking' && $b['status'] === 'Pending'): ?><span class="status-booked">BOOKED</span><br><small>Pending admin approval</small><?php elseif ($b['status'] === 'Declined' || $b['status'] === 'Cancelled'): ?><span class="status-danger"><?= e(strtoupper($b['status'])) ?></span><?php else: ?><strong class="<?= $b['status'] === 'Approved' || $b['status'] === 'Completed' ? 'status-booked' : '' ?>"><?= e(strtoupper($b['status'])) ?></strong><?php endif; ?></td>
    <td><?= e($b['admin_note'] ?: '—') ?></td>
  </tr><?php endforeach; ?></table></div>
  <?php endif; ?>


  <h3 id="myReports" style="margin-top:30px">My Reports &amp; Reviews</h3>
  <?php if ($reportSubmitted): ?><div class="success-box"><strong>Your report/review was submitted successfully.</strong> Thank you for your feedback.</div><?php endif; ?>
  <?php if (!$reports): ?><p>No reports or reviews yet.</p><?php else: ?>
  <div style="overflow:auto"><table class="portal-table"><tr><th>Type</th><th>Subject</th><th>Rating</th><th>Status</th><th>Admin Reply</th><th>Date</th></tr>
  <?php foreach($reports as $report): ?><tr>
    <td><?= e($report['type']) ?></td>
    <td><strong><?= e($report['subject']) ?></strong><br><?= e($report['message']) ?></td>
    <td><?= $report['rating'] ? e(str_repeat('★', (int)$report['rating']) . str_repeat('☆', 5-(int)$report['rating'])) : '—' ?></td>
    <td><span class="report-status <?= strtolower($report['status']) ?>"><?= e($report['status']) ?></span></td>
    <td><?= e($report['admin_reply'] ?: '—') ?></td>
    <td><?= e(date('M d, Y', strtotime($report['created_at']))) ?></td>
  </tr><?php endforeach; ?></table></div>
  <?php endif; ?>

  <div style="margin-top:25px;display:flex;gap:10px;flex-wrap:wrap"><a class="btn outline" href="../index.php">View Website</a><a class="btn outline" href="logout.php">Logout</a></div>
</section>
</main>
<script>
document.querySelectorAll('[data-panel]').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.panel;
    const panel = document.getElementById(id);
    if (!panel) return;
    document.querySelectorAll('.portal-panel').forEach(p => p.classList.remove('active'));
    panel.classList.add('active');
    panel.scrollIntoView({behavior:'smooth', block:'start'});
  });
});
const select = document.getElementById('dashboardAvailability');
if (select) select.addEventListener('change', function(){
  const o=this.options[this.selectedIndex];
  document.getElementById('dashboardDate').value=o.dataset.date||'';
  document.getElementById('dashboardTime').value=o.dataset.time||'';
});
document.querySelectorAll('.choose-slot').forEach(btn => btn.addEventListener('click', () => {
  document.querySelectorAll('.portal-panel').forEach(p=>p.classList.remove('active'));
  document.getElementById('bookingPanel').classList.add('active');
  select.value=btn.dataset.slot;
  select.dispatchEvent(new Event('change'));
  document.getElementById('bookingPanel').scrollIntoView({behavior:'smooth',block:'start'});
}));
const reportType = document.getElementById('reportType');
const reportRatingWrap = document.getElementById('reportRatingWrap');
const reportRating = document.getElementById('reportRating');
function updateReportRating() {
  const review = reportType && reportType.value === 'Review';
  if (reportRatingWrap) reportRatingWrap.style.display = review ? 'grid' : 'none';
  if (reportRating) reportRating.required = review;
}
if (reportType) { reportType.addEventListener('change', updateReportRating); updateReportRating(); }
</script>
</body>
</html>
