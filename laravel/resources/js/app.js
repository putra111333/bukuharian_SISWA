import './bootstrap';

const sidebar = document.getElementById('portal-sidebar');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarScrim = document.querySelector('[data-sidebar-scrim]');

if (sidebar && sidebarToggle) {
    if (window.matchMedia('(max-width: 850px)').matches) {
        sidebarToggle.setAttribute('aria-expanded', 'false');
    }

    const closeMobileSidebar = () => {
        if (!window.matchMedia('(max-width: 850px)').matches) {
            return;
        }

        document.body.classList.remove('portal-sidebar-open');
        sidebarToggle.setAttribute('aria-expanded', 'false');
    };

    sidebarToggle.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 850px)').matches) {
            const isOpen = document.body.classList.toggle('portal-sidebar-open');
            sidebarToggle.setAttribute('aria-expanded', String(isOpen));
            return;
        }

        const isCollapsed = document.body.classList.toggle('portal-sidebar-collapsed');
        sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
    });

    sidebarScrim?.addEventListener('click', closeMobileSidebar);

    const navLinks = [...sidebar.querySelectorAll('[data-portal-nav-link]')];
    const dashboard = document.querySelector('.portal-container');
    const setActiveFeature = (featureId) => {
        const feature = document.getElementById(featureId);
        const activeLink = navLinks.find((link) => link.hash === `#${featureId}`);

        if (!dashboard || !feature || !dashboard.contains(feature)) {
            return false;
        }

        const visibleElements = new Set();
        const showBranch = (element) => {
            for (let current = element; current && current !== dashboard; current = current.parentElement) {
                visibleElements.add(current);
            }
            element.querySelectorAll('*').forEach((child) => visibleElements.add(child));
        };

        showBranch(feature);
        activeLink?.dataset.related?.split(',').forEach((relatedId) => {
            const relatedFeature = document.getElementById(relatedId);
            if (relatedFeature && dashboard.contains(relatedFeature)) {
                showBranch(relatedFeature);
            }
        });
        if (featureId === 'dashboard-overview') {
            dashboard.querySelectorAll('.metric-grid, .family-welcome').forEach(showBranch);
        }
        dashboard.querySelectorAll('.portal-alert').forEach(showBranch);

        dashboard.querySelectorAll('*').forEach((element) => {
            element.hidden = !visibleElements.has(element);
        });

        navLinks.forEach((link) => {
            const isActive = link.hash === `#${featureId}`;
            link.classList.toggle('is-active', isActive);
            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
        dashboard.classList.add('has-active-feature');

        return true;
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const featureId = link.hash.slice(1);

            if (setActiveFeature(featureId)) {
                history.pushState(null, '', `#${featureId}`);
                window.scrollTo({
                    top: 0,
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                });
            }

            closeMobileSidebar();
        });
    });

    window.addEventListener('popstate', () => {
        setActiveFeature(window.location.hash.slice(1) || 'dashboard-overview');
    });

    const initialFeature = window.location.hash.slice(1) || 'dashboard-overview';
    if (!setActiveFeature(initialFeature)) {
        setActiveFeature('dashboard-overview');
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });
}

document.querySelectorAll('[data-demo-login]').forEach((button) => {
    button.addEventListener('click', () => {
        const username = document.getElementById('username');
        const password = document.getElementById('password');

        username.value = button.dataset.demoLogin;
        password.value = button.dataset.demoLogin;
        password.focus();
    });
});

let installPrompt;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    document.querySelectorAll('[data-install-app]').forEach((button) => {
        button.hidden = false;
    });
});

document.querySelectorAll('[data-install-app]').forEach((button) => {
    button.addEventListener('click', async () => {
        if (!installPrompt) {
            return;
        }

        installPrompt.prompt();
        await installPrompt.userChoice;
        installPrompt = null;
        document.querySelectorAll('[data-install-app]').forEach((installButton) => {
            installButton.hidden = true;
        });
    });
});

window.addEventListener('appinstalled', () => {
    document.querySelectorAll('[data-install-app]').forEach((button) => {
        button.hidden = true;
    });
});
