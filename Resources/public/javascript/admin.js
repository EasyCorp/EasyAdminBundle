$(function() {
    $(window).bind('load', updateSidebar);
    $(window).bind('resize', updateSidebar);
});

function updateSidebar() {
    // the sticky sidebar is only displayed in large desktops
    if ($(window).width() < 1200) {
        return;
    }

    // the '#header-nav' element needs to define its 'height' explicitly
    // otherwise, the 'overflow-y: scroll' property will be ignored
    var menuHeight = $(window).height() - $('#header-logo').outerHeight() - $('#header-security').outerHeight();
    $('#header-nav').css('height', menuHeight);
}
