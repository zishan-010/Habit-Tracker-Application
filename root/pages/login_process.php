<?php
/**
 * login_process.php
 * Handles POST from loginpage.html
 *
 * Security features:
 *  - CSRF token check
 *  - Prepared statements (no string-concatenated SQL, ever)
 *  - password_verify() against the stored hash (constant-time comparison internally)
 *  - Same generic error for "no such user" and "wrong password" (prevents
 *    attackers enumerating valid emails)
 *  - Login attempt throttling per email to slow brute force
 *  - session_regenerate_id() on successful login (prevents session fixation)
 */

declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
// ini_set('session.cookie_secure', '1'); // enable once served over HTTPS

session_start();
require_once __DIR__ . '/../config/Database.php';

header("Location: ../pages/mainpage.html");
// header('Content-Type: application/json');
function fail(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Invalid request method.', 405);
}

// --- CSRF check ---
$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    fail('Invalid or expired form submission. Please refresh and try again.', 419);
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$email = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($email === false || $password === '') {
    fail('Please enter a valid email and password.');
}

// --- Per-email throttling to slow brute-force / credential stuffing ---
$attemptsKey = 'login_attempts_' . md5($email);
$_SESSION[$attemptsKey] = $_SESSION[$attemptsKey] ?? ['count' => 0, 'first' => time()];

if ($_SESSION[$attemptsKey]['count'] >= 5 && (time() - $_SESSION[$attemptsKey]['first']) < 300) {
    fail('Too many login attempts. Please wait a few minutes and try again.', 429);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION[$attemptsKey]['count']++;
        // Generic message regardless of which check failed
        fail('Invalid email or password.', 401);
    }

    // Optional: transparently re-hash if the algorithm/cost has changed since the hash was created
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
            ->execute(['hash' => $newHash, 'id' => $user['id']]);
    }

    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION[$attemptsKey]);

    echo json_encode(['success' => true, 'message' => 'Logged in successfully.']);
} catch (RuntimeException $e) {
    fail($e->getMessage(), 500);
} catch (PDOException $e) {
    error_log('Login DB error: ' . $e->getMessage());
    fail('Something went wrong. Please try again later.', 500);
}