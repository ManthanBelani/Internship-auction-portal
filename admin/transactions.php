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

// Check if user is admin or moderator
if ($_SESSION['admin_user']['role'] !== 'admin' && $_SESSION['admin_user']['role'] !== 'moderator') {
    header('Location: login.php?error=unauthorized');
    exit;
}

$pageTitle = 'Transaction Management';
$apiToken = $_SESSION['admin_user']['token'] ?? '';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Transaction Management</h1>
        <p>View and manage all platform transactions</p>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label>Status:</label>
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Date From:</label>
            <input type="date" id="dateFrom">
        </div>
        <div class="filter-group">
            <label>Date To:</label>
            <input type="date" id="dateTo">
        </div>
        <div class="filter-group">
            <label>Search:</label>
            <input type="text" id="searchInput" placeholder="Search by item or user...">
        </div>
        <button class="btn btn-primary" onclick="loadTransactions()">
            <i class="fas fa-search"></i> Filter
        </button>
        <button class="btn btn-success" onclick="exportTransactions()">
            <i class="fas fa-download"></i> Export
        </button>
    </div>

    <!-- Transactions Table -->
    <div class="table-container">
        <table id="transactionsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Amount</th>
                    <th>Commission</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="transactionsTableBody">
                <tr>
                    <td colspan="9" class="loading">Loading transactions...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/transactions.js"></script>

<?php include 'includes/footer.php'; ?>
