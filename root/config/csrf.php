<?php
/**
 * csrf.php
 * Include at the top of any page that renders a form which posts
 * to login_process.php / signup_process.php.
 *
 * Usage in an HTML page (e.g. loginpage.html renamed to loginpage.php):
 *
 *   <?php require_once __DIR__ . '/../config/csrf.php'; ?>
 *   <form action="login_process.php" method="POST">
 *       <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
 *       ...
 *   </form>
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Escape output to prevent stored/reflected XSS when echoing user data back into HTML */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}