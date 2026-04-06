// Transactions management JavaScript

// Load transactions from API
async function loadTransactions() {
    try {
        const statusFilter = document.getElementById('statusFilter').value;
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const searchInput = document.getElementById('searchInput').value;
        
        let queryParams = [];
        if (statusFilter) queryParams.push(`status=${statusFilter}`);
        if (dateFrom) queryParams.push(`date_from=${dateFrom}`);
        if (dateTo) queryParams.push(`date_to=${dateTo}`);
        if (searchInput) queryParams.push(`search=${encodeURIComponent(searchInput)}`);
        
        const queryString = queryParams.length > 0 ? '?' + queryParams.join('&') : '';
        const data = await apiCall(`/admin/transactions${queryString}`);
        
        displayTransactions(data.transactions || []);
    } catch (error) {
        console.error('Failed to load transactions:', error);
        showToast('Failed to load transactions: ' + error.message, 'error');
        displayTransactions([]);
    }
}

// Display transactions in table
function displayTransactions(transactions) {
    const tbody = document.getElementById('transactionsTableBody');
    
    if (transactions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="loading">No transactions found</td></tr>';
        return;
    }
    
    tbody.innerHTML = transactions.map(transaction => `
        <tr>
            <td>${transaction.id}</td>
            <td>${escapeHtml(transaction.item_title || 'N/A')}</td>
            <td>${escapeHtml(transaction.buyer_name || 'N/A')}</td>
            <td>${escapeHtml(transaction.seller_name || 'N/A')}</td>
            <td>${formatCurrency(transaction.amount)}</td>
            <td>${formatCurrency(transaction.commission)}</td>
            <td><span class="badge badge-${getStatusBadge(transaction.status)}">${transaction.status}</span></td>
            <td>${formatDate(transaction.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-primary" onclick="viewTransaction(${transaction.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Get status badge class
function getStatusBadge(status) {
    switch(status) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'failed': return 'danger';
        default: return 'info';
    }
}

// View transaction details
function viewTransaction(transactionId) {
    showToast('Transaction details view coming soon', 'info');
}

// Export transactions
function exportTransactions() {
    showToast('Exporting transactions...', 'info');
    // In production, this would generate a CSV/Excel file
    setTimeout(() => {
        showToast('Export functionality coming soon', 'info');
    }, 1000);
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadTransactions();
    
    // Add enter key listener to search input
    document.getElementById('searchInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            loadTransactions();
        }
    });
});
