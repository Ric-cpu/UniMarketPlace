// Function to show notification
function showNotification(type, title, message, duration = 5000) {
    // Save notification to localStorage
    const notification = {
        type,
        title,
        message,
        timestamp: new Date().getTime()
    };
    localStorage.setItem('pendingNotification', JSON.stringify(notification));

    // Show notification if we're not about to navigate away
    displayNotification(type, title, message, duration);
}

// Function to display notification
function displayNotification(type, title, message, duration = 5000) {
    // Create container if it doesn't exist
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.left = '50%';
        container.style.transform = 'translateX(-50%)';
        container.style.zIndex = '9999';
        container.style.minWidth = '300px';
        container.style.maxWidth = '500px';
        document.body.appendChild(container);
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.style.marginBottom = '10px';
    
    // Set variant and icon based on type
    let variant, icon;
    switch(type) {
        case 'success':
            variant = 'success';
            icon = 'circle-check';
            break;
        case 'error':
            variant = 'danger';
            icon = 'circle-exclamation';
            break;
        case 'warning':
            variant = 'warning';
            icon = 'triangle-exclamation';
            break;
        case 'info':
            variant = 'brand';
            icon = 'circle-info';
            break;
        default:
            variant = 'neutral';
            icon = 'gear';
    }

    notification.innerHTML = `
        <wa-callout variant="${variant}">
            <wa-icon slot="icon" name="${icon}" variant="regular"></wa-icon>
            <strong>${title}</strong><br />
            ${message}
        </wa-callout>
    `;

    // Add to container
    container.appendChild(notification);

    // Remove after duration
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            notification.remove();
            if (container.children.length === 0) {
                container.remove();
            }
        }, 500);
    }, duration);
}

// Check for pending notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    const pendingNotification = localStorage.getItem('pendingNotification');
    if (pendingNotification) {
        const { type, title, message } = JSON.parse(pendingNotification);
        displayNotification(type, title, message);
        localStorage.removeItem('pendingNotification');
    }
}); 