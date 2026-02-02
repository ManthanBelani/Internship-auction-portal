// Dashboard statistics loader
const API_BASE = '/api';
let usersChart, itemsChart;

// Get token from session
function getToken() {
    // Token is stored in PHP session, we'll pass it via a meta tag or data attribute
    return document.querySelector('meta[name="api-token"]')?.content || '';
}

// Fetch with authentication
async function fetchAPI(endpoint) {
    const token = getToken();
    const response = await fetch(`${API_BASE}${endpoint}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    
    if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
    }
    
    return response.json();
}

// Load statistics
async function loadStatistics() {
    try {
        const stats = await fetchAPI('/admin/stats');
        
        // Update stat cards
        document.getElementById('total-users').textContent = stats.users.total;
        document.getElementById('total-items').textContent = stats.items.total;
        document.getElementById('total-transactions').textContent = stats.transactions.total;
        document.getElementById('total-earnings').textContent = `$${parseFloat(stats.earnings.total).toFixed(2)}`;
        
        // Create charts
        createUsersChart(stats.users.byRole);
        createItemsChart(stats.items.byStatus);
        
    } catch (error) {
        console.error('Failed to load statistics:', error);
        showError('Failed to load dashboard statistics');
    }
}

// Create users by role chart
function createUsersChart(data) {
    const ctx = document.getElementById('usersChart').getContext('2d');
    
    if (usersChart) {
        usersChart.destroy();
    }
    
    const labels = data.map(item => item.role.charAt(0).toUpperCase() + item.role.slice(1));
    const counts = data.map(item => parseInt(item.count));
    
    usersChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: [
                    '#4CAF50',
                    '#2196F3',
                    '#FF9800',
                    '#9C27B0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Create items by status chart
function createItemsChart(data) {
    const ctx = document.getElementById('itemsChart').getContext('2d');
    
    if (itemsChart) {
        itemsChart.destroy();
    }
    
    const labels = data.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
    const counts = data.map(item => parseInt(item.count));
    
    itemsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Items',
                data: counts,
                backgroundColor: '#2196F3'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Load recent activity
async function loadRecentActivity() {
    try {
        // For now, show placeholder
        // In production, you'd fetch real activity data
        const activityList = document.getElementById('recent-activity');
        activityList.innerHTML = `
            <div class="activity-item">
                <div class="activity-icon" style="background: #4CAF50;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="activity-content">
                    <p>New user registered</p>
                    <small>2 minutes ago</small>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon" style="background: #2196F3;">
                    <i class="fas fa-gavel"></i>
                </div>
                <div class="activity-content">
                    <p>New item listed</p>
                    <small>15 minutes ago</small>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon" style="background: #FF9800;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="activity-content">
                    <p>Auction completed</p>
                    <small>1 hour ago</small>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Failed to load activity:', error);
    }
}

// Show error message
function showError(message) {
    const statsGrid = document.querySelector('.stats-grid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-error';
    errorDiv.style.gridColumn = '1 / -1';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    statsGrid.insertBefore(errorDiv, statsGrid.firstChild);
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    loadStatistics();
    loadRecentActivity();
    
    // Refresh stats every 30 seconds
    setInterval(loadStatistics, 30000);
});
