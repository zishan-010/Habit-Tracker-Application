<?php require_once __DIR__ . '/../config/csrf.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../resources/images/title_icon.png">
    <title> Sign up — Habit Tracker </title>
    <link rel="stylesheet" href="../resources/css/signuppage.css">
</head>

<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="main_container">
        <div class="container">

            <h1><span>Habit</span> Tracker</h1>
            <p class="subtitle">Create an account to start tracking</p>
        </div>
        <div class="card">
            <form action="signup_process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="field">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" placeholder="Your name" required>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <span class="hint">At least 8 characters</span>
                </div>

                <div class="field">
                    <label for="confirm-password">Confirm password</label>
                    <input type="password" id="confirm-password" name="confirm-password"
                        placeholder="Re-enter your password" required>
                </div>

                <label class="terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    I agree to the <a href="termspage.html">Terms of Service</a> and <a href="privacypage.html">Privacy
                        Policy</a>
                </label>

                <button type="submit" class="btn-signup">Create account</button>
            </form>

            <div class="divider">or continue with</div>
            <p class="login-line">Already have an account? <a href="../pages/loginpage.html">Log in</a></p>
        </div>
    </div>

</body>

</html>