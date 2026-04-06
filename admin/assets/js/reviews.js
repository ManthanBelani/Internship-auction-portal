// Reviews management JavaScript

// Load reviews from API
async function loadReviews() {
    try {
        const ratingFilter = document.getElementById('ratingFilter').value;
        const typeFilter = document.getElementById('typeFilter').value;
        const searchInput = document.getElementById('searchInput').value;
        
        let queryParams = [];
        if (ratingFilter) queryParams.push(`rating=${ratingFilter}`);
        if (typeFilter) queryParams.push(`type=${typeFilter}`);
        if (searchInput) queryParams.push(`search=${encodeURIComponent(searchInput)}`);
        
        const queryString = queryParams.length > 0 ? '?' + queryParams.join('&') : '';
        const data = await apiCall(`/admin/reviews${queryString}`);
        
        displayReviews(data.reviews || []);
    } catch (error) {
        console.error('Failed to load reviews:', error);
        showToast('Failed to load reviews: ' + error.message, 'error');
        displayReviews([]);
    }
}

// Display reviews in table
function displayReviews(reviews) {
    const tbody = document.getElementById('reviewsTableBody');
    
    if (reviews.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="loading">No reviews found</td></tr>';
        return;
    }
    
    tbody.innerHTML = reviews.map(review => `
        <tr>
            <td>${review.id}</td>
            <td>${escapeHtml(review.reviewer_name || 'N/A')}</td>
            <td>${escapeHtml(review.reviewed_name || 'N/A')}</td>
            <td>${getRatingStars(review.rating)}</td>
            <td>${escapeHtml(truncateText(review.comment, 50))}</td>
            <td>${formatDate(review.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-primary" onclick="viewReview(${review.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteReview(${review.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Get rating stars HTML
function getRatingStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="fas fa-star" style="color: #FFD700;"></i>';
        } else {
            stars += '<i class="far fa-star" style="color: #ccc;"></i>';
        }
    }
    return stars;
}

// Truncate text
function truncateText(text, maxLength) {
    if (!text) return 'No comment';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// View review details
function viewReview(reviewId) {
    showToast('Review details view coming soon', 'info');
}

// Delete review
async function deleteReview(reviewId) {
    if (!confirmAction('Are you sure you want to delete this review? This action cannot be undone.')) {
        return;
    }
    
    try {
        await apiCall(`/admin/reviews/${reviewId}`, {
            method: 'DELETE'
        });
        
        showToast('Review deleted successfully', 'success');
        loadReviews();
    } catch (error) {
        showToast('Failed to delete review: ' + error.message, 'error');
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
    loadReviews();
    
    // Add enter key listener to search input
    document.getElementById('searchInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            loadReviews();
        }
    });
});
