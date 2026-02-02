<?php
session_start();

// Handle session creation from AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_session') {
    $userData = json_decode($_POST['user_data'], true);
    if ($userData) {
        $_SESSION['admin_user'] = $userData;
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user data']);
    exit;
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_user'])) {
    header('Location: index.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Auction Portal</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-gavel"></i>
                <h1>Auction Portal</h1>
                <p>Admin Dashboard</p>
            </div>

            <?php if ($error === 'unauthorized'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    You don't have permission to access the admin panel.
                </div>
            <?php endif; ?>

            <?php if ($error === 'invalid'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    Invalid email or password.
                </div>
            <?php endif; ?>

            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="admin@auction.com">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>

            <div class="login-footer">
                <p>Default credentials: admin@auction.com / admin123</p>
                <p class="warning">⚠️ Change default password after first login!</p>
            </div>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>
