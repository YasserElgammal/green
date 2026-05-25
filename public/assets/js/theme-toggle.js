(function () {
    var storageKey = 'green-theme';
    var choices = ['light', 'dark', 'system'];
    var mediaQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var root = document.documentElement;
    var toggle = document.querySelector('[data-theme-toggle]');
    var toggleIcon = document.querySelector('[data-theme-toggle-icon]');
    var toggleLabel = document.querySelector('[data-theme-toggle-label]');
    var buttons = document.querySelectorAll('[data-theme-choice]');
    var labels = {
        light: (toggle && toggle.dataset.themeLabelLight) || 'Light',
        dark: (toggle && toggle.dataset.themeLabelDark) || 'Dark',
        system: (toggle && toggle.dataset.themeLabelSystem) || 'System'
    };
    var icons = {
        light: 'bi-sun',
        dark: 'bi-moon-stars',
        system: 'bi-laptop'
    };

    function getStoredPreference() {
        var stored = 'system';

        try {
            stored = localStorage.getItem(storageKey) || 'system';
        } catch (error) {
            stored = 'system';
        }

        return choices.indexOf(stored) === -1 ? 'system' : stored;
    }

    function resolveTheme(preference) {
        if (preference === 'system') {
            return mediaQuery && mediaQuery.matches ? 'dark' : 'light';
        }

        return preference;
    }

    function setToggleIcon(preference) {
        if (!toggleIcon) {
            return;
        }

        toggleIcon.className = 'bi ' + icons[preference] + ' theme-toggle-icon';
    }

    function applyTheme(preference, persist) {
        var resolvedTheme = resolveTheme(preference);

        root.dataset.theme = resolvedTheme;
        root.dataset.themePreference = preference;

        if (toggleLabel) {
            toggleLabel.textContent = labels[preference];
        }

        setToggleIcon(preference);

        buttons.forEach(function (button) {
            var isActive = button.dataset.themeChoice === preference;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (persist) {
            try {
                localStorage.setItem(storageKey, preference);
            } catch (error) {
                // Browsers can block storage in private or restricted contexts.
            }
        }
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            applyTheme(button.dataset.themeChoice, true);
        });
    });

    if (mediaQuery) {
        var syncSystemTheme = function () {
            if (getStoredPreference() === 'system') {
                applyTheme('system', false);
            }
        };

        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', syncSystemTheme);
        } else if (mediaQuery.addListener) {
            mediaQuery.addListener(syncSystemTheme);
        }
    }

    applyTheme(getStoredPreference(), false);
})();
