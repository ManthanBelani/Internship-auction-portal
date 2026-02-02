        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-gavel"></i>
                <span>Admin Panel</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <a href="index.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <a href="users.php">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'items.php' ? 'active' : ''; ?>">
                    <a href="items.php">
                        <i class="fas fa-gavel"></i>
                        <span>Auction Items</span>
                    </a>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                    <a href="transactions.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Transactions</span>
                    </a>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                    <a href="reviews.php">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </li>
                
                <?php if ($_SESSION['admin_user']['role'] === 'admin'): ?>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'earnings.php' ? 'active' : ''; ?>">
                    <a href="earnings.php">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Earnings</span>
                    </a>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </aside>
