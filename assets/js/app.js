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
import 'bootstrap/js/src/dropdown.js';

import './adminlte.js';
import 'jquery.are-you-sure';
import 'featherlight';
import 'jquery-highlight';
import 'select2';

window.addEventListener('load', function() {
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="dropdown"]').dropdown();
    createNullableControls();
    createAutoCompleteFields();
    $(document).on('easyadmin.collection.item-added', createAutoCompleteFields);
    createContentResizer();
    createNavigationToggler();
});

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

function createContentResizer() {
    const sidebarResizerHandler = document.getElementById('sidebar-resizer-handler');
    sidebarResizerHandler.addEventListener('click', function() {
        const oldValue = localStorage.getItem('easyadmin/sidebar/width') || 'normal';
        const newValue = 'normal' == oldValue ? 'compact' : 'normal';

        document.querySelector('body').classList.remove('easyadmin-sidebar-width-' + oldValue);
        document.querySelector('body').classList.add('easyadmin-sidebar-width-' + newValue);
        localStorage.setItem('easyadmin/sidebar/width', newValue);
    });

    const contentResizerHandler = document.getElementById('content-resizer-handler');
    contentResizerHandler.addEventListener('click', function() {
        const oldValue = localStorage.getItem('easyadmin/content/width') || 'normal';
        const newValue = 'normal' == oldValue ? 'full' : 'normal';

        document.querySelector('body').classList.remove('easyadmin-content-width-' + oldValue);
        document.querySelector('body').classList.add('easyadmin-content-width-' + newValue);
        localStorage.setItem('easyadmin/content/width', newValue);
    });
}

function createNavigationToggler() {
    const toggler = document.getElementById('navigation-toggler');
    const cssClassName = 'easyadmin-mobile-sidebar-visible';
    let modalBackdrop;

    toggler.addEventListener('click', function() {
        document.querySelector('body').classList.toggle(cssClassName);

        if (document.querySelector('body').classList.contains(cssClassName)) {
            modalBackdrop = document.createElement('div');
            modalBackdrop.classList.add('modal-backdrop', 'fade', 'show');
            modalBackdrop.onclick = function() {
                document.querySelector('body').classList.remove(cssClassName);
                document.body.removeChild(modalBackdrop);
                modalBackdrop = null;
            };

            document.body.appendChild(modalBackdrop);
        } else if (modalBackdrop) {
            document.body.removeChild(modalBackdrop);
            modalBackdrop = null;
        }
    });
}
