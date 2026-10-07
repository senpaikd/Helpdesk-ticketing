import './bootstrap';
import Chart from 'chart.js/auto';

const root = document.documentElement;
const themeButton = document.querySelector('[data-theme-toggle]');
const menuButton = document.querySelector('[data-menu-toggle]');
const mobileMenu = document.querySelector('[data-mobile-menu]');
const workspaceSidebar = document.querySelector('[data-workspace-sidebar]');
const workspaceMenuButton = document.querySelector('[data-workspace-menu-toggle]');
const workspaceBackdrop = document.querySelector('[data-workspace-backdrop]');

themeButton?.addEventListener('click', () => {
    const dark = root.classList.toggle('dark');

    localStorage.setItem(
        'helpdesk-theme',
        dark ? 'dark' : 'light'
    );
});

menuButton?.addEventListener('click', () => {
    if (!mobileMenu) {
        return;
    }

    const opening = mobileMenu.classList.contains('hidden');

    mobileMenu.classList.toggle('hidden');
    menuButton.setAttribute('aria-expanded', String(opening));
});

mobileMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        mobileMenu.classList.add('hidden');
        menuButton?.setAttribute('aria-expanded', 'false');
    });
});

const setWorkspaceNavigation = (open, restoreFocus = false) => {
    if (!workspaceSidebar || !workspaceMenuButton || !workspaceBackdrop) {
        return;
    }

    workspaceSidebar.classList.toggle('is-open', open);
    workspaceBackdrop.hidden = !open;
    workspaceMenuButton.setAttribute('aria-expanded', String(open));
    workspaceMenuButton.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');

    if (open) {
        workspaceSidebar.querySelector('a')?.focus();
    } else if (restoreFocus) {
        workspaceMenuButton.focus();
    }
};

workspaceMenuButton?.addEventListener('click', () => {
    setWorkspaceNavigation(!workspaceSidebar?.classList.contains('is-open'));
});

workspaceBackdrop?.addEventListener('click', () => setWorkspaceNavigation(false, true));

workspaceSidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setWorkspaceNavigation(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && workspaceSidebar?.classList.contains('is-open')) {
        setWorkspaceNavigation(false, true);
    }
});

const ticketDrawer = document.querySelector('[data-workspace-ticket-drawer]');
const drawerMask = document.querySelector('[data-workspace-drawer-close-url]');

if (ticketDrawer) {
    ticketDrawer.querySelector('h2')?.focus();
}

drawerMask?.addEventListener('click', () => {
    window.location.assign(drawerMask.dataset.workspaceDrawerCloseUrl);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && ticketDrawer && drawerMask?.dataset.workspaceDrawerCloseUrl) {
        window.location.assign(drawerMask.dataset.workspaceDrawerCloseUrl);
    }
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const showing = input.type === 'text';

        input.type = showing ? 'password' : 'text';
        button.textContent = showing ? 'Show' : 'Hide';
        button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
});

document.querySelectorAll('[data-workspace-chart]').forEach((canvas) => {
    if (!(canvas instanceof HTMLCanvasElement)) {
        return;
    }

    let chartData;

    try {
        chartData = JSON.parse(canvas.dataset.chart ?? '{}');
    } catch {
        return;
    }

    const styles = getComputedStyle(document.body);
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    Chart.getChart(canvas)?.destroy();

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: chartData.labels ?? [],
            datasets: [{
                data: chartData.counts ?? [],
                backgroundColor: styles.getPropertyValue('--portal-primary').trim() || '#4f46e5',
                borderRadius: 7,
                borderSkipped: false,
            }],
        },
        options: {
            animation: reduceMotion ? false : { duration: 450 },
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { displayColors: false },
            },
            scales: {
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: { color: styles.getPropertyValue('--portal-muted').trim() },
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: styles.getPropertyValue('--portal-border').trim() },
                    ticks: { color: styles.getPropertyValue('--portal-muted').trim(), precision: 0 },
                },
            },
        },
    });
});
