import {
    ArrowRight,
    BadgeCheck,
    ChevronRight,
    CircleCheckBig,
    CircleUserRound,
    Compass,
    createIcons,
    Heart,
    House,
    LayoutDashboard,
    Leaf,
    LogIn,
    LogOut,
    MessagesSquare,
    Moon,
    Settings,
    ShieldCheck,
    Sprout,
    Sun,
    Ticket,
    UserPlus,
    UserRound,
} from 'lucide';

const themeStorageKey = 'myapes-theme';
const themeToggle = document.querySelector('[data-theme-toggle]');
const themeColor = document.querySelector('meta[name="theme-color"]');

const applyTheme = (theme, persist = false) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';

    document.documentElement.dataset.theme = normalizedTheme;
    document.documentElement.style.colorScheme = normalizedTheme;
    themeColor?.setAttribute('content', normalizedTheme === 'dark' ? '#021b20' : '#f7f1e3');

    if (themeToggle) {
        const isDark = normalizedTheme === 'dark';
        themeToggle.setAttribute('aria-pressed', String(isDark));
        themeToggle.setAttribute('aria-label', `Switch to ${isDark ? 'light' : 'dark'} theme`);
    }

    if (persist) {
        try {
            localStorage.setItem(themeStorageKey, normalizedTheme);
        } catch {
            // The selected theme still applies for the current page when storage is unavailable.
        }
    }
};

createIcons({
    icons: {
        ArrowRight,
        BadgeCheck,
        ChevronRight,
        CircleCheckBig,
        CircleUserRound,
        Compass,
        Heart,
        House,
        LayoutDashboard,
        Leaf,
        LogIn,
        LogOut,
        MessagesSquare,
        Moon,
        Settings,
        ShieldCheck,
        Sprout,
        Sun,
        Ticket,
        UserPlus,
        UserRound,
    },
});

applyTheme(document.documentElement.dataset.theme);

themeToggle?.addEventListener('click', () => {
    applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark', true);
});
