// Dark mode toggle functionality
function toggleTheme() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? null : 'dark';
    
    body.setAttribute('data-theme', newTheme);
    
    // Set cookie
    const expires = new Date();
    expires.setTime(expires.getTime() + (365 * 24 * 60 * 60 * 1000)); // 1 year
    document.cookie = `darkmode=${newTheme === 'dark' ? 'on' : 'off'}; expires=${expires.toUTCString()}; path=/`;
    
    // Reload to apply theme changes
    location.reload();
}

// Initialize dark mode on page load
document.addEventListener('DOMContentLoaded', function() {
    // Theme is already set by PHP, no need to do anything here
});

