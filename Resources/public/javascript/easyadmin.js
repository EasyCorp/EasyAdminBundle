$(function () {
    var body = $('body');

    body
        .on('expanded.pushMenu', toggleNavigation(false))
        .on('collapsed.pushMenu', toggleNavigation(true))
    ;

    createNullableControls();
});

function createNullableControls() {
    var fnNullDates = function() {
        var checkbox = $(this);

        checkbox.closest('.form-group').find('select').each(function() {
            var formFieldIsDisabled = checkbox.is(':checked');
            $(this).prop('disabled', formFieldIsDisabled);

            if (formFieldIsDisabled) {
                $(this).parent().slideUp({ duration: 200 });
            } else {
                $(this).parent().slideDown({ duration: 200 });
            }
        });
    };

    $('.nullable-control :checkbox').bind('change', fnNullDates).each(fnNullDates);
}

function toggleNavigation(hidden) {
    var cookieName = '_easyadmin_navigation_isCollapsed';

    return function() {
        if (hidden) {
            createPersistentCookie(cookieName, true);
        } else {
            deleteCookie(cookieName);
        }
    };
}

function createPersistentCookie(name, value)
{
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + "; expires=Fri, 31 Dec 9999 23:59:59 GMT";
}

function deleteCookie(name, path, domain)
{
    document.cookie = encodeURIComponent(name) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (domain ? "; domain=" + domain : "") + (path ? "; path=" + path : "");
}
