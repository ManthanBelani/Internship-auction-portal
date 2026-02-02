<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="api-token" content="<?php echo htmlspecialchars($apiToken ?? ''); ?>">
    <title><?php echo $pageTitle ?? 'Admin'; ?> - Auction Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <nav class="top-navbar">
            <div class="navbar-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Auction Portal Admin</h2>
            </div>
            <div class="navbar-right">
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_user']['name']); ?></span>
                    <span class="user-role"><?php echo ucfirst($_SESSION['admin_user']['role']); ?></span>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </nav>
