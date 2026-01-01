// assets/js/theme.js

function updateThemeIcons() {
    const isLight = document.body.classList.contains('light-mode');
    const icons = document.querySelectorAll('.theme-toggle-icon');
    icons.forEach(icon => {
        if (isLight) {
            icon.classList.replace('fa-moon', 'fa-sun');
        } else {
            icon.classList.replace('fa-sun', 'fa-moon');
        }
    });
}

function applyTheme() {
    const hour = new Date().getHours();
    const isDay = hour >= 7 && hour < 19;
    const pref = localStorage.getItem('theme_pref');

    if (pref === 'light') {
        document.body.classList.add('light-mode');
    } else if (pref === 'dark') {
        document.body.classList.remove('light-mode');
    } else {
        if (isDay) {
            document.body.classList.add('light-mode');
        } else {
            document.body.classList.remove('light-mode');
        }
    }
    updateThemeIcons();
}

function toggleTheme() {
    if (document.body.classList.contains('light-mode')) {
        document.body.classList.remove('light-mode');
        localStorage.setItem('theme_pref', 'dark');
    } else {
        document.body.classList.add('light-mode');
        localStorage.setItem('theme_pref', 'light');
    }
    updateThemeIcons();
}

// Apply immediately to prevent flash
applyTheme();
document.addEventListener('DOMContentLoaded', applyTheme);
