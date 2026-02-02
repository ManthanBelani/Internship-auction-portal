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

$pageTitle = 'User Management';
$apiToken = $_SESSION['admin_user']['token'] ?? '';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>User Management</h1>
        <p>Manage users, roles, and permissions</p>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <div class="filter-group">
            <label>Role:</label>
            <select id="roleFilter">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="moderator">Moderator</option>
                <option value="seller">Seller</option>
                <option value="buyer">Buyer</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Status:</label>
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="banned">Banned</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Search:</label>
            <input type="text" id="searchInput" placeholder="Search by name or email...">
        </div>
        <button class="btn btn-primary" onclick="loadUsers()">
            <i class="fas fa-search"></i> Filter
        </button>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="7" class="loading">Loading users...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- User Action Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">User Actions</h2>
        <div id="modalBody"></div>
    </div>
</div>

<script src="assets/js/users.js"></script>

<?php include 'includes/footer.php'; ?>
