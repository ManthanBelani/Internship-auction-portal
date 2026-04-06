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

// Check if user is admin (only admins can view earnings)
if ($_SESSION['admin_user']['role'] !== 'admin') {
    header('Location: index.php?error=unauthorized');
    exit;
}

$pageTitle = 'Platform Earnings';
$apiToken = $_SESSION['admin_user']['token'] ?? '';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Platform Earnings</h1>
        <p>View detailed earnings and commission reports</p>
    </div>

    <!-- Earnings Summary -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3 id="total-earnings">$0.00</h3>
                <p>Total Earnings</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3 id="today-earnings">$0.00</h3>
                <p>Today's Earnings</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <h3 id="week-earnings">$0.00</h3>
                <p>This Week</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3 id="month-earnings">$0.00</h3>
                <p>This Month</p>
            </div>
        </div>
    </div>

    <!-- Earnings Chart -->
    <div class="chart-card" style="margin-bottom: 30px;">
        <h3>Earnings Over Time</h3>
        <canvas id="earningsChart"></canvas>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label>Period:</label>
            <select id="periodFilter">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last Year</option>
            </select>
        </div>
        <button class="btn btn-primary" onclick="loadEarnings()">
            <i class="fas fa-sync"></i> Refresh
        </button>
        <button class="btn btn-success" onclick="exportEarnings()">
            <i class="fas fa-download"></i> Export Report
        </button>
    </div>

    <!-- Earnings Details Table -->
    <div class="table-container">
        <h3 style="margin-bottom: 15px;">Recent Transactions</h3>
        <table id="earningsTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Transaction ID</th>
                    <th>Item</th>
                    <th>Sale Amount</th>
                    <th>Commission Rate</th>
                    <th>Commission Earned</th>
                </tr>
            </thead>
            <tbody id="earningsTableBody">
                <tr>
                    <td colspan="6" class="loading">Loading earnings data...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/earnings.js"></script>

<?php include 'includes/footer.php'; ?>
