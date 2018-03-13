$(function () {
    var body = $('body');

    body
        .on('expanded.pushMenu', toggleNavigation(false))
        .on('collapsed.pushMenu', toggleNavigation(true))
    ;

    createNullableControls();
    createAutoCompleteFields();
    $(document).on('easyadmin.collection.item-added', createAutoCompleteFields);

    // Entity Type
    $(document).on('click', '.easyadmin-entity-new-btn, .easyadmin-entity-edit-btn', function (e) {
        e.preventDefault();
        showAdminPopup(this);
    });
    $(document).on('change', 'select.easyadmin-entity-actions', function (e) {
        var $this = $(this);
        var $edit_btn = $this.closest('.input-group').find('.easyadmin-entity-edit-btn');
        var edit_url = $this.attr('data-easyadmin-edit-url');
        if (!edit_url) {
            return;
        }
        // Update edit url on change value
        $this.attr('data-easyadmin-edit-url', edit_url.replace(/(\?\w+=)\d+(&)/g, '$1' + (this.value || '0') + '$2'));
        if (this.value) {
            $edit_btn.removeClass('hide');
        } else {
            $edit_btn.addClass('hide');
        }
    });
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

function showAdminPopup(btn) {
    var id = $(btn).data('target');
    var action = $(btn).data('action');
    var url = $('.easyadmin-entity-actions[data-uniqueid=' + id + ']').attr('data-easyadmin-' + action + '-url');
    var win = window.open(url, id, 'width=800,height=600,resizable=yes,scrollbars=yes');
    win.focus();

    return false;
}

function dismissAdminPopup(win, action, value, label) {
    var id = win.name;
    var $elem = $('.easyadmin-entity-actions[data-uniqueid="' + id + '"]');
    if ($elem.length && $elem[0].tagName === 'SELECT') {
        switch (action) {
            case 'new':
                $elem[0].options[$elem[0].options.length] = new Option(label, value, true, true);
                $elem.trigger('change');
                break;
            case 'edit':
                $elem.find('option').each(function() {
                    if (this.value == value) {
                        this.textContent = label;
                    }
                });
                $elem.next().find('.select2-selection__rendered').each(function() {
                    // The element can have a clear button as a child.
                    // Use the lastChild to modify only the displayed value.
                    this.lastChild.textContent = label;
                    this.title = label;
                });
                break;
        }
    }
    win.close();
}

window.showAdminPopup = showAdminPopup;
window.dismissAdminPopup = dismissAdminPopup;
