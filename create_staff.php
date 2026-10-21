
<?php
// create_staff.php — run once, then delete this file.
require __DIR__ . '/db.php';

$fullName = 'Hospital Administrator';
$username = 'admin';
$password = 'ChangeThisPassword123!';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO staff_users (full_name, username, password_hash, role)
     VALUES (?, ?, ?, ?)'
);

try {
    $stmt->execute([$fullName, $username, $hash, 'admin']);
    echo 'Administrator created. Log in and change the temporary password.';
} catch (PDOException $e) {
    http_response_code(400);
    echo 'Account could not be created. The username may already exist.';
}
