$(function () {
    var mainMenu = document.getElementById('header-nav');

    $(mainMenu).bind('scroll', mainMenuDropShadow);
    $(window).bind('resize', function() {
        mainMenuDropShadow.apply(mainMenu);
        mainMenuResponsiveCollapse();
    });

    mainMenuDropShadow.apply(mainMenu);
    mainMenuResponsiveCollapse();
});

function mainMenuDropShadow() {
    var headerFooter = $("#header-footer");

    if (this.scrollHeight === this.clientHeight) {
        headerFooter.removeClass('drop-shadow');
        return;
    }

    var scrollPercent = 100 * this.scrollTop / this.scrollHeight / (1 - this.clientHeight / this.scrollHeight);
    isNaN(scrollPercent) || scrollPercent >= 100 ? headerFooter.removeClass('drop-shadow') : headerFooter.addClass('drop-shadow');
}

function mainMenuResponsiveCollapse() {
    var mainMenuItems = $('#header-menu');

    if ($(window).width() > 768 && $(window).width() < 1200) {
        mainMenuItems.flexMenu({'linkText': '...'});
    } else {
        mainMenuItems.flexMenu({ 'undo': true });
    }
}
