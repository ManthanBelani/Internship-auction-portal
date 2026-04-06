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

// Check if user is admin (only admins can access settings)
if ($_SESSION['admin_user']['role'] !== 'admin') {
    header('Location: index.php?error=unauthorized');
    exit;
}

$pageTitle = 'Platform Settings';
$apiToken = $_SESSION['admin_user']['token'] ?? '';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Platform Settings</h1>
        <p>Configure platform settings and preferences</p>
    </div>

    <!-- Settings Sections -->
    <div class="settings-container">
        <!-- General Settings -->
        <div class="settings-card">
            <h3><i class="fas fa-cog"></i> General Settings</h3>
            <form id="generalSettingsForm" class="settings-form">
                <div class="form-group">
                    <label>Platform Name</label>
                    <input type="text" id="platformName" value="Auction Portal" required>
                </div>
                <div class="form-group">
                    <label>Support Email</label>
                    <input type="email" id="supportEmail" value="support@auction.com" required>
                </div>
                <div class="form-group">
                    <label>Platform Status</label>
                    <select id="platformStatus">
                        <option value="active">Active</option>
                        <option value="maintenance">Maintenance Mode</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Commission Settings -->
        <div class="settings-card">
            <h3><i class="fas fa-percent"></i> Commission Settings</h3>
            <form id="commissionSettingsForm" class="settings-form">
                <div class="form-group">
                    <label>Default Commission Rate (%)</label>
                    <input type="number" id="commissionRate" value="5" min="0" max="100" step="0.1" required>
                    <small>Platform commission on each successful auction</small>
                </div>
                <div class="form-group">
                    <label>Minimum Commission ($)</label>
                    <input type="number" id="minCommission" value="1" min="0" step="0.01" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Auction Settings -->
        <div class="settings-card">
            <h3><i class="fas fa-gavel"></i> Auction Settings</h3>
            <form id="auctionSettingsForm" class="settings-form">
                <div class="form-group">
                    <label>Minimum Auction Duration (hours)</label>
                    <input type="number" id="minDuration" value="24" min="1" required>
                </div>
                <div class="form-group">
                    <label>Maximum Auction Duration (days)</label>
                    <input type="number" id="maxDuration" value="30" min="1" required>
                </div>
                <div class="form-group">
                    <label>Minimum Bid Increment ($)</label>
                    <input type="number" id="minBidIncrement" value="1" min="0.01" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Auto-extend Time (minutes)</label>
                    <input type="number" id="autoExtend" value="5" min="0" required>
                    <small>Extend auction if bid placed in last X minutes</small>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Email Settings -->
        <div class="settings-card">
            <h3><i class="fas fa-envelope"></i> Email Settings</h3>
            <form id="emailSettingsForm" class="settings-form">
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" id="smtpHost" placeholder="smtp.example.com">
                </div>
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" id="smtpPort" value="587">
                </div>
                <div class="form-group">
                    <label>SMTP Username</label>
                    <input type="text" id="smtpUsername">
                </div>
                <div class="form-group">
                    <label>SMTP Password</label>
                    <input type="password" id="smtpPassword">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="emailNotifications" checked>
                        Enable Email Notifications
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Security Settings -->
        <div class="settings-card">
            <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
            <form id="securitySettingsForm" class="settings-form">
                <div class="form-group">
                    <label>Session Timeout (minutes)</label>
                    <input type="number" id="sessionTimeout" value="60" min="5" required>
                </div>
                <div class="form-group">
                    <label>Max Login Attempts</label>
                    <input type="number" id="maxLoginAttempts" value="5" min="1" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="twoFactorAuth">
                        Enable Two-Factor Authentication
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="requireEmailVerification" checked>
                        Require Email Verification
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Maintenance -->
        <div class="settings-card">
            <h3><i class="fas fa-tools"></i> Maintenance</h3>
            <div class="maintenance-actions">
                <button class="btn btn-warning" onclick="clearCache()">
                    <i class="fas fa-broom"></i> Clear Cache
                </button>
                <button class="btn btn-info" onclick="runDatabaseOptimization()">
                    <i class="fas fa-database"></i> Optimize Database
                </button>
                <button class="btn btn-success" onclick="exportBackup()">
                    <i class="fas fa-download"></i> Export Backup
                </button>
                <button class="btn btn-danger" onclick="viewLogs()">
                    <i class="fas fa-file-alt"></i> View System Logs
                </button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/settings.js"></script>

<?php include 'includes/footer.php'; ?>
