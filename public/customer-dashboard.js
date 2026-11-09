const tabList = document.querySelector('[data-dashboard-tablist]');
const tabs = Array.from(document.querySelectorAll('[data-dashboard-tab]'));
const panels = Array.from(document.querySelectorAll('[data-dashboard-panel]'));

function activateDashboardTab(name, focusTab = false) {
    const activeTab = tabs.find((tab) => tab.dataset.dashboardTab === name);
    if (!activeTab) return;

    tabs.forEach((tab) => {
        const isActive = tab === activeTab;
        tab.setAttribute('aria-selected', String(isActive));
        tab.tabIndex = isActive ? 0 : -1;
        tab.classList.toggle('primary', isActive);
        tab.classList.toggle('outline', !isActive);
    });

    panels.forEach((panel) => {
        const isActive = panel.dataset.dashboardPanel === name;
        panel.hidden = !isActive;
        panel.classList.toggle('is-active', isActive);
    });

    if (focusTab) activeTab.focus();
}

if (tabList && tabs.length && panels.length) {
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activateDashboardTab(tab.dataset.dashboardTab));
    });

    tabList.addEventListener('keydown', (event) => {
        const currentIndex = tabs.indexOf(document.activeElement);
        if (currentIndex === -1) return;

        let nextIndex;
        switch (event.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                nextIndex = (currentIndex + 1) % tabs.length;
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                break;
            case 'Home':
                nextIndex = 0;
                break;
            case 'End':
                nextIndex = tabs.length - 1;
                break;
            default:
                return;
        }

        event.preventDefault();
        activateDashboardTab(tabs[nextIndex].dataset.dashboardTab, true);
    });

    const requestedTab = new URLSearchParams(window.location.search).get('tab');
    const fragmentTabs = {
        '#bookingPanel': 'booking',
        '#estimatePanel': 'estimate',
        '#schedulePanel': 'availability',
        '#bookings': 'bookings',
        '#reportPanel': 'reports',
        '#myReports': 'reports',
    };
    const initialTab = requestedTab || fragmentTabs[window.location.hash] || 'booking';
    activateDashboardTab(initialTab);

    if (requestedTab || fragmentTabs[window.location.hash]) {
        const url = new URL(window.location.href);
        url.searchParams.delete('tab');
        url.hash = '';
        window.history.replaceState({}, '', `${url.pathname}${url.search}`);
    }
}

const availability = document.getElementById('dashboardAvailability');
if (availability) {
    availability.addEventListener('change', () => {
        const option = availability.options[availability.selectedIndex];
        document.getElementById('dashboardDate').value = option.dataset.date || '';
        document.getElementById('dashboardTime').value = option.dataset.time || '';
    });
}

document.querySelectorAll('[data-customer-theme]').forEach((button) => {
    button.addEventListener('click', () => {
        const theme = button.dataset.customerTheme;
        document.documentElement.classList.remove('customer-light', 'customer-dark');
        document.documentElement.classList.add(`customer-${theme}`);
        window.localStorage.setItem('xoruCustomerTheme', theme);
    });
});
