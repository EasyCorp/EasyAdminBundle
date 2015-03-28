$(function () {
    var mainMenu = document.getElementById('header-nav');

    $(mainMenu).bind('scroll', mainMenuDropShadow);
    $(window).bind('resize', function() {
        mainMenuDropShadow.apply(mainMenu);
    });

    mainMenuDropShadow.apply(mainMenu);
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

function enhanceNullableDatetimeFields() {
    var fnNullDates = function() {
        var checkbox = $(this);
        checkbox.parents('.null-control').siblings('select').each(function() {
            $(this).prop('disabled', checkbox.is(':checked'))
        });
    };
    $('.field_datetime .null-control')
        .each(function () {
            $(this).appendTo($(this).siblings('.form-inline'));
        })
        .find(':checkbox').bind('change', fnNullDates).each(fnNullDates);
}
