<?php
// TASK 4: PHP BACK-END PROCESSING

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Helper function to sanitize input data
    function sanitize_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    // 1. Receive and sanitize form inputs
    $patientName    = sanitize_input($_POST['patientName'] ?? '');
    $nationalID     = sanitize_input($_POST['nationalID'] ?? '');
    $gender         = sanitize_input($_POST['gender'] ?? '');
    $phone          = sanitize_input($_POST['phone'] ?? '');
    $email          = sanitize_input($_POST['email'] ?? '');
    $department     = sanitize_input($_POST['department'] ?? '');
    $appointmentDate= sanitize_input($_POST['appointmentDate'] ?? '');

    // 2. Validate submitted information (Server-side validation)
    if (empty($patientName)) {
        $errors[] = "Patient Name is required.";
    }

    if (empty($nationalID)) {
        $errors[] = "National ID Number is required.";
    }

    if (empty($gender)) {
        $errors[] = "Gender is required.";
    }

    if (empty($phone)) {
        $errors[] = "Phone Number is required.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = "Invalid phone number format.";
    }

    if (empty($email)) {
        $errors[] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address format.";
    }

    if (empty($department)) {
        $errors[] = "Department selection is required.";
    }

    $currentDate = date('Y-m-d');
    if (empty($appointmentDate)) {
        $errors[] = "Appointment Date is required.";
    } elseif ($appointmentDate < $currentDate) {
        $errors[] = "Appointment Date cannot be in the past.";
    }

    // 3. Process form submissions and display message
    if (empty($errors)) {
        // Success Message output
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Booking Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; }
                .success-card { background: #fff; max-width: 500px; margin: 0 auto; padding: 25px; border-radius: 8px; border-left: 6px solid #28a745; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                h2 { color: #28a745; }
                p { font-size: 16px; line-height: 1.5; color: #333; }
                .btn { display: inline-block; margin-top: 15px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='success-card'>
                <h2>Appointment Booked Successfully!</h2>
                <p><strong>Patient Name:</strong> {$patientName}</p>
                <p><strong>National ID:</strong> {$nationalID}</p>
                <p><strong>Gender:</strong> {$gender}</p>
                <p><strong>Phone:</strong> {$phone}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Department:</strong> {$department}</p>
                <p><strong>Appointment Date:</strong> {$appointmentDate}</p>
                <a href='index.php' class='btn'>Book Another Appointment</a>
            </div>
        </body>
        </html>";
    } else {
        // Display validation errors if any back-end checks fail
        echo "<div style='color: red; padding: 20px; border: 1px solid red; max-width: 500px; margin: 20px auto;'>";
        echo "<h3>Submission Errors:</h3><ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul><a href='javascript:history.back()'>Go Back and Fix</a></div>";
    }
} else {
    // Redirect if accessed directly without POST
    header("Location: index.php");
    exit();
}
?>