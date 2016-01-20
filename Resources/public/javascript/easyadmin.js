$(function () {
    var body = $('body');

    body
        .on('expanded.pushMenu', navigationToggled(false))
        .on('collapsed.pushMenu', navigationToggled(true))
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

function navigationToggled(hidden) {
    var key = '_easyadmin_navbar_hidden';

    return function() {
        if (hidden) {
            docCookies.setItem(key, true, Infinity);
        } else {
            docCookies.removeItem(key);
        }
    };
}

