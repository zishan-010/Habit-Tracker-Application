<?php require_once __DIR__ . '/../config/csrf.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../resources/images/title_icon.png">
    <title> Log in — Habit Tracker </title>
    <link rel="stylesheet" href="../resources/css/loginpage.css">
</head>

<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="main_container">
        <div class="container">
            <h1><span>Habit</span> Tracker</h1>
            <p class="subtitle">Log in to keep your streak going</p>
        </div>
        <div class="card">

            <form action="login_process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="row-between">
                    <label class="remember">
                        <input type="checkbox" id="remember" name="remember">
                        Remember me
                    </label>
                    <a href="#" class="forgot">Forgot password?</a>
                </div>
                </a>
                <button type="submit" class="btn-login">Login</button>
            </form>

            <div class="divider">or continue with</div>
            <p class="signup-line">Don't have an account? <a href="../pages/signuppage.php">Sign up</a></p>
        </div>
    </div>
</body>

</html>