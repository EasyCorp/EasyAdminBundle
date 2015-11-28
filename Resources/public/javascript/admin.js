$(function () {
    var mainMenu = document.getElementById('header-nav');

    $(mainMenu).bind('scroll', mainMenuDropShadow);
    $(window).bind('resize', function() {
        mainMenuDropShadow.apply(mainMenu);
        mainMenuResponsiveCollapse();
    });

    mainMenuDropShadow.apply(mainMenu);
    mainMenuResponsiveCollapse();
    createNullableControls();
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

    if ($(window).width() > 768 && $(window).width() < 1024) {
        mainMenuItems.flexMenu({ 'linkText': '<i class="fa fa-ellipsis-h"></i>' });
    } else {
        mainMenuItems.flexMenu({ 'undo': true });
    }
}

function createNullableControls() {
    var fnNullDates = function() {
        var checkbox = $(this);

        checkbox.closest('.form-group').find('select').each(function() {
            var formFiledIsDisabled = checkbox.is(':checked');
            $(this).prop('disabled', formFiledIsDisabled);

            if (formFiledIsDisabled) {
                $(this).parent().slideUp({ duration: 200 });
            } else {
                $(this).parent().slideDown({ duration: 200 });
            }
        });
    };

    $('.nullable-control :checkbox').bind('change', fnNullDates).each(fnNullDates);
}
