<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'hospital_management';

$conn = mysqli_connect($host,$user,$password,$database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// process.php
declare(strict_types=1);

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: appointment.html');
    exit;
}

require __DIR__ . '/db.php';

function postValue(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

$patientName = postValue('name');
$nationalId  = postValue('patient-id');
$gender      = strtolower(postValue('gender'));
$phone       = postValue('phone-number');
$email       = postValue('email');
$department  = postValue('department');
$dateInput   = postValue('date');

$errors = [];

// Validate patient name.
if ($patientName === '') {
    $errors[] = 'Patient name is required.';
} elseif (mb_strlen($patientName) > 120) {
    $errors[] = 'Patient name must not exceed 120 characters.';
} elseif (!preg_match("/^[\p{L}\p{M} .'-]+$/u", $patientName)) {
    $errors[] = 'Enter a valid patient name.';
}

// Validate national ID.
if (!preg_match('/^[A-Za-z0-9-]{8,14}$/', $nationalId)) {
    $errors[] = 'National ID must contain 8–14 letters or numbers.';
}

// Validate gender.
if (!in_array($gender, ['male', 'female'], true)) {
    $errors[] = 'Please select a valid gender.';
}

// Validate phone number.
// Accepts 10–12 digits, optionally separated by spaces,
// or a leading + followed by digits and spaces.
$phoneDigits = preg_replace('/\D/', '', $phone);

if (
    !preg_match('/^\+?[0-9 ]{10,16}$/', $phone) ||
    strlen($phoneDigits) < 10 ||
    strlen($phoneDigits) > 12
) {
    $errors[] = 'Enter a valid phone number containing 10–12 digits.';
}

// Validate email.
if (
    $email === '' ||
    strlen($email) > 254 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    $errors[] = 'Enter a valid email address.';
}

// Validate department against the form's permitted values.
$allowedDepartments = [
    'general',
    'pediatrics',
    'cardiology',
    'maternity',
    'neurology',
    'dental'
];

if (!in_array($department, $allowedDepartments, true)) {
    $errors[] = 'Please select a valid department.';
}

// Validate date and time from datetime-local.
$dateObject = DateTimeImmutable::createFromFormat(
    '!Y-m-d\TH:i',
    $dateInput
);

$dateFormatErrors = DateTimeImmutable::getLastErrors();

$dateIsValid = $dateObject !== false
    && (
        $dateFormatErrors === false ||
        (
            $dateFormatErrors['warning_count'] === 0 &&
            $dateFormatErrors['error_count'] === 0
        )
    )
    && $dateObject->format('Y-m-d\TH:i') === $dateInput;

if (!$dateIsValid) {
    $errors[] = 'Please select a valid appointment date and time.';
} else {
    $now = new DateTimeImmutable('now');

    if ($dateObject <= $now) {
        $errors[] = 'Appointment date and time must be in the future.';
    }
}

// If validation fails, display errors safely.
if ($errors) {
    http_response_code(422);
    $pageTitle = 'Appointment Not Submitted';
} else {
    try {
        $sql = "INSERT INTO appointments
                (patient_name, national_id, gender, phone_number,
                 email_address, department, appointment_date)
                VALUES
                (:patient_name, :national_id, :gender, :phone_number,
                 :email_address, :department, :appointment_date)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':patient_name'    => $patientName,
            ':national_id'     => $nationalId,
            ':gender'           => $gender,
            ':phone_number'     => $phone,
            ':email_address'    => $email,
            ':department'       => $department,
            ':appointment_date' => $dateObject->format('Y-m-d H:i:s')
        ]);

        $bookingId = (int) $pdo->lastInsertId();
        $pageTitle = 'Appointment Booked Successfully';

    } catch (PDOException $e) {
        error_log('Appointment insert failed: ' . $e->getMessage());
        http_response_code(500);
        $errors[] = 'We could not save your appointment. Please try again later.';
        $pageTitle = 'Booking Error';
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Friendly department names for the confirmation.
$departmentNames = [
    'general'    => 'General Medicine',
    'pediatrics' => 'Pediatrics',
    'cardiology' => 'Cardiology',
    'maternity'  => 'Obstetrics & Gynecology',
    'neurology'  => 'Neurology',
    'dental'     => 'Dental Care'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeHtml($pageTitle) ?> | Sunrise Community Hospital</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .booking-result {
            max-width: 720px;
            margin: 60px auto;
            padding: 32px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 5px 24px rgba(0,0,0,.08);
            color: #2c3e50;
        }
        .booking-result h1 {
            font-size: 2.8rem;
            margin-bottom: 18px;
        }
        .booking-result p,
        .booking-result li {
            font-size: 1.6rem;
            line-height: 1.8;
        }
        .booking-success { border-top: 5px solid #66A515; }
        .booking-error { border-top: 5px solid #d9534f; }
        .booking-result a {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 20px;
            background: #1790db;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<main class="booking-result <?= empty($errors) ? 'booking-success' : 'booking-error' ?>">
    <?php if (empty($errors)): ?>

        <h1>Appointment Booked Successfully!</h1>
        <p>
            Thank you, <strong><?= escapeHtml($patientName) ?></strong>.
            Your appointment details have been saved.
        </p>

        <p><strong>Booking reference:</strong> <?= $bookingId ?></p>
        <p><strong>Department:</strong>
            <?= escapeHtml($departmentNames[$department]) ?>
        </p>
        <p><strong>Date and time:</strong>
            <?= escapeHtml($dateObject->format('d M Y, H:i')) ?>
        </p>

        <p>Please contact the hospital if you need to confirm or change your booking.</p>

    <?php else: ?>

        <h1>Appointment Not Submitted</h1>
        <p>Please correct the following issues:</p>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= escapeHtml($error) ?></li>
            <?php endforeach; ?>
        </ul>

    <?php endif; ?>

    <a href="appointment.html">Return to Appointment Form</a>
</main>

</body>
</html>


?>