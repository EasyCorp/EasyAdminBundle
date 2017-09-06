$(function () {
    var body = $('body');

    body
        .on('expanded.pushMenu', toggleNavigation(false))
        .on('collapsed.pushMenu', toggleNavigation(true))
    ;

    createNullableControls();
    createAutoCompleteFields();
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

function toggleNavigation(collapsed) {
    var cookieName = '_easyadmin_navigation_iscollapsed';

    return function() {
        if (collapsed) {
            createPersistentCookie(cookieName, true);
        } else {
            deleteCookie(cookieName);
        }
    };
}

function createPersistentCookie(name, value)
{
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + "; path=/; expires=Fri, 31 Dec 9999 23:59:59 GMT";
}

function deleteCookie(name)
{
    document.cookie = encodeURIComponent(name) + "=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
}

function createAutoCompleteFields() {
    var autocompleteFields = $('[data-easyadmin-autocomplete-url]');

    autocompleteFields.each(function () {
        var $this = $(this),
            url = $this.data('easyadmin-autocomplete-url');

        $this.select2({
            theme: 'bootstrap',
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { 'query': params.term, 'page': params.page };
                },
                // to indicate that infinite scrolling can be used
                processResults: function (data, params) {
                    return {
                        results: data.results,
                        pagination: {
                            more: data.has_next_page
                        }
                    };
                },
                cache: true
            },
            placeholder: '',
            allowClear: true,
            minimumInputLength: 1
        });
    });
}
