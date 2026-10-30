/**
 * PARSU-GPass: Notification & Toast System
 */

document.addEventListener('DOMContentLoaded', function () {
    const toasts = document.querySelectorAll('.notifications-container .toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 400);
        }, 5000);
    });
});

/**
 * Display dynamic client-side toast notification
 */
function showToast(type, message) {
    let container = document.getElementById('notificationsContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationsContainer';
        container.className = 'notifications-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'ℹ';
    if (type === 'success') icon = '✓';
    else if (type === 'error' || type === 'danger') icon = '✕';
    else if (type === 'warning') icon = '⚠';

    toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-body">${message}</div>
        <button type="button" class="toast-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 400);
    }, 5000);
}

