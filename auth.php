<?php
// auth.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['staff_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(array $allowedRoles): void
{
    requireLogin();

    if (!in_array($_SESSION['staff_role'] ?? '', $allowedRoles, true)) {
        http_response_code(403);
        exit('Access denied. You do not have permission to perform this action.');
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submitted) ||
        !is_string($stored) ||
        $stored === '' ||
        !hash_equals($stored, $submitted)
    ) {
        http_response_code(403);
        exit('Invalid request token. Please return to the page and try again.');
    }
}
