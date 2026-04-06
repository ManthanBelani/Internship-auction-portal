// Earnings management JavaScript
// API_BASE is now defined in main.js
let earningsChart;

// Get token from session
function getToken() {
    return document.querySelector('meta[name="api-token"]')?.content || '';
}

// Fetch with authentication
async function fetchAPI(endpoint) {
    const token = getToken();
    const baseUrl = API_BASE_URL ? `${API_BASE_URL}/api` : '/api';
    const response = await fetch(`${baseUrl}${endpoint}`, {
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

// Load earnings data
async function loadEarnings() {
    try {
        const period = document.getElementById('periodFilter').value;
        const data = await fetchAPI(`/admin/earnings?period=${period}`);

        // Update summary cards
        document.getElementById('total-earnings').textContent = formatCurrency(data.total || 0);
        document.getElementById('today-earnings').textContent = formatCurrency(data.today || 0);
        document.getElementById('week-earnings').textContent = formatCurrency(data.week || 0);
        document.getElementById('month-earnings').textContent = formatCurrency(data.month || 0);

        // Create earnings chart
        if (data.chartData) {
            createEarningsChart(data.chartData);
        }

        // Display recent transactions
        if (data.transactions) {
            displayEarningsTable(data.transactions);
        }
    } catch (error) {
        console.error('Failed to load earnings:', error);
        showToast('Failed to load earnings data: ' + error.message, 'error');

        // Show default values
        document.getElementById('total-earnings').textContent = '$0.00';
        document.getElementById('today-earnings').textContent = '$0.00';
        document.getElementById('week-earnings').textContent = '$0.00';
        document.getElementById('month-earnings').textContent = '$0.00';

        displayEarningsTable([]);
    }
}

// Create earnings chart
function createEarningsChart(chartData) {
    const ctx = document.getElementById('earningsChart').getContext('2d');

    if (earningsChart) {
        earningsChart.destroy();
    }

    earningsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels || [],
            datasets: [{
                label: 'Earnings ($)',
                data: chartData.values || [],
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });
}

// Display earnings table
function displayEarningsTable(transactions) {
    const tbody = document.getElementById('earningsTableBody');

    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="loading">No earnings data available</td></tr>';
        return;
    }

    tbody.innerHTML = transactions.map(transaction => `
        <tr>
            <td>${formatDate(transaction.created_at)}</td>
            <td>#${transaction.id}</td>
            <td>${escapeHtml(transaction.item_title || 'N/A')}</td>
            <td>${formatCurrency(transaction.amount)}</td>
            <td>${transaction.commission_rate}%</td>
            <td>${formatCurrency(transaction.commission)}</td>
        </tr>
    `).join('');
}

// Export earnings report
function exportEarnings() {
    showToast('Generating earnings report...', 'info');
    setTimeout(() => {
        showToast('Export functionality coming soon', 'info');
    }, 1000);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadEarnings();
});
