// this matches only the tabs created by EasyAdmin; the <twig:ea:Tabs:Item> component
// always generates this 'tablist-*' id, so custom Bootstrap tabs added by users on
// their own backend pages are not affected
const TAB_SELECTOR = 'a[data-bs-toggle="tab"][id^="tablist-"]';

// don't push a new history entry when the tab changes because of a back/forward navigation
let isNavigatingHistory = false;

export function initTabs() {
    if (null === document.querySelector(TAB_SELECTOR)) {
        return;
    }

    persistSelectedTab();
    linkActionsToSelectedTab();
}

export function setTabAsActive(tabItemId) {
    const tabElement = document.getElementById(tabItemId);
    if (!tabElement) {
        return;
    }

    const Tab = bootstrap.Tab;
    const bootstrapTab = new Tab(tabElement);
    // when showing a tab, Bootstrap hides all the other tabs automatically
    bootstrapTab.show();
}

function persistSelectedTab() {
    // the ID of the selected tab is appended as a hash in the URL to persist it;
    // if the URL has a hash, try to look for a tab with that ID and show it
    const urlHash = window.location.hash;
    if (urlHash) {
        const selectedTabPaneId = urlHash.substring(1); // remove the leading '#' from the hash
        const selectedTabId = `tablist-${selectedTabPaneId}`;
        setTabAsActive(selectedTabId);
    }

    document.querySelectorAll(TAB_SELECTOR).forEach((tabElement) => {
        tabElement.addEventListener('shown.bs.tab', (event) => {
            if (isNavigatingHistory) {
                return;
            }
            const urlHash = `#${event.target.getAttribute('href').substring(1)}`;
            history.pushState({}, '', urlHash);
        });
    });

    window.addEventListener('popstate', () => {
        isNavigatingHistory = true;
        const urlHash = window.location.hash;
        if (urlHash) {
            const selectedTabPaneId = urlHash.substring(1);
            const selectedTabId = `tablist-${selectedTabPaneId}`;
            setTabAsActive(selectedTabId);
        } else {
            // no hash means show the first tab
            const firstTab = document.querySelector(TAB_SELECTOR);
            if (firstTab) {
                setTabAsActive(firstTab.id);
            }
        }
        isNavigatingHistory = false;
    });
}

// appends the selected tab hash to the URL of some actions (e.g. the 'edit' action of
// the 'detail' page) so those pages open the same tab that was selected in the current
// page; tab IDs are generated from tab labels, so they are the same in both pages
function linkActionsToSelectedTab(actionNames = ['edit', 'detail']) {
    // actions rendered as forms use 'href="#"' and submit via JavaScript, so they are excluded
    const actionSelector = actionNames
        .map((actionName) => `a[data-action-name="${actionName}"][href]:not([data-ea-action-form-id])`)
        .join(', ');
    const actionLinks = document.querySelectorAll(actionSelector);
    if (0 === actionLinks.length) {
        return;
    }

    // store the original URLs (without hash) so changing tabs replaces the hash instead of appending more hashes
    const actionUrls = new Map();
    actionLinks.forEach((actionLink) => {
        actionUrls.set(actionLink, actionLink.getAttribute('href').split('#')[0]);
    });

    // the href is rewritten (instead of using a click event listener) so middle-click,
    // Ctrl/Cmd + click and "copy link address" also point to the selected tab
    const updateActionLinks = () => {
        // the href of the selected tab link is the '#<paneId>' hash to append to the action URLs
        const selectedTab = document.querySelector(`${TAB_SELECTOR}.active`);
        actionLinks.forEach((actionLink) => {
            const actionUrl = actionUrls.get(actionLink);
            actionLink.setAttribute(
                'href',
                null === selectedTab ? actionUrl : `${actionUrl}${selectedTab.getAttribute('href')}`
            );
        });
    };

    updateActionLinks();
    document.querySelectorAll(TAB_SELECTOR).forEach((tabElement) => {
        tabElement.addEventListener('shown.bs.tab', updateActionLinks);
    });
}
