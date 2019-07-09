// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.scss');

global.$ = global.jQuery = require('jquery');

import 'bootstrap/js/src/index.js';

import './adminlte.js';
import 'jquery.are-you-sure';
import 'featherlight';
import 'jquery-highlight';
import 'select2';

window.addEventListener('load', function() {
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="dropdown"]').dropdown();
    $('.collapse').collapse();
    createNullableControls();
    createAutoCompleteFields();
    $(document).on('easyadmin.collection.item-added', createAutoCompleteFields);
    createContentResizer();
    createNavigationToggler();
    createCodeEditorFields();
    createTextEditorFields();
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
    const contentResizerHandler = document.getElementById('content-resizer-handler');

    if (null !== sidebarResizerHandler) {
        sidebarResizerHandler.addEventListener('click', function() {
            const oldValue = localStorage.getItem('easyadmin/sidebar/width') || 'normal';
            const newValue = 'normal' == oldValue ? 'compact' : 'normal';

            document.querySelector('body').classList.remove('easyadmin-sidebar-width-' + oldValue);
            document.querySelector('body').classList.add('easyadmin-sidebar-width-' + newValue);
            localStorage.setItem('easyadmin/sidebar/width', newValue);
        });
    }

    if (null !== contentResizerHandler) {
        contentResizerHandler.addEventListener('click', function() {
            const oldValue = localStorage.getItem('easyadmin/content/width') || 'normal';
            const newValue = 'normal' == oldValue ? 'full' : 'normal';

            document.querySelector('body').classList.remove('easyadmin-content-width-' + oldValue);
            document.querySelector('body').classList.add('easyadmin-content-width-' + newValue);
            localStorage.setItem('easyadmin/content/width', newValue);
        });
    }
}

function createNavigationToggler() {
    const toggler = document.getElementById('navigation-toggler');
    const cssClassName = 'easyadmin-mobile-sidebar-visible';
    let modalBackdrop;

    if (null === toggler) {
        return;
    }

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

// Code editor fields require extra JavaScript dependencies, which are loaded
// dynamically only when there are code editor fields in the page
function createCodeEditorFields()
{
    const codeEditorElements = document.querySelectorAll('[data-easyadmin-code-editor]');
    if (codeEditorElements.length === 0) {
        return;
    }

    const codeEditorJs = document.createElement('script');
    codeEditorJs.setAttribute('src', codeEditorElements[0].dataset.jsUrl);

    const codeEditorCss = document.createElement('link');
    codeEditorCss.setAttribute('rel', 'stylesheet');
    codeEditorCss.setAttribute('href', codeEditorElements[0].dataset.cssUrl);

    document.querySelector('head').appendChild(codeEditorCss);
    document.querySelector('body').appendChild(codeEditorJs);
}

// Text editor fields require extra JavaScript dependencies, which are loaded
// dynamically only when there are code editor fields in the page
function createTextEditorFields()
{
    const textEditorElements = document.querySelectorAll('trix-editor');
    if (textEditorElements.length === 0) {
        return;
    }

    const textEditorJs = document.createElement('script');
    textEditorJs.setAttribute('src', textEditorElements[0].dataset.jsUrl);

    const textEditorCss = document.createElement('link');
    textEditorCss.setAttribute('rel', 'stylesheet');
    textEditorCss.setAttribute('href', textEditorElements[0].dataset.cssUrl);

    document.querySelector('head').appendChild(textEditorCss);
    document.querySelector('body').appendChild(textEditorJs);
}
