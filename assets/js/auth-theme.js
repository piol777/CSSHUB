document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('authThemeToggle');
    const body = document.body;
    const THEME_ORDER = ['light', 'dark-purple', 'dark'];

    function applyTheme(theme) {
        body.classList.remove('theme-dark-purple', 'theme-dark');
        if (theme === 'dark-purple') {
            body.classList.add('theme-dark-purple');
        } else if (theme === 'dark') {
            body.classList.add('theme-dark');
        }
        // 'light' = walang class
    }

    const savedTheme = localStorage.getItem('cdsga_theme') || 'light';
    applyTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const current = localStorage.getItem('cdsga_theme') || 'light';
            const nextTheme = THEME_ORDER[(THEME_ORDER.indexOf(current) + 1) % THEME_ORDER.length];
            applyTheme(nextTheme);
            localStorage.setItem('cdsga_theme', nextTheme);
        });
    }
});