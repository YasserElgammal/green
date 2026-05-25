(function () {
    var storageKey = 'green-theme';
    var theme = 'system';

    try {
        theme = localStorage.getItem(storageKey) || 'system';
    } catch (error) {
        theme = 'system';
    }

    if (theme !== 'light' && theme !== 'dark' && theme !== 'system') {
        theme = 'system';
    }

    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var resolvedTheme = theme === 'system' ? (prefersDark ? 'dark' : 'light') : theme;

    document.documentElement.dataset.theme = resolvedTheme;
    document.documentElement.dataset.themePreference = theme;
})();
