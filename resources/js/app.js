import './bootstrap';

const THEME_KEY = 'app-theme';

function getTheme() {
    return document.documentElement.getAttribute('data-theme') || 'dark';
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(THEME_KEY, theme);
    updateThemeToggleUi(theme);

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.setAttribute('content', theme === 'dark' ? '#0f172a' : '#6366f1');
    }
}

function updateThemeToggleUi(theme) {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;

    const iconLight = btn.querySelector('.theme-icon-light');
    const iconDark = btn.querySelector('.theme-icon-dark');

    if (iconLight && iconDark) {
        iconLight.classList.toggle('d-none', theme === 'dark');
        iconDark.classList.toggle('d-none', theme !== 'dark');
    }

    const label = theme === 'dark' ? 'Mode terang' : 'Mode gelap';
    btn.setAttribute('aria-label', label);
    btn.setAttribute('title', label);
}

document.addEventListener('DOMContentLoaded', () => {
    updateThemeToggleUi(getTheme());

    const btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', () => {
            setTheme(getTheme() === 'dark' ? 'light' : 'dark');
        });
    }
});
