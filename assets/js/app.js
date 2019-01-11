// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.scss');

global.$ = global.jQuery = require('jquery');

// Imports only the Bootstrap JS components used by default in the backend.
// If you develop features that need other Bootstrap components, check out the
// bootstrap-all.js file that provides the rest of the Bootstrap JS plugins
import 'bootstrap/js/src/modal.js';
import 'bootstrap/js/src/tab.js';
import 'bootstrap/js/src/tooltip.js';
import 'bootstrap/js/src/popover.js';

import './adminlte.js';
import 'jquery.are-you-sure';
import 'featherlight';
import 'jquery-highlight';
import 'select2';

$(function () {
    $('.main-sidebar-collapse').on('click', toggleNavigation);
    $('[data-toggle="popover"]').popover();
    createNullableControls();
    createAutoCompleteFields();
    $(document).on('easyadmin.collection.item-added', createAutoCompleteFields);
});

function toggleNavigation() {
    // Toggle main-sidebar state (VanillaJS)
    const sideBar = document.querySelector('#main-sidebar');
    if (sideBar) {
      sideBar.classList.toggle('collapsed');
    }
    // Store main-sidebar state in a cookie
    var cookieName = '_easyadmin_navigation_iscollapsed';
    if ($('#main-sidebar').hasClass('collapsed')) {
        document.cookie = encodeURIComponent(cookieName) + "=" + encodeURIComponent(true) + "; path=/; expires=Fri, 31 Dec 9999 23:59:59 GMT";
    } else {
        document.cookie = encodeURIComponent(cookieName) + "=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
    }
}

function createNullableControls() {
    var fnNullDates = function() {
        var checkbox = $(this);

        checkbox.closest('.form-group').find('select, input[type="date"], input[type="time"], input[type="datetime-local"]').each(function() {
            var formFieldIsDisabled = checkbox.is(':checked');
            $(this).prop('disabled', formFieldIsDisabled);

            if (formFieldIsDisabled) {
                $(this).closest('.datetime-widget').slideUp({ duration: 200 });
            } else {
                $(this).closest('.datetime-widget').slideDown({ duration: 200 });
            }
        });
    };

    $('.nullable-control :checkbox').bind('change', fnNullDates).each(fnNullDates);
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
