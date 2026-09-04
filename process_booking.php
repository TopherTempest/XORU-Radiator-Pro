<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$type = $_POST['form_type'] ?? 'booking';
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$vehicle = trim($_POST['vehicle'] ?? '');
$date = trim($_POST['date'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '' || $vehicle === '' || $message === '') {
    http_response_code(400);
    $error = 'Please complete all required fields.';
} else {
    $label = $type === 'estimate'
        ? 'Estimate request'
        : 'Diagnostic booking request';

    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $safeVehicle = htmlspecialchars($vehicle, ENT_QUOTES, 'UTF-8');
    $safeDate = htmlspecialchars(
        $date ?: 'Not specified',
        ENT_QUOTES,
        'UTF-8'
    );

    $safeMessage = nl2br(
        htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>XORU — Request</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <main
        class="wrap"
        style="min-height:70vh;display:grid;place-items:center"
    >

        <section
            class="modal-box"
            style="margin:auto"
        >

            <?php if (isset($error)): ?>

                <h2>Something went wrong</h2>

                <p><?= $error ?></p>

            <?php else: ?>

                <h2><?= $label ?> received</h2>

                <p>
                    Thanks, <?= $safeName ?>.
                    Your request has been recorded by this demo website.
                </p>

                <p>
                    <strong>Vehicle:</strong>
                    <?= $safeVehicle ?>

                    <br>

                    <strong>Phone:</strong>
                    <?= $safePhone ?>

                    <br>

                    <strong>Date:</strong>
                    <?= $safeDate ?>

                    <br>

                    <strong>Message:</strong>
                    <?= $safeMessage ?>
                </p>

            <?php endif; ?>

            <a
                class="btn primary"
                href="index.php"
            >
                Back to XORU
            </a>

        </section>

    </main>

</body>

</html>