// Users management JavaScript

// Load users from API
async function loadUsers() {
    try {
        const roleFilter = document.getElementById('roleFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        const searchInput = document.getElementById('searchInput').value;
        
        let queryParams = [];
        if (roleFilter) queryParams.push(`role=${roleFilter}`);
        if (statusFilter) queryParams.push(`status=${statusFilter}`);
        if (searchInput) queryParams.push(`search=${encodeURIComponent(searchInput)}`);
        
        const queryString = queryParams.length > 0 ? '?' + queryParams.join('&') : '';
        const data = await apiCall(`/admin/users${queryString}`);
        
        displayUsers(data.users);
    } catch (error) {
        console.error('Failed to load users:', error);
        showToast('Failed to load users: ' + error.message, 'error');
    }
}

// Display users in table
function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="loading">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>${escapeHtml(user.name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td><span class="badge badge-info">${user.role}</span></td>
            <td><span class="badge badge-${getStatusBadge(user.status)}">${user.status}</span></td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <div class="action-buttons">
                    ${getActionButtons(user)}
                </div>
            </td>
        </tr>
    `).join('');
}

// Get status badge class
function getStatusBadge(status) {
    switch(status) {
        case 'active': return 'success';
        case 'suspended': return 'warning';
        case 'banned': return 'danger';
        default: return 'info';
    }
}

// Get action buttons based on user status
function getActionButtons(user) {
    const isAdmin = document.querySelector('.user-role').textContent.toLowerCase() === 'admin';
    let buttons = [];
    
    if (isAdmin) {
        buttons.push(`<button class="btn btn-sm btn-primary" onclick="changeRole(${user.id}, '${user.role}')">
            <i class="fas fa-user-tag"></i> Role
        </button>`);
    }
    
    if (user.status === 'active') {
        buttons.push(`<button class="btn btn-sm btn-warning" onclick="suspendUser(${user.id})">
            <i class="fas fa-pause"></i> Suspend
        </button>`);
        
        if (isAdmin) {
            buttons.push(`<button class="btn btn-sm btn-danger" onclick="banUser(${user.id})">
                <i class="fas fa-ban"></i> Ban
            </button>`);
        }
    } else {
        buttons.push(`<button class="btn btn-sm btn-success" onclick="reactivateUser(${user.id})">
            <i class="fas fa-check"></i> Reactivate
        </button>`);
    }
    
    return buttons.join(' ');
}

// Change user role
async function changeRole(userId, currentRole) {
    const roles = ['buyer', 'seller', 'moderator', 'admin'];
    const role = prompt(`Enter new role (${roles.join(', ')}):`, currentRole);
    
    if (!role || !roles.includes(role)) {
        return;
    }
    
    try {
        await apiCall(`/admin/users/${userId}/role`, {
            method: 'PUT',
            body: JSON.stringify({ role })
        });
        
        showToast('User role updated successfully', 'success');
        loadUsers();
    } catch (error) {
        showToast('Failed to update role: ' + error.message, 'error');
    }
}

// Suspend user
async function suspendUser(userId) {
    const until = prompt('Suspend until (YYYY-MM-DD HH:MM:SS) or leave empty for indefinite:');
    
    if (until === null) return;
    
    try {
        await apiCall(`/admin/users/${userId}/suspend`, {
            method: 'POST',
            body: JSON.stringify({ until: until || null })
        });
        
        showToast('User suspended successfully', 'success');
        loadUsers();
    } catch (error) {
        showToast('Failed to suspend user: ' + error.message, 'error');
    }
}

// Ban user
async function banUser(userId) {
    if (!confirmAction('Are you sure you want to ban this user? This action is permanent.')) {
        return;
    }
    
    try {
        await apiCall(`/admin/users/${userId}/ban`, {
            method: 'POST'
        });
        
        showToast('User banned successfully', 'success');
        loadUsers();
    } catch (error) {
        showToast('Failed to ban user: ' + error.message, 'error');
    }
}

// Reactivate user
async function reactivateUser(userId) {
    try {
        await apiCall(`/admin/users/${userId}/reactivate`, {
            method: 'POST'
        });
        
        showToast('User reactivated successfully', 'success');
        loadUsers();
    } catch (error) {
        showToast('Failed to reactivate user: ' + error.message, 'error');
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
    loadUsers();
    
    // Add enter key listener to search input
    document.getElementById('searchInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            loadUsers();
        }
    });
});
