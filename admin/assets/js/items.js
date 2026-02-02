// Items management JavaScript

// Load items from API
async function loadItems() {
    try {
        const statusFilter = document.getElementById('statusFilter').value;
        const searchInput = document.getElementById('searchInput').value;
        
        let queryParams = [];
        if (statusFilter) queryParams.push(`status=${statusFilter}`);
        if (searchInput) queryParams.push(`search=${encodeURIComponent(searchInput)}`);
        
        const queryString = queryParams.length > 0 ? '?' + queryParams.join('&') : '';
        const data = await apiCall(`/items${queryString}`);
        
        displayItems(data.items);
    } catch (error) {
        console.error('Failed to load items:', error);
        showToast('Failed to load items: ' + error.message, 'error');
    }
}

// Display items in table
function displayItems(items) {
    const tbody = document.getElementById('itemsTableBody');
    
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="loading">No items found</td></tr>';
        return;
    }
    
    tbody.innerHTML = items.map(item => `
        <tr>
            <td>${item.id}</td>
            <td>${escapeHtml(item.title)}</td>
            <td>${escapeHtml(item.seller_name || 'N/A')}</td>
            <td>${formatCurrency(item.current_price)}</td>
            <td>${item.reserve_price ? formatCurrency(item.reserve_price) : 'None'}</td>
            <td><span class="badge badge-${getStatusBadge(item.status)}">${item.status}</span></td>
            <td>${formatDate(item.end_time)}</td>
            <td>
                <div class="action-buttons">
                    <a href="/api/items/${item.id}" target="_blank" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <button class="btn btn-sm btn-danger" onclick="deleteItem(${item.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Get status badge class
function getStatusBadge(status) {
    switch(status) {
        case 'active': return 'success';
        case 'sold': return 'info';
        case 'expired': return 'warning';
        default: return 'info';
    }
}

// Delete item
async function deleteItem(itemId) {
    if (!confirmAction('Are you sure you want to delete this item? This action cannot be undone.')) {
        return;
    }
    
    try {
        await apiCall(`/admin/items/${itemId}`, {
            method: 'DELETE'
        });
        
        showToast('Item deleted successfully', 'success');
        loadItems();
    } catch (error) {
        showToast('Failed to delete item: ' + error.message, 'error');
    }
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadItems();
    
    // Add enter key listener to search input
    document.getElementById('searchInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            loadItems();
        }
    });
});
