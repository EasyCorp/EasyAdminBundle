$(function () {
    var body = $('body'),
        batchActionsAvaiable = !!body.find('.js-batch-actions').length;

    body
        .on('expanded.pushMenu', toggleNavigation(false))
        .on('collapsed.pushMenu', toggleNavigation(true))
    ;

    if (batchActionsAvaiable) {
        initBatchActions();
    }

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
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + "; expires=Fri, 31 Dec 9999 23:59:59 GMT";
}

function deleteCookie(name)
{
    document.cookie = encodeURIComponent(name) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
}

// thanks to http://stackoverflow.com/a/901144/2780840
function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function initBatchActions() {
    var selectedRecords = [], // keeps selected record id's
        selectAll = false, // if its true, that means all records selected
        allToggled = false, // the state of toggling all
        $main = $('#main'),
        $records = $main.find('.js-is-selected'),
        $message = $main.find('.js-batch-message');

    // show message on the target element
    var showMessage = function(message) {
        $message.html(message || '');
    };

    // shows selected element count
    var showSelectedCount = function() {
        var message = selectedRecords.length + ' records selected.';

        if ( ! selectedRecords.length) {
            message = '';
        }

        showMessage(message);
    };

    // gets id of the record from checkbox element
    var getIdFromCheckbox = function(element) {
        return element.closest('tr[data-id]').data('id');
    };

    // toggles given id
    var toggleSelected = function(id, remove) {
        var idx = selectedRecords.indexOf(id);

        if ('undefined' === typeof remove) {
            remove = true;
        }

        if (-1 === idx) {
            selectedRecords.push(id);
        } else if (remove) {
            selectedRecords.splice(idx, 1);
        }
    };

    // toggles all records
    var toggleAll = function(active) {
        if ('undefined' === typeof active) {
            selectAll = false; // when all elements toggled, selectAll became false

            active = allToggled = ! allToggled;
        }

        if (active) {
            $records
                .each(function() {
                    var $this = $(this);

                    $this.prop('checked', true);

                    toggleSelected(
                        getIdFromCheckbox($this),
                        false
                    );
                })
            ;
        } else {
            selectedRecords = [];

            $records.prop('checked', false);
        }

        showSelectedCount();
    };

    // listen checkboxes change event, and toggle them in selectedRecords array
    $main
        .find('.js-is-selected')
        .on('change', function() {
            selectAll = false; // when any element changed, selectAll became false

            toggleSelected(
                getIdFromCheckbox($(this))
            );

            showSelectedCount();

            // if all checkbox became unchecked, make false the allToggled
            if ( ! selectedRecords.length) {
                allToggled = false
            }
        })
    ;

    // listen toggle all button, and toggle all records
    $main
        .find('.js-toggle-all')
        .on('click', function() {
            toggleAll();
        })
    ;

    // listen select all button
    $main
        .find('.js-select-all')
        .on('click', function() {
            selectAll = allToggled = true;

            toggleAll(true);

            showMessage('All records selected.');
        })
    ;

    // listen batch action's click event
    $('.js-batch-action')
        .on('click', function() {
            if ( ! selectAll && ! selectedRecords.length) {
                alert('You need to select at least one record for applying batch actions.');

                return;
            }

            $action = $(this).data('action-name');

            var params = {
                entity: getParameterByName('entity'),
                action: 'process',
                batch_action: $action
            };

            if (selectAll) {
                params.data = 'all';
            } else {
                params.data = selectedRecords;
            }

            location.search = decodeURIComponent($.param(params));
        })
    ;
}
