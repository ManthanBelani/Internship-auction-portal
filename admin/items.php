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

$pageTitle = 'Item Management';
$apiToken = $_SESSION['admin_user']['token'] ?? '';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Item Management</h1>
        <p>Manage auction items and listings</p>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label>Status:</label>
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="sold">Sold</option>
                <option value="expired">Expired</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Search:</label>
            <input type="text" id="searchInput" placeholder="Search by title...">
        </div>
        <button class="btn btn-primary" onclick="loadItems()">
            <i class="fas fa-search"></i> Filter
        </button>
    </div>

    <!-- Items Table -->
    <div class="table-container">
        <table id="itemsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Seller</th>
                    <th>Current Price</th>
                    <th>Reserve Price</th>
                    <th>Status</th>
                    <th>End Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="itemsTableBody">
                <tr>
                    <td colspan="8" class="loading">Loading items...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/items.js"></script>

<?php include 'includes/footer.php'; ?>
