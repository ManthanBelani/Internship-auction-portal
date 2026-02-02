// Login form handler
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const submitBtn = e.target.querySelector('.btn-login');
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<div class="spinner"></div> Logging in...';
    
    try {
        // Call login API
        const response = await fetch('/api/users/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Login failed');
        }
        
        // Check if user has admin or moderator role
        if (data.user.role !== 'admin' && data.user.role !== 'moderator') {
            throw new Error('You do not have permission to access the admin panel');
        }
        
        // Store user data in session via PHP
        const sessionResponse = await fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'set_session',
                user_data: JSON.stringify({
                    id: data.user.userId,
                    name: data.user.name,
                    email: data.user.email,
                    role: data.user.role,
                    token: data.token
                })
            })
        });
        
        if (sessionResponse.ok) {
            // Redirect to dashboard
            window.location.href = 'index.php';
        } else {
            throw new Error('Failed to create session');
        }
        
    } catch (error) {
        // Show error
        let errorDiv = document.querySelector('.alert-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-error';
            document.querySelector('.login-form').insertBefore(
                errorDiv, 
                document.querySelector('.form-group')
            );
        }
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            ${error.message}
        `;
        
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
    }
});
