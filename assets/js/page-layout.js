document.body.classList.add(
    'ea-content-width-' + (localStorage.getItem('ea/content/width') || document.body.dataset.eaContentWidth),
    'ea-sidebar-width-' + (localStorage.getItem('ea/sidebar/width') || document.body.dataset.eaSidebarWidth)
);
