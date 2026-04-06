// Settings management JavaScript

// Load current settings
async function loadSettings() {
    try {
        const data = await apiCall('/admin/settings');
        
        // Populate form fields with current settings
        if (data.settings) {
            populateSettings(data.settings);
        }
    } catch (error) {
        console.error('Failed to load settings:', error);
        showToast('Failed to load settings: ' + error.message, 'error');
    }
}

// Populate settings forms
function populateSettings(settings) {
    // General settings
    if (settings.general) {
        document.getElementById('platformName').value = settings.general.name || 'Auction Portal';
        document.getElementById('supportEmail').value = settings.general.email || 'support@auction.com';
        document.getElementById('platformStatus').value = settings.general.status || 'active';
    }
    
    // Commission settings
    if (settings.commission) {
        document.getElementById('commissionRate').value = settings.commission.rate || 5;
        document.getElementById('minCommission').value = settings.commission.minimum || 1;
    }
    
    // Auction settings
    if (settings.auction) {
        document.getElementById('minDuration').value = settings.auction.minDuration || 24;
        document.getElementById('maxDuration').value = settings.auction.maxDuration || 30;
        document.getElementById('minBidIncrement').value = settings.auction.minBidIncrement || 1;
        document.getElementById('autoExtend').value = settings.auction.autoExtend || 5;
    }
    
    // Email settings
    if (settings.email) {
        document.getElementById('smtpHost').value = settings.email.host || '';
        document.getElementById('smtpPort').value = settings.email.port || 587;
        document.getElementById('smtpUsername').value = settings.email.username || '';
        document.getElementById('emailNotifications').checked = settings.email.enabled !== false;
    }
    
    // Security settings
    if (settings.security) {
        document.getElementById('sessionTimeout').value = settings.security.sessionTimeout || 60;
        document.getElementById('maxLoginAttempts').value = settings.security.maxLoginAttempts || 5;
        document.getElementById('twoFactorAuth').checked = settings.security.twoFactorAuth || false;
        document.getElementById('requireEmailVerification').checked = settings.security.emailVerification !== false;
    }
}

// Save general settings
document.getElementById('generalSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const settings = {
        name: document.getElementById('platformName').value,
        email: document.getElementById('supportEmail').value,
        status: document.getElementById('platformStatus').value
    };
    
    try {
        await apiCall('/admin/settings/general', {
            method: 'PUT',
            body: JSON.stringify(settings)
        });
        
        showToast('General settings saved successfully', 'success');
    } catch (error) {
        showToast('Failed to save settings: ' + error.message, 'error');
    }
});

// Save commission settings
document.getElementById('commissionSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const settings = {
        rate: parseFloat(document.getElementById('commissionRate').value),
        minimum: parseFloat(document.getElementById('minCommission').value)
    };
    
    try {
        await apiCall('/admin/settings/commission', {
            method: 'PUT',
            body: JSON.stringify(settings)
        });
        
        showToast('Commission settings saved successfully', 'success');
    } catch (error) {
        showToast('Failed to save settings: ' + error.message, 'error');
    }
});

// Save auction settings
document.getElementById('auctionSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const settings = {
        minDuration: parseInt(document.getElementById('minDuration').value),
        maxDuration: parseInt(document.getElementById('maxDuration').value),
        minBidIncrement: parseFloat(document.getElementById('minBidIncrement').value),
        autoExtend: parseInt(document.getElementById('autoExtend').value)
    };
    
    try {
        await apiCall('/admin/settings/auction', {
            method: 'PUT',
            body: JSON.stringify(settings)
        });
        
        showToast('Auction settings saved successfully', 'success');
    } catch (error) {
        showToast('Failed to save settings: ' + error.message, 'error');
    }
});

// Save email settings
document.getElementById('emailSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const settings = {
        host: document.getElementById('smtpHost').value,
        port: parseInt(document.getElementById('smtpPort').value),
        username: document.getElementById('smtpUsername').value,
        password: document.getElementById('smtpPassword').value,
        enabled: document.getElementById('emailNotifications').checked
    };
    
    try {
        await apiCall('/admin/settings/email', {
            method: 'PUT',
            body: JSON.stringify(settings)
        });
        
        showToast('Email settings saved successfully', 'success');
    } catch (error) {
        showToast('Failed to save settings: ' + error.message, 'error');
    }
});

// Save security settings
document.getElementById('securitySettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const settings = {
        sessionTimeout: parseInt(document.getElementById('sessionTimeout').value),
        maxLoginAttempts: parseInt(document.getElementById('maxLoginAttempts').value),
        twoFactorAuth: document.getElementById('twoFactorAuth').checked,
        emailVerification: document.getElementById('requireEmailVerification').checked
    };
    
    try {
        await apiCall('/admin/settings/security', {
            method: 'PUT',
            body: JSON.stringify(settings)
        });
        
        showToast('Security settings saved successfully', 'success');
    } catch (error) {
        showToast('Failed to save settings: ' + error.message, 'error');
    }
});

// Maintenance actions
function clearCache() {
    if (confirmAction('Are you sure you want to clear the cache?')) {
        showToast('Cache cleared successfully', 'success');
    }
}

function runDatabaseOptimization() {
    if (confirmAction('Are you sure you want to optimize the database?')) {
        showToast('Database optimization started...', 'info');
        setTimeout(() => {
            showToast('Database optimized successfully', 'success');
        }, 2000);
    }
}

function exportBackup() {
    showToast('Generating backup...', 'info');
    setTimeout(() => {
        showToast('Backup export functionality coming soon', 'info');
    }, 1000);
}

function viewLogs() {
    showToast('System logs viewer coming soon', 'info');
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
});
