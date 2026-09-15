<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';
require_once __DIR__ . '/../database/validation.php';

requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(403); exit('Invalid request.'); }

$action = $_POST['action'] ?? '';
$bookingId = (int)($_POST['booking_id'] ?? 0);

try {
    if ($action === 'status') {
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['Approved','Declined','Completed','Cancelled'], true)) throw new RuntimeException('Invalid status.');
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT availability_id FROM bookings WHERE id = ? FOR UPDATE');
        $stmt->execute([$bookingId]); $booking = $stmt->fetch();
        if (!$booking) throw new RuntimeException('Booking not found.');
        $update = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        $update->execute([$status, $bookingId]);
        if ($booking['availability_id']) {
            $slotStatus = in_array($status, ['Declined','Cancelled'], true) ? 'Available' : 'Booked';
            $slot = $pdo->prepare('UPDATE availability SET status = ? WHERE id = ?');
            $slot->execute([$slotStatus, (int)$booking['availability_id']]);
        }
        $pdo->commit();
        $_SESSION['admin_message'] = 'Booking status updated.';
    } elseif ($action === 'reschedule') {
        $newSlotId = (int)($_POST['availability_id'] ?? 0);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT availability_id FROM bookings WHERE id = ? FOR UPDATE');
        $stmt->execute([$bookingId]); $booking = $stmt->fetch();
        if (!$booking) throw new RuntimeException('Booking not found.');
        $slot = $pdo->prepare("SELECT id, available_date, available_time FROM availability WHERE id = ? AND status = 'Available' FOR UPDATE");
        $slot->execute([$newSlotId]); $newSlot = $slot->fetch();
        if (!$newSlot) throw new RuntimeException('Selected new schedule is not available.');
        if ($booking['availability_id']) {
            $old = $pdo->prepare("UPDATE availability SET status = 'Available' WHERE id = ?");
            $old->execute([(int)$booking['availability_id']]);
        }
        $reserve = $pdo->prepare("UPDATE availability SET status = 'Booked' WHERE id = ?");
        $reserve->execute([$newSlotId]);
        $update = $pdo->prepare("UPDATE bookings SET availability_id = ?, date = ?, time = ?, status = 'Pending', admin_note = ? WHERE id = ?");
        $note = 'Booking rescheduled by admin. Awaiting customer confirmation.';
        $update->execute([$newSlotId, $newSlot['available_date'], $newSlot['available_time'], $note, $bookingId]);
        $pdo->commit();
        $_SESSION['admin_message'] = 'Booking rescheduled and returned to Pending.';
    } elseif ($action === 'add_slot') {
        $validation = validateScheduleInput($_POST);
        if ($validation['errors']) throw new RuntimeException(implode(' ', $validation['errors']));
        $data = $validation['data'];
        $stmt = $pdo->prepare('INSERT INTO availability (available_date, available_time, status) VALUES (?, ?, \'Available\')');
        $stmt->execute([$data['date'], $data['time'] . ':00']);
        $_SESSION['admin_message'] = 'New available schedule added.';
    } elseif ($action === 'block_slot') {
        $slotId = (int)($_POST['availability_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE availability SET status = 'Blocked' WHERE id = ? AND status = 'Available'");
        $stmt->execute([$slotId]);
        $_SESSION['admin_message'] = 'Schedule slot blocked.';
    } elseif ($action === 'unblock_slot') {
        $slotId = (int)($_POST['availability_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE availability SET status = 'Available' WHERE id = ? AND status = 'Blocked'");
        $stmt->execute([$slotId]);
        $_SESSION['admin_message'] = 'Schedule slot made available.';
    } else {
        throw new RuntimeException('Invalid action.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['admin_error'] = $e instanceof PDOException && (int)($e->errorInfo[1] ?? 0) === 1062 ? 'That date and time already exists.' : $e->getMessage();
}
header('Location: dashboard.php'); exit;
