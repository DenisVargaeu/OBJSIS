// assets/js/theme.js

function applyTheme() {
    const hour = new Date().getHours();
    // Day time: 7 AM to 7 PM (19:00)
    const isDay = hour >= 7 && hour < 19;
    const pref = localStorage.getItem('theme_pref');

    if (pref === 'light') {
        document.body.classList.add('light-mode');
    } else if (pref === 'dark') {
        document.body.classList.remove('light-mode');
    } else {
        // Auto based on time
        if (isDay) {
            document.body.classList.add('light-mode');
        } else {
            document.body.classList.remove('light-mode');
        }
    }
}

function toggleTheme() {
    if (document.body.classList.contains('light-mode')) {
        document.body.classList.remove('light-mode');
        localStorage.setItem('theme_pref', 'dark');
    } else {
        document.body.classList.add('light-mode');
        localStorage.setItem('theme_pref', 'light');
    }
}

// Apply immediately to prevent flash
applyTheme();
document.addEventListener('DOMContentLoaded', applyTheme);
