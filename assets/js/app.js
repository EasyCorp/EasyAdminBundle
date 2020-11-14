// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.scss');

global.$ = global.jQuery = require('jquery');

import 'bootstrap';

import './adminlte.js';
import 'jquery.are-you-sure';
import 'featherlight';
import 'jquery-highlight';
import 'select2';

window.addEventListener('load', function() {
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
    createNullableControls();

    createAutoCompleteFields();
    document.addEventListener('ea.collection.item-added', createAutoCompleteFields);

    createContentResizer();
    createNavigationToggler();
    createFileUploadFields();
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
    var autocompleteFields = $('[data-widget="select2"]:not(.select2-hidden-accessible)');

    autocompleteFields.each(function () {
        var $this = $(this);
        var autocompleteUrl = $this.data('ea-autocomplete-endpoint-url');
        var allowClear = $this.data('allow-clear');
        var escapeMarkup = $this.data('ea-escape-markup');

        if (undefined === autocompleteUrl) {
            var options = {
                theme: 'bootstrap',
                placeholder: '',
                allowClear: true
            };

            if (false === escapeMarkup) {
                options.escapeMarkup = function(markup) { return markup; };
            }

            $this.select2(options);
        } else {
            $this.select2({
                theme: 'bootstrap',
                ajax: {
                    url: autocompleteUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { 'query': params.term, 'page': params.page };
                    },
                    // to indicate that infinite scrolling can be used
                    processResults: function (data, params) {
                        return {
                            results: $.map(data.results, function(result) {
                                return { id: result.entityId, text: result.entityAsString };
                            }),
                            pagination: {
                                more: data.has_next_page
                            }
                        };
                    },
                    cache: true
                },
                allowClear: allowClear,
                minimumInputLength: 1
            });
        }
    });
}

function createContentResizer() {
    const sidebarResizerHandler = document.getElementById('sidebar-resizer-handler');
    const contentResizerHandler = document.getElementById('content-resizer-handler');

    if (null !== sidebarResizerHandler) {
        sidebarResizerHandler.addEventListener('click', function() {
            const oldValue = localStorage.getItem('ea/sidebar/width') || 'normal';
            const newValue = 'normal' == oldValue ? 'compact' : 'normal';

            document.querySelector('body').classList.remove('ea-sidebar-width-' + oldValue);
            document.querySelector('body').classList.add('ea-sidebar-width-' + newValue);
            localStorage.setItem('ea/sidebar/width', newValue);
        });
    }

    if (null !== contentResizerHandler) {
        contentResizerHandler.addEventListener('click', function() {
            const oldValue = localStorage.getItem('ea/content/width') || 'normal';
            const newValue = 'normal' == oldValue ? 'full' : 'normal';

            document.querySelector('body').classList.remove('ea-content-width-' + oldValue);
            document.querySelector('body').classList.add('ea-content-width-' + newValue);
            localStorage.setItem('ea/content/width', newValue);
        });
    }
}

function createNavigationToggler() {
    const toggler = document.getElementById('navigation-toggler');
    const cssClassName = 'ea-mobile-sidebar-visible';
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

function createFileUploadFields()
{
    function fileSize(bytes) {
        const size = ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        const factor = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));

        return parseInt(bytes / (1024 ** factor)) + size[factor];
    }

    $(document).on('change', '.ea-fileupload input[type=file].custom-file-input', function () {
        if (this.files.length === 0) {
            return;
        }


        let filename = '';
        if (this.files.length === 1) {
            filename = this.files[0].name;
        } else {
            filename = this.files.length + ' ' + $(this).data('files-label');
        }
        let bytes = 0;
        for (let i = 0; i < this.files.length; i++) {
            bytes += this.files[i].size;
        }

        const container = $(this).closest('.ea-fileupload');
        container.find('.custom-file-label').text(filename);
        container.find('.input-group-text').text(fileSize(bytes)).show();
        container.find('.ea-fileupload-delete-btn').show();
    });

    $(document).on('click', '.ea-fileupload .ea-fileupload-delete-btn', function () {
        const container = $(this).closest('.ea-fileupload');
        container.find('input').val('').removeAttr('title');
        container.find('.custom-file-label').text('');
        container.find('.input-group-text').text('').hide();
        container.find('.fileupload-list').hide();
        $(this).hide();
    });
}
