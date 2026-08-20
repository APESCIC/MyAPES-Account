import {
    ArrowRight,
    BadgeCheck,
    BriefcaseBusiness,
    Building2,
    ChevronRight,
    CirclePause,
    CircleCheckBig,
    CircleUserRound,
    Compass,
    createIcons,
    Heart,
    HeartPulse,
    House,
    LayoutDashboard,
    Leaf,
    LogIn,
    LogOut,
    Menu,
    MessagesSquare,
    Moon,
    PawPrint,
    Settings,
    ShieldCheck,
    Sprout,
    Sun,
    Ticket,
    UserPlus,
    UserRound,
    X,
} from 'lucide';
import { initChangeLog } from './change-log.js';

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
    themeColor?.setAttribute('content', normalizedTheme === 'dark' ? '#1c1914' : '#f3e4c4');

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
        BriefcaseBusiness,
        Building2,
        ChevronRight,
        CirclePause,
        CircleCheckBig,
        CircleUserRound,
        Compass,
        Heart,
        HeartPulse,
        House,
        LayoutDashboard,
        Leaf,
        LogIn,
        LogOut,
        Menu,
        MessagesSquare,
        Moon,
        PawPrint,
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
initChangeLog();

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

const mascotStorageKey = 'myapes-mascot-dismissed-v2';
const mascotDock = document.querySelector('[data-mascot-dock]');
const mascotDismiss = document.querySelector('[data-mascot-dismiss]');

const readDismissedMascotRoutes = () => {
    try {
        const stored = JSON.parse(localStorage.getItem(mascotStorageKey) ?? '[]');

        return Array.isArray(stored) ? stored.filter((value) => typeof value === 'string') : [];
    } catch {
        return [];
    }
};

if (mascotDock instanceof HTMLElement) {
    const routeName = mascotDock.dataset.mascotRoute ?? '';

    if (routeName !== '' && readDismissedMascotRoutes().includes(routeName)) {
        mascotDock.hidden = true;
    }

    mascotDismiss?.addEventListener('click', () => {
        if (routeName === '') {
            return;
        }

        const dismissed = new Set(readDismissedMascotRoutes());
        dismissed.add(routeName);

        try {
            localStorage.setItem(mascotStorageKey, JSON.stringify([...dismissed]));
        } catch {
            // The tip still hides for this page when storage is unavailable.
        }

        mascotDock.hidden = true;
    });
}
