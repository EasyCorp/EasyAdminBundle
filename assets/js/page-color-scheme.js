function setEasyAdminColorScheme () {
    const selectedColorScheme = localStorage.getItem('ea/colorScheme') || 'auto';
    const resolvedColorScheme = 'auto' === selectedColorScheme
        ? matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        : selectedColorScheme;

    document.body.classList.remove('ea-light-scheme', 'ea-dark-scheme');
    document.body.classList.add('light' === resolvedColorScheme ? 'ea-light-scheme' : 'ea-dark-scheme');
    localStorage.setItem('ea/colorScheme', selectedColorScheme);
    document.body.style.colorScheme = resolvedColorScheme;
}

setEasyAdminColorScheme();
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
    setEasyAdminColorScheme();
});
