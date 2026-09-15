<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/function.php';

$adminId = requireAdmin();
$csrf = ensureCsrfToken();

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


/* =========================
   BOOKINGS
========================= */

$bookings = $pdo->query(
    "SELECT 
        b.*,
        s.service_name,
        c.name AS customer_name,
        c.email AS customer_email
     FROM bookings b
     JOIN services s ON s.id = b.service_id
     JOIN customers c ON c.id = b.customer_id
     ORDER BY b.created_at DESC"
)->fetchAll();


/* =========================
   AVAILABLE SLOTS
========================= */

$slots = $pdo->query(
    "SELECT 
        id,
        available_date,
        available_time,
        status
     FROM availability
     WHERE available_date >= CURDATE()
     ORDER BY available_date, available_time"
)->fetchAll();


$freeSlots = $pdo->query(
    "SELECT 
        id,
        available_date,
        available_time
     FROM availability
     WHERE status = 'Available'
       AND available_date >= CURDATE()
     ORDER BY available_date, available_time"
)->fetchAll();


/* =========================
   SUBSCRIBERS
========================= */

$subscribers = $pdo->query(
    "SELECT 
        id,
        email,
        created_at
     FROM subscribers
     ORDER BY created_at DESC"
)->fetchAll();


/* =========================
   MESSAGES
========================= */

$message = $_SESSION['admin_message'] ?? '';
$error = $_SESSION['admin_error'] ?? '';

unset(
    $_SESSION['admin_message'],
    $_SESSION['admin_error']
);


/* =========================
   DATE / MONTH DATA
========================= */

$today = new DateTimeImmutable('today');

$monthKey = $today->format('Y-m');

$monthBookings = array_values(
    array_filter(
        $bookings,
        fn($b) =>
            !empty($b['date']) &&
            substr($b['date'], 0, 7) === $monthKey
    )
);

$totalBookings = count($monthBookings);

$pendingBookings = count(
    array_filter(
        $monthBookings,
        fn($b) => $b['status'] === 'Pending'
    )
);

$confirmedBookings = count(
    array_filter(
        $monthBookings,
        fn($b) => $b['status'] === 'Approved'
    )
);

$completedBookings = count(
    array_filter(
        $monthBookings,
        fn($b) => $b['status'] === 'Completed'
    )
);

$totalCustomers = (int) $pdo
    ->query('SELECT COUNT(*) FROM customers')
    ->fetchColumn();


/* =========================
   CALENDAR
========================= */

$monthStart = $today->modify('first day of this month');
$monthEnd = $today->modify('last day of this month');

$monthLabel = $today->format('F Y');

$calendarStart = $monthStart->modify('sunday this week');
$calendarEnd = $monthEnd->modify('saturday this week');

$calendarCounts = [];

foreach ($bookings as $b) {
    if (!empty($b['date'])) {
        $calendarCounts[$b['date']] =
            ($calendarCounts[$b['date']] ?? 0) + 1;
    }
}


/* =========================
   TODAY'S BOOKINGS
========================= */

$todayBookings = array_values(
    array_filter(
        $bookings,
        fn($b) =>
            !empty($b['date']) &&
            $b['date'] === $today->format('Y-m-d')
    )
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>XORU Admin Dashboard</title>

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


    <!-- =========================
         SIDEBAR
    ========================== -->

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

            <a
                class="active"
                href="dashboard.php"
            >
                <img src="../assets/dashboard.png" class="nav-icon" alt="">
                Dashboard
            </a>


            <a href="#calendar">
                <img src="../assets/calendar.png" class="nav-icon" alt="">
                Calendar
            </a>


            <a href="#bookings">
                <img src="../assets/book.png" class="nav-icon" alt="">
                Bookings
            </a>


            <a href="customers.php">
                <img src="../assets/rook.png" class="nav-icon" alt="">
                Customers
            </a>


            <a href="#subscribers">
                <img src="../assets/add-friend.png" class="nav-icon" alt="">
                Subscribers
            </a>


            <a href="#slots">
                <img src="../assets/24-hours.png" class="nav-icon" alt="">
                Services
            </a>


            <a href="reports.php">
                <img src="../assets/bar-chart.png" class="nav-icon" alt="">
                Reports
            </a>


            <a href="settings.php">
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


    <!-- =========================
         MAIN
    ========================== -->

    <main class="admin-main">


        <!-- TOP BAR -->

        <header class="admin-topbar">

            <div class="admin-topbar-title">

                <span class="admin-menu-icon">☰</span>

                Dashboard

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


        <!-- CONTENT -->

        <div class="admin-content">


            <!-- ALERTS -->

            <?php if ($message): ?>

                <div class="admin-alert">
                    <?= e($message) ?>
                </div>

            <?php endif; ?>


            <?php if ($error): ?>

                <div class="admin-login-error">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>


            <!-- =========================
                 METRICS
            ========================== -->

            <section class="admin-metrics">


                <!-- TOTAL BOOKINGS -->

                <div class="admin-metric">

                    <div class="metric-icon">

                        <img
                            src="../assets/chart.png"
                            alt="Total Bookings"
                        >

                    </div>

                    <div>

                        <strong>
                            <?= $totalBookings ?>
                        </strong>

                        <span>
                            Total Bookings<br>
                            This Month
                        </span>

                    </div>

                </div>


                <!-- PENDING BOOKINGS -->

                <div class="admin-metric">

                    <div class="metric-icon">

                        <img
                            src="../assets/appointment.png"
                            alt="Pending Bookings"
                        >

                    </div>

                    <div>

                        <strong>
                            <?= $pendingBookings ?>
                        </strong>

                        <span>
                            Pending<br>
                            Bookings
                        </span>

                    </div>

                </div>


                <!-- CONFIRMED BOOKINGS -->

                <div class="admin-metric">

                    <div class="metric-icon">

                        <img
                            src="../assets/confirm.png"
                            alt="Confirmed Bookings"
                        >

                    </div>

                    <div>

                        <strong>
                            <?= $confirmedBookings ?>
                        </strong>

                        <span>
                            Confirmed<br>
                            Bookings
                        </span>

                    </div>

                </div>


                <!-- TOTAL CUSTOMERS -->

                <div class="admin-metric">

                    <div class="metric-icon">

                        <img
                            src="../assets/user.png"
                            alt="Total Customers"
                        >

                    </div>

                    <div>

                        <strong>
                            <?= $totalCustomers ?>
                        </strong>

                        <span>
                            Total<br>
                            Customers
                        </span>

                    </div>

                </div>


            </section>


            <!-- =========================
                 CALENDAR + TODAY
            ========================== -->

            <section class="admin-grid">


                <!-- CALENDAR -->

                <div
                    class="admin-card"
                    id="calendar"
                >

                    <div class="admin-card-header">

                        <h2>
                            Booking Calendar
                        </h2>

                        <span class="admin-link">
                            <?= e($monthLabel) ?>
                        </span>

                    </div>


                    <div class="admin-card-body">

                        <div class="admin-calendar-head">

                            <strong>
                                <?= e($monthLabel) ?>
                            </strong>

                            <a
                                class="admin-link"
                                href="#bookings"
                            >
                                View bookings
                            </a>

                        </div>


                        <table class="admin-calendar">

                            <thead>

                                <tr>

                                    <?php foreach (
                                        ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']
                                        as $d
                                    ): ?>

                                        <th><?= $d ?></th>

                                    <?php endforeach; ?>

                                </tr>

                            </thead>


                            <tbody>

                                <?php
                                for (
                                    $d = $calendarStart;
                                    $d <= $calendarEnd;
                                    $d = $d->modify('+1 day')
                                ):
                                ?>

                                    <?php if ($d->format('w') === '0'): ?>

                                        <tr>

                                    <?php endif; ?>


                                    <?php

                                    $key = $d->format('Y-m-d');

                                    $inMonth =
                                        $d->format('Y-m') === $monthKey;

                                    $count =
                                        $calendarCounts[$key] ?? 0;

                                    $isToday =
                                        $key ===
                                        $today->format('Y-m-d');

                                    ?>


                                    <td
                                        class="<?= !$inMonth ? 'empty ' : '' ?><?= $isToday ? 'today' : '' ?>"
                                    >

                                        <span class="day">
                                            <?= $d->format('j') ?>
                                        </span>


                                        <?php if ($inMonth): ?>

                                            <span class="count">

                                                <?= $count ?>

                                                booking<?= $count === 1 ? '' : 's' ?>

                                            </span>


                                            <?php if ($count > 0): ?>

                                                <span class="admin-dot"></span>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                    </td>


                                    <?php if ($d->format('w') === '6'): ?>

                                        </tr>

                                    <?php endif; ?>


                                <?php endfor; ?>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- TODAY'S BOOKINGS -->

                <div class="admin-card">

                    <div class="admin-card-header">

                        <h2>
                            Today's Bookings
                        </h2>

                        <a
                            class="admin-link"
                            href="#bookings"
                        >
                            View All
                        </a>

                    </div>


                    <div class="admin-card-body admin-today-list">


                        <?php if (!$todayBookings): ?>

                            <p style="color:#667085">
                                No bookings scheduled for today.
                            </p>


                        <?php else: ?>


                            <?php foreach (
                                array_slice($todayBookings, 0, 6)
                                as $b
                            ): ?>


                                <div class="admin-booking-row">


                                    <div class="admin-time">

                                        <?= $b['time']
                                            ? e(date('h:i', strtotime($b['time'])))
                                            : '—'
                                        ?>


                                        <small>

                                            <?= $b['time']
                                                ? e(date('A', strtotime($b['time'])))
                                                : ''
                                            ?>

                                        </small>

                                    </div>


                                    <div>

                                        <strong>
                                            <?= e($b['customer_name']) ?>
                                        </strong>

                                        <p>
                                            <?= e($b['vehicle']) ?>
                                            ·
                                            <?= e($b['service_name']) ?>
                                        </p>

                                    </div>


                                    <span
                                        class="admin-status <?= strtolower($b['status']) ?>"
                                    >

                                        <?= e(
                                            strtoupper(
                                                $b['status'] === 'Approved'
                                                    ? 'Confirmed'
                                                    : $b['status']
                                            )
                                        ) ?>

                                    </span>


                                </div>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>

                </div>


            </section>


            <!-- =========================
                 BOOKINGS
            ========================== -->

            <section
                class="admin-grid"
                style="margin-top:20px"
            >


                <!-- BOOKINGS -->

                <div
                    class="admin-card"
                    id="bookings"
                >

                    <div class="admin-card-header">

                        <h2>
                            Customer Booking Requests
                        </h2>

                        <a
                            class="admin-link"
                            href="#bookings"
                        >
                            View All
                        </a>

                    </div>


                    <div class="admin-table-wrap">

                        <table class="admin-table">

                            <thead>

                                <tr>

                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Type</th>
                                    <th>Service / Vehicle</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php if (!$bookings): ?>

                                    <tr>

                                        <td colspan="7">
                                            No booking requests yet.
                                        </td>

                                    </tr>

                                <?php endif; ?>


                                <?php foreach ($bookings as $b): ?>

                                    <tr>


                                        <td>

                                            <strong>
                                                <?= e($b['customer_name']) ?>
                                            </strong>

                                            <br>

                                            <small>
                                                <?= e($b['customer_email']) ?>
                                            </small>

                                        </td>


                                        <td>
                                            <?= e($b['phone']) ?>
                                        </td>


                                        <td>
                                            <?= e(ucfirst($b['form_type'])) ?>
                                        </td>


                                        <td>

                                            <?= e($b['service_name']) ?>

                                            <br>

                                            <small>
                                                <?= e($b['vehicle']) ?>
                                            </small>

                                        </td>


                                        <td>

                                            <?= e(
                                                $b['date']
                                                    ? date(
                                                        'M d, Y',
                                                        strtotime($b['date'])
                                                    )
                                                    : '—'
                                            ) ?>

                                            <br>

                                            <?= e(
                                                $b['time']
                                                    ? date(
                                                        'h:i A',
                                                        strtotime($b['time'])
                                                    )
                                                    : '—'
                                            ) ?>

                                        </td>


                                        <td>

                                            <span
                                                class="admin-status <?= strtolower($b['status']) ?>"
                                            >

                                                <?= e(
                                                    $b['status'] === 'Approved'
                                                        ? 'Confirmed'
                                                        : $b['status']
                                                ) ?>

                                            </span>

                                            <br>

                                            <small>
                                                <?= e($b['admin_note'] ?: '') ?>
                                            </small>

                                        </td>


                                        <td style="min-width:210px">


                                            <?php if ($b['form_type'] === 'booking'): ?>


                                                <!-- ACCEPT -->

                                                <form
                                                    action="action.php"
                                                    method="post"
                                                    style="display:inline-block;margin:2px"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrf) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="status"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="booking_id"
                                                        value="<?= (int)$b['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="Approved"
                                                    >

                                                    <button
                                                        class="admin-btn primary"
                                                        type="submit"
                                                    >
                                                        Accept
                                                    </button>

                                                </form>


                                                <!-- DECLINE -->

                                                <form
                                                    action="action.php"
                                                    method="post"
                                                    style="display:inline-block;margin:2px"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrf) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="status"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="booking_id"
                                                        value="<?= (int)$b['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="Declined"
                                                    >

                                                    <button
                                                        class="admin-btn outline"
                                                        type="submit"
                                                    >
                                                        Decline
                                                    </button>

                                                </form>


                                                <!-- RESCHEDULE -->

                                                <form
                                                    action="action.php"
                                                    method="post"
                                                    class="admin-form"
                                                    style="margin-top:7px"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrf) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="reschedule"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="booking_id"
                                                        value="<?= (int)$b['id'] ?>"
                                                    >

                                                    <select
                                                        name="availability_id"
                                                        required
                                                    >

                                                        <option value="">
                                                            Reschedule to...
                                                        </option>


                                                        <?php foreach ($freeSlots as $slot): ?>

                                                            <option
                                                                value="<?= (int)$slot['id'] ?>"
                                                            >

                                                                <?= e(
                                                                    date(
                                                                        'M d, Y',
                                                                        strtotime(
                                                                            $slot['available_date']
                                                                        )
                                                                    )
                                                                ) ?>

                                                                -

                                                                <?= e(
                                                                    date(
                                                                        'h:i A',
                                                                        strtotime(
                                                                            $slot['available_time']
                                                                        )
                                                                    )
                                                                ) ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>


                                                    <button
                                                        class="admin-btn outline"
                                                        type="submit"
                                                    >
                                                        Reschedule
                                                    </button>

                                                </form>


                                            <?php endif; ?>


                                            <?php if ($b['status'] === 'Approved'): ?>


                                                <!-- COMPLETED -->

                                                <form
                                                    action="action.php"
                                                    method="post"
                                                    style="display:inline-block;margin:2px"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrf) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="status"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="booking_id"
                                                        value="<?= (int)$b['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="Completed"
                                                    >

                                                    <button
                                                        class="admin-btn outline"
                                                        type="submit"
                                                    >
                                                        Completed
                                                    </button>

                                                </form>


                                            <?php endif; ?>


                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>

                </div>


            </section>


            <!-- =========================
                 AVAILABLE DATES & TIMES
            ========================== -->

            <section
                class="admin-card"
                id="slots"
                style="margin-top:20px"
            >

                <div class="admin-card-header">

                    <h2>
                        Manage Available Dates &amp; Times
                    </h2>

                </div>


                <div class="admin-card-body">


                    <form
                        action="action.php"
                        method="post"
                        class="admin-form"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrf) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="add_slot"
                        >


                        <div class="admin-form-row">

                            <input
                                type="date"
                                name="available_date"
                                required
                            >

                            <input
                                type="time"
                                name="available_time"
                                required
                            >

                        </div>


                        <button
                            class="admin-btn primary"
                            type="submit"
                        >
                            Add Available Slot
                        </button>

                    </form>


                    <div
                        class="admin-table-wrap"
                        style="margin-top:14px"
                    >

                        <table
                            class="admin-table"
                            style="min-width:450px"
                        >

                            <thead>

                                <tr>

                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($slots as $slot): ?>

                                    <tr>


                                        <td>

                                            <?= e(
                                                date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $slot['available_date']
                                                    )
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= e(
                                                date(
                                                    'h:i A',
                                                    strtotime(
                                                        $slot['available_time']
                                                    )
                                                )
                                            ) ?>

                                        </td>


                                        <td>
                                            <?= e($slot['status']) ?>
                                        </td>


                                        <td>


                                            <?php if ($slot['status'] === 'Available'): ?>


                                                <form
                                                    action="action.php"
                                                    method="post"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrf) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="block_slot"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="availability_id"
                                                        value="<?= (int)$slot['id'] ?>"
                                                    >

                                                    <button
                                                        class="admin-btn outline"
                                                        type="submit"
                                                    >
                                                        Block
                                                    </button>

                                                </form>


                                            <?php elseif ($slot['status'] === 'Blocked'): ?>


                                                <form
                                                    action="action.php"
                                                    method="post"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrf) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="unblock_slot"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="availability_id"
                                                        value="<?= (int)$slot['id'] ?>"
                                                    >

                                                    <button
                                                        class="admin-btn outline"
                                                        type="submit"
                                                    >
                                                        Make Available
                                                    </button>

                                                </form>


                                            <?php else: ?>

                                                Booked

                                            <?php endif; ?>


                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>

                </div>

            </section>


            <!-- =========================
                 SUBSCRIBERS
            ========================== -->

            <section
                class="admin-card"
                id="subscribers"
                style="margin-top:20px"
            >

                <div class="admin-card-header">

                    <h2>
                        Maintenance Tips Subscribers
                    </h2>

                    <span class="admin-link">

                        <?= count($subscribers) ?>

                        Subscriber<?= count($subscribers) === 1 ? '' : 's' ?>

                    </span>

                </div>


                <div class="admin-table-wrap">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>Email</th>
                                <th>Date Subscribed</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (!$subscribers): ?>

                                <tr>

                                    <td colspan="2">
                                        No subscribers yet.
                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($subscribers as $subscriber): ?>

                                    <tr>

                                        <td>

                                            <strong>
                                                <?= e($subscriber['email']) ?>
                                            </strong>

                                        </td>


                                        <td>

                                            <?= e(
                                                date(
                                                    'M d, Y h:i A',
                                                    strtotime(
                                                        $subscriber['created_at']
                                                    )
                                                )
                                            ) ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </section>


            <!-- =========================
                 FOOTER ACTIONS
            ========================== -->

            <div class="admin-footer-actions">

                <a
                    class="admin-btn outline"
                    href="../index.php"
                >
                    View Website
                </a>


                <a
                    class="admin-btn outline"
                    href="../auth/logout.php"
                >
                    Logout
                </a>

            </div>


        </div>

    </main>

</div>

</body>

</html>