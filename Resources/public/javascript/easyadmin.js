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
    var cookieName = '_easyadmin_navigation_collapse';

    return function() {
        if (hidden) {
            createCookie(cookieName, true, Infinity);
        } else {
            deleteCookie(cookieName);
        }
    };
}

function createCookie(name, value, expiration, path, domain, secure)
{
    if (!name || /^(?:expires|max\-age|path|domain|secure)$/i.test(name)) { return false; }

    var expires = "";
    if (expiration) {
        switch (expiration.constructor) {
            case Number:
                expires = expiration === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + expiration;
                break;
            case String:
                expires = "; expires=" + expiration;
                break;
            case Date:
                expires = "; expires=" + expiration.toUTCString();
                break;
        }
    }

    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + (domain ? "; domain=" + domain : "") + (path ? "; path=" + path : "") + (secure ? "; secure" : "");

    return true;
}

function deleteCookie(name, path, domain)
{
    document.cookie = encodeURIComponent(name) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (domain ? "; domain=" + domain : "") + (path ? "; path=" + path : "");

    return true;
}
