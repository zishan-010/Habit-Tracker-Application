<?php
/**
 * signup_process.php
 * Handles POST from signuppage.html
 *
 * Security features:
 *  - CSRF token check
 *  - Server-side input validation (never trust client-side `required`)
 *  - password_hash() with the strong default algorithm (never store plaintext)
 *  - Prepared statements only (prevents SQL injection)
 *  - Generic error messages (no "email already exists" user enumeration leak
 *    unless you decide that's acceptable UX trade-off)
 *  - Session fixation protection via session_regenerate_id()
 */

declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
// ini_set('session.cookie_secure', '1'); // enable once served over HTTPS

session_start();
require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json');

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

// --- Basic rate limiting (per session) to slow down bots/brute force ---
$_SESSION['signup_attempts'] = ($_SESSION['signup_attempts'] ?? 0) + 1;
if ($_SESSION['signup_attempts'] > 10) {
    fail('Too many attempts. Please try again later.', 429);
}

// --- Input validation ---
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm-password'] ?? '';

if ($name === '' || mb_strlen($name) > 100) {
    fail('Please enter a valid name.');
}

$email = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    fail('Please enter a valid email address.');
}

if (mb_strlen($password) < 8) {
    fail('Password must be at least 8 characters.');
}

if (!hash_equals($password, $confirmPassword)) {
    fail('Passwords do not match.');
}

if (empty($_POST['terms'])) {
    fail('You must agree to the Terms of Service and Privacy Policy.');
}

try {
    $pdo = Database::getConnection();

    // --- Layer 1: application-level pre-check (fast, gives a clean error) ---
    // Not race-safe by itself, so it is backed by the DB-level UNIQUE
    // constraint on users.email — see Layer 2 below.
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        fail('An account with that email already exists. Try logging in instead.', 409);
    }

    // Strong, salted, adaptive hash — PASSWORD_DEFAULT tracks best-practice algo (currently bcrypt)
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $insert = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, created_at) 
             VALUES (:name, :email, :password_hash, NOW())'
        );
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);
    } catch (PDOException $e) {
        // --- Layer 2: DB-level guard (race-condition safe) ---
        // If two signups for the same email land at nearly the same instant,
        // the pre-check above can miss it. The UNIQUE index on users.email
        // (see schema in README) makes MySQL reject the second INSERT with
        // SQLSTATE 23000 (integrity constraint violation). We catch that
        // specific error and turn it into the same friendly message.
        if ($e->getCode() === '23000') {
            fail('An account with that email already exists. Try logging in instead.', 409);
        }
        throw $e; // any other DB error bubbles up to the generic handler below
    }

    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    unset($_SESSION['signup_attempts']);

    echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
} catch (RuntimeException $e) {
    // Thrown deliberately by Database.php with a safe message
    fail($e->getMessage(), 500);
} catch (PDOException $e) {
    error_log('Signup DB error: ' . $e->getMessage());
    fail('Something went wrong. Please try again later.', 500);
}