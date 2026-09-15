<?php

function validateEmailFormat(string $value): ?string
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email address.';
}

function validateRequired(string $value, string $label): ?string
{
    return trim($value) === '' ? "$label is required." : null;
}

function validatePassword(string $value): ?string
{
    return strlen($value) >= 8 ? null : 'Password must be at least 8 characters.';
}

function validatePhone(string $value): ?string
{
    return preg_match('/^[0-9+\-\s()]{7,20}$/', $value) ? null : 'Enter a valid phone number.';
}

function validateDateFormat(string $value): ?string
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? null : 'Enter a valid date.';
}

function validateTimeFormat(string $value): ?string
{
    $time = DateTime::createFromFormat('H:i', $value);
    return $time && $time->format('H:i') === $value ? null : 'Enter a valid time.';
}

function validateCustomerRegistration(array $post): array
{
    $name = trim($post['name'] ?? '');
    $email = trim($post['email'] ?? '');
    $password = $post['password'] ?? '';
    $confirm = $post['confirm_password'] ?? '';

    $errors = array_filter([
        validateRequired($name, 'Full Name'),
        validateEmailFormat($email),
        validatePassword($password),
        $password === $confirm ? null : 'Passwords do not match.',
    ]);

    return ['errors' => array_values($errors), 'data' => compact('name', 'email', 'password', 'confirm')];
}

function validateCustomerLogin(array $post): array
{
    $email = trim($post['email'] ?? '');
    $password = $post['password'] ?? '';
    $errors = array_filter([validateEmailFormat($email), validateRequired($password, 'Password')]);
    return ['errors' => array_values($errors), 'data' => compact('email', 'password')];
}

function validateAdminLogin(array $post): array
{
    $username = trim($post['username'] ?? '');
    $password = $post['password'] ?? '';
    $errors = array_filter([validateRequired($username, 'Username'), validateRequired($password, 'Password')]);
    return ['errors' => array_values($errors), 'data' => compact('username', 'password')];
}

function validateBookingInput(array $post, bool $scheduleRequired = true): array
{
    $name = trim($post['name'] ?? '');
    $phone = trim($post['phone'] ?? '');
    $vehicle = trim($post['vehicle'] ?? '');
    $serviceId = trim((string)($post['service_id'] ?? ''));
    $availabilityId = trim((string)($post['availability_id'] ?? ''));
    $date = trim($post['date'] ?? '');
    $time = trim($post['time'] ?? '');
    $message = trim($post['message'] ?? '');

    $errors = array_filter([
        validateRequired($name, 'Name'),
        validatePhone($phone),
        validateRequired($vehicle, 'Vehicle'),
        validateRequired($serviceId, 'Service'),
        $scheduleRequired ? validateRequired($availabilityId, 'Available schedule') : null,
        $date !== '' ? validateDateFormat($date) : null,
        $time !== '' ? validateTimeFormat($time) : null,
        validateRequired($message, 'Message'),
    ]);

    if ($serviceId !== '' && filter_var($serviceId, FILTER_VALIDATE_INT) === false) $errors[] = 'Service must be a valid selection.';
    if ($availabilityId !== '' && filter_var($availabilityId, FILTER_VALIDATE_INT) === false) $errors[] = 'Schedule must be a valid selection.';

    return [
        'errors' => array_values($errors),
        'data' => compact('name', 'phone', 'vehicle', 'serviceId', 'availabilityId', 'date', 'time', 'message'),
    ];
}

function validateScheduleInput(array $post): array
{
    $date = trim($post['available_date'] ?? '');
    $time = trim($post['available_time'] ?? '');
    $errors = array_filter([
        validateRequired($date, 'Date'),
        $date !== '' ? validateDateFormat($date) : null,
        validateRequired($time, 'Time'),
        $time !== '' ? validateTimeFormat($time) : null,
    ]);
    return ['errors' => array_values($errors), 'data' => compact('date', 'time')];
}
