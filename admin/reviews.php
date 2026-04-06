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

$pageTitle = 'Review Management';
$apiToken = $_SESSION['admin_user']['token'] ?? '';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Review Management</h1>
        <p>Moderate user reviews and ratings</p>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label>Rating:</label>
            <select id="ratingFilter">
                <option value="">All Ratings</option>
                <option value="5">5 Stars</option>
                <option value="4">4 Stars</option>
                <option value="3">3 Stars</option>
                <option value="2">2 Stars</option>
                <option value="1">1 Star</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Type:</label>
            <select id="typeFilter">
                <option value="">All Types</option>
                <option value="seller">Seller Reviews</option>
                <option value="buyer">Buyer Reviews</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Search:</label>
            <input type="text" id="searchInput" placeholder="Search reviews...">
        </div>
        <button class="btn btn-primary" onclick="loadReviews()">
            <i class="fas fa-search"></i> Filter
        </button>
    </div>

    <!-- Reviews Table -->
    <div class="table-container">
        <table id="reviewsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reviewer</th>
                    <th>Reviewed User</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="reviewsTableBody">
                <tr>
                    <td colspan="7" class="loading">Loading reviews...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/reviews.js"></script>

<?php include 'includes/footer.php'; ?>
