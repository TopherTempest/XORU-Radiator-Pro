<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/database/validation.php';
require_once __DIR__ . '/database/function.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); exit('Invalid request token. Please go back and try again.'); }
if (empty($_SESSION['customer_id'])) { http_response_code(401); exit('Please login or create a customer account before making a request.'); }

$type = $_POST['form_type'] ?? 'booking';
if (!in_array($type, ['booking', 'estimate'], true)) { http_response_code(400); exit('Invalid request type.'); }

$validation = validateBookingInput($_POST, $type === 'booking');
$data = $validation['data'];
$error = implode(' ', $validation['errors']);
$customerId = (int) $_SESSION['customer_id'];

if ($error === '') {
    $serviceCheck = $pdo->prepare('SELECT id, service_name FROM services WHERE id = ? LIMIT 1');
    $serviceCheck->execute([(int)$data['serviceId']]);
    $service = $serviceCheck->fetch();
    if (!$service) $error = 'Selected service does not exist.';
}

$availability = null;
if ($error === '' && $type === 'booking') {
    $slot = $pdo->prepare('SELECT id, available_date, available_time, status FROM availability WHERE id = ? LIMIT 1');
    $slot->execute([(int)$data['availabilityId']]);
    $availability = $slot->fetch();
    if (!$availability || $availability['status'] !== 'Available') {
        $error = 'That date and time is no longer available. Please choose another slot.';
    } elseif ($availability['available_date'] !== $data['date'] || substr($availability['available_time'], 0, 5) !== $data['time']) {
        $error = 'The selected schedule does not match the requested date and time.';
    } elseif ($data['date'] < date('Y-m-d')) {
        $error = 'Please choose a future date.';
    }
}

if ($error === '') {
    try {
        $pdo->beginTransaction();
        $dateValue = $data['date'] !== '' ? $data['date'] : null;
        $timeValue = $data['time'] !== '' ? $data['time'] . ':00' : null;
        $availabilityId = $type === 'booking' ? (int)$data['availabilityId'] : null;

        if ($type === 'booking') {
            $lock = $pdo->prepare("SELECT id FROM availability WHERE id = ? AND status = 'Available' FOR UPDATE");
            $lock->execute([$availabilityId]);
            if (!$lock->fetch()) throw new RuntimeException('That schedule was just taken. Please choose another slot.');
        }

        $stmt = $pdo->prepare('INSERT INTO bookings (customer_id, form_type, name, phone, vehicle, service_id, availability_id, date, time, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$customerId, $type, $data['name'], $data['phone'], $data['vehicle'], (int)$data['serviceId'], $availabilityId, $dateValue, $timeValue, $data['message']]);
        $bookingId = (int)$pdo->lastInsertId();

        if ($type === 'booking') {
            $updateSlot = $pdo->prepare("UPDATE availability SET status = 'Booked' WHERE id = ?");
            $updateSlot->execute([$availabilityId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        $error = 'Your request could not be saved. Please try again.';
    }
}

if ($error === '') {
    if ($type === 'booking') {
        header('Location: customer/dashboard.php?booked=1');
        exit;
    }
    header('Location: customer/dashboard.php?estimated=1');
    exit;

    $label = $type === 'estimate' ? 'Estimate request' : 'Booking request';
    $safeName = e($data['name']); $safePhone = e($data['phone']); $safeVehicle = e($data['vehicle']);
    $safeService = e($service['service_name']);
    $safeDate = e($data['date'] ?: 'Not specified');
    $safeTime = e($data['time'] ?: 'Not specified');
    $safeMessage = nl2br(e($data['message']));
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>XORU — Request</title><link rel="stylesheet" href="style.css"></head>
<body><main class="wrap" style="min-height:70vh;display:grid;place-items:center"><section class="modal-box" style="margin:auto">
<?php if ($error): ?><h2>Something went wrong</h2><p><?= e($error) ?></p>
<?php else: ?><h2><?= e($label) ?> received</h2><p>Thanks, <?= $safeName ?>. Your request has been saved to your customer account.</p><p><strong>Vehicle:</strong> <?= $safeVehicle ?><br><strong>Service:</strong> <?= $safeService ?><br><strong>Phone:</strong> <?= $safePhone ?><br><strong>Date:</strong> <?= $safeDate ?><br><strong>Time:</strong> <?= $safeTime ?><br><strong>Message:</strong> <?= $safeMessage ?></p><?php endif; ?>
<a class="btn primary" href="customer/dashboard.php">My Account</a><a class="btn outline" href="index.php">Back to XORU</a>
</section></main></body></html>
