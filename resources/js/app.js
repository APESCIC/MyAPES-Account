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
    Menu,
    MessagesSquare,
    Moon,
    Settings,
    ShieldCheck,
    Sprout,
    Sun,
    Ticket,
    UserPlus,
    UserRound,
    X,
} from 'lucide';

const themeStorageKey = 'myapes-theme';
const themeToggle = document.querySelector('[data-theme-toggle]');
const themeLabel = document.querySelector('[data-theme-label]');
const themeColor = document.querySelector('meta[name="theme-color"]');
const sidebar = document.querySelector('[data-sidebar]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarClose = document.querySelector('[data-sidebar-close]');
const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
const sidebarMedia = window.matchMedia('(max-width: 64rem)');
let sidebarReturnFocus = null;

const applyTheme = (theme, persist = false) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';

    document.documentElement.dataset.theme = normalizedTheme;
    document.documentElement.style.colorScheme = normalizedTheme;
    themeColor?.setAttribute('content', normalizedTheme === 'dark' ? '#021b20' : '#f7f1e3');

    if (themeToggle) {
        const isDark = normalizedTheme === 'dark';
        themeToggle.setAttribute('aria-pressed', String(isDark));
        themeToggle.setAttribute('aria-label', `Switch to ${isDark ? 'light' : 'dark'} theme`);
        if (themeLabel) {
            themeLabel.textContent = isDark ? 'Dark mode' : 'Light mode';
        }
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
        Menu,
        MessagesSquare,
        Moon,
        Settings,
        ShieldCheck,
        Sprout,
        Sun,
        Ticket,
        UserPlus,
        UserRound,
        X,
    },
});

applyTheme(document.documentElement.dataset.theme);

themeToggle?.addEventListener('click', () => {
    applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark', true);
});

const sidebarFocusableElements = () => Array.from(sidebar?.querySelectorAll(
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
) ?? []).filter((element) => element.getClientRects().length > 0);

const setSidebarOpen = (open, returnFocus = true) => {
    if (!sidebar || !sidebarToggle) {
        return;
    }

    const shouldOpen = Boolean(open && sidebarMedia.matches);
    sidebar.dataset.open = String(shouldOpen);
    sidebarToggle.setAttribute('aria-expanded', String(shouldOpen));
    sidebarToggle.setAttribute('aria-label', shouldOpen ? 'Close navigation menu' : 'Open navigation menu');
    document.body.classList.toggle('sidebar-open', shouldOpen);

    if (shouldOpen) {
        sidebarReturnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : sidebarToggle;
        window.requestAnimationFrame(() => {
            (sidebarClose ?? sidebarFocusableElements()[0])?.focus();
        });
    } else if (returnFocus && sidebarReturnFocus instanceof HTMLElement) {
        sidebarReturnFocus.focus();
        sidebarReturnFocus = null;
    }
};

sidebarToggle?.addEventListener('click', () => {
    setSidebarOpen(sidebar?.dataset.open !== 'true');
});

sidebarClose?.addEventListener('click', () => setSidebarOpen(false));
sidebarBackdrop?.addEventListener('click', () => setSidebarOpen(false));

document.addEventListener('keydown', (event) => {
    if (sidebar?.dataset.open !== 'true') {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        setSidebarOpen(false);
        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const focusable = sidebarFocusableElements();
    const first = focusable[0];
    const last = focusable.at(-1);

    if (!first || !last) {
        return;
    }

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});

sidebarMedia.addEventListener('change', () => {
    setSidebarOpen(false, false);
});
