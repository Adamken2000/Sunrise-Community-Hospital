<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/db.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: appointments.php');
    exit;
}

$error = '';
$success = '';

if (empty($_SESSION['auth_csrf'])) {
    $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['auth_csrf'], $token)) {
        $error = 'Your session has expired. Please reload and try again.';
    } else {
        $action = $_POST['action'] ?? 'login';

        if ($action === 'login') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $stmt = $pdo->prepare(
                'SELECT id, full_name, username, password_hash, role
                 FROM staff_users
                 WHERE username = ? AND is_active = 1
                 LIMIT 1'
            );
            $stmt->execute([$username]);
            $staff = $stmt->fetch();

            if ($staff && password_verify($password, $staff['password_hash'])) {
                session_regenerate_id(true);

                $_SESSION['staff_id'] = (int) $staff['id'];
                $_SESSION['staff_name'] = $staff['full_name'];
                $_SESSION['staff_role'] = $staff['role'];

                unset($_SESSION['auth_csrf']);

                header('Location: appointments.php');
                exit;
            }

            $error = 'Invalid username or password.';
        } elseif ($action === 'register') {
            $fullName = trim((string) ($_POST['reg_full_name'] ?? ''));
            $role = trim((string) ($_POST['reg_role'] ?? ''));
            $username = trim((string) ($_POST['reg_username'] ?? ''));
            $password = (string) ($_POST['reg_password'] ?? '');

            $allowedRoles = ['doctor', 'nurse', 'admin'];

            if ($fullName === '' || $username === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
                $error = 'All fields are required and you must select a valid staff role.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                // Check if username already exists
                $checkStmt = $pdo->prepare('SELECT id FROM staff_users WHERE username = ? LIMIT 1');
                $checkStmt->execute([$username]);
                
                if ($checkStmt->fetch()) {
                    $error = 'The username is already taken. Please choose another.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $insStmt = $pdo->prepare(
                        'INSERT INTO staff_users (full_name, username, password_hash, role, is_active)
                         VALUES (?, ?, ?, ?, 1)'
                    );
                    
                    try {
                        $insStmt->execute([$fullName, $username, $hash, $role]);
                        $success = 'Account created successfully! You can now sign in below.';
                    } catch (PDOException $e) {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        }
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Portal | Sunrise Community Hospital</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .staff-login {
            max-width: 480px;
            margin: 50px auto;
            padding: 32px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,.08);
            color: #2c3e50;
        }
        .staff-login h1 { font-size: 2.6rem; margin-bottom: 6px; }
        .staff-login p { font-size: 1.4rem; margin-bottom: 20px; }
        .staff-login label {
            display: block;
            margin: 14px 0 5px;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .staff-login input, .staff-login select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd5dd;
            border-radius: 6px;
            font-size: 1.4rem;
        }
        .staff-login button[type="submit"] {
            margin-top: 18px;
            width: 100%;
            padding: 12px;
            border: 0;
            border-radius: 6px;
            background: var(--secondary-color);
            color: white;
            font-size: 1.4rem;
            cursor: pointer;
        }
        .login-error { color: #b42318; margin-bottom: 15px; font-size: 1.3rem; }
        .login-success { color: #236b2b; margin-bottom: 15px; font-size: 1.3rem; background: #e7f6e7; padding: 10px; border-radius: 6px; }
        
        .form-section { display: none; }
        .form-section.active { display: block; }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
            font-size: 1.3rem;
        }
        .back-home a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: bold;
        }
        .back-home a:hover {
            text-decoration: underline;
        }

        .create-account, .switch-to-login {
            background: none;
            border: none;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: bold;
            font-size: 1.3rem;
            cursor: pointer;
            margin-top: 15px;
            display: block;
            text-align: center;
            width: 100%;
        }
        .create-account:hover, .switch-to-login:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<main class="staff-login">
    <h1>Staff Portal</h1>
    <p>Sunrise Community Hospital Management System</p>

    <?php if ($error !== ''): ?>
        <p class="login-error" role="alert"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p class="login-success" role="status"><?= h($success) ?></p>
    <?php endif; ?>

    <!-- Sign In Form -->
    <form id="login-form" class="form-section active" method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= h($_SESSION['auth_csrf']) ?>">
        <input type="hidden" name="action" value="login">

        <label for="username">Username</label>
        <input id="username" name="username" autocomplete="username" maxlength="60" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button type="submit">Sign In</button>
        <button type="button" class="create-account" onclick="switchTab('register')">Create Account ?</button>
    </form>

    <!-- Register Form -->
    <form id="register-form" class="form-section" method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= h($_SESSION['auth_csrf']) ?>">
        <input type="hidden" name="action" value="register">

        <label for="reg_full_name">Full Name</label>
        <input id="reg_full_name" name="reg_full_name" maxlength="100" required>

        <label for="reg_role">Staff Role</label>
        <select id="reg_role" name="reg_role" required>
            <option value="">-- Select Role --</option>
            <option value="doctor">Doctor</option>
            <option value="nurse">Nurse</option>
            <option value="admin">Admin</option>
        </select>

        <label for="reg_username">Username</label>
        <input id="reg_username" name="reg_username" maxlength="60" required>

        <label for="reg_password">Password (min 6 characters)</label>
        <input id="reg_password" name="reg_password" type="password" minlength="6" required>

        <button type="submit">Register Account</button>
        <button type="button" class="switch-to-login" onclick="switchTab('login')">Already have an account? Sign In</button>
    </form>

    <div class="back-home">
        <a href="index.html">← Return to Homepage</a>
    </div>
</main>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.form-section').forEach(f => f.classList.remove('active'));
    
    if (tabName === 'login') {
        document.getElementById('login-form').classList.add('active');
    } else {
        document.getElementById('register-form').classList.add('active');
    }
}
</script>
</body>
</html>