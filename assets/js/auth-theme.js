document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('authThemeToggle');
    const body = document.body;

    function applyTheme(theme) {
        if (theme === 'dark' || theme === 'dark-purple') {
            body.classList.add('theme-dark');
        } else {
            body.classList.remove('theme-dark');
        }
    }

    // Reuses the same localStorage key as the dashboard theme toggle,
    // so a user's dark/light preference carries over between login and dashboard.
    const savedTheme = localStorage.getItem('cdsga_theme') || 'light';
    applyTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const isDark = body.classList.contains('theme-dark');
            const nextTheme = isDark ? 'light' : 'dark';
            applyTheme(nextTheme);
            localStorage.setItem('cdsga_theme', nextTheme);
        });
    }
});