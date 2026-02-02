<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Check if user is logged in
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

// Check if user is admin
if ($_SESSION['admin_user']['role'] !== 'admin' && $_SESSION['admin_user']['role'] !== 'moderator') {
    header('Location: login.php?error=unauthorized');
    exit;
}

$pageTitle = 'Dashboard';
$apiToken = $_SESSION['admin_user']['token'] ?? '';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_user']['name']); ?>!</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3 id="total-users">Loading...</h3>
                <p>Total Users</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-gavel"></i>
            </div>
            <div class="stat-content">
                <h3 id="total-items">Loading...</h3>
                <p>Total Items</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <h3 id="total-transactions">Loading...</h3>
                <p>Transactions</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3 id="total-earnings">Loading...</h3>
                <p>Platform Earnings</p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-card">
            <h3>Users by Role</h3>
            <canvas id="usersChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>Items by Status</h3>
            <canvas id="itemsChart"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="activity-section">
        <h3>Recent Activity</h3>
        <div class="activity-list" id="recent-activity">
            <p class="loading">Loading recent activity...</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/dashboard.js"></script>

<?php include 'includes/footer.php'; ?>
