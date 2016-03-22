/*
 *  jquery-boilerplate - v4.0.0
 *  A jump-start for jQuery plugins development.
 *  http://jqueryboilerplate.com
 *
 *  Made by Zeno Rocha
 *  Under MIT License
 */
/**
 * easyAdminStickyFooter
 * A wrapper over Waypoints.js (http://imakewebthings.com/waypoints/) to handle
 * skticky footers on EasyAdminBundle with AdminLTE theme
 */
;
(function ($, window, document, undefined) {

    "use strict";

    var pluginName = "easyAdminStickyFooter",
            defaults = {
                stickyClass: 'footer-stuck',
                placeholderClass: 'sticky-footer-placeholder',
                sidebarDuration: 300,
                bottomOffset: 15
            };

    function Plugin(element, options) {
        this.element = element;
        this.settings = $.extend({}, defaults);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    $.extend(Plugin.prototype, {
        init: function () {
            this._createPlaceholder();
            this._initWaypoint();
            this._bindWindowResizeEvent();
            this._bindAdminLteSidebarEvents();
            this._bindCkEditorEvent();
        },
        _createPlaceholder: function() {
            this.$placeholder = $('<div>')
                    .attr('class', $(this.element).attr('class'))
                    .removeClass(this.settings.stickyClass)
                    .addClass(this.settings.placeholderClass)
                    .insertAfter(this.element);
        },
        _initWaypoint: function() {
            var that = this;
            this.waypoint = new Waypoint({
                element: that.$placeholder[0],
                offset: function() {
                    return this.context.innerHeight() - this.adapter.outerHeight() + that.settings.bottomOffset;
                },
                handler: function(direction) {
                    var $element = $(that.element);
                    
                    that.$placeholder.height(0);
                    
                    $element
                        .removeClass(that.settings.stickyClass)
                        .css('width', '100%');
                    
                    if ('up' === direction) {
                        that._createStickyFooter();
                    }
                }
            });
            if ($(window).height() > that.waypoint.triggerPoint) {
                that._createStickyFooter();
            }
        },
        _bindAdminLteSidebarEvents: function() {
            var that = this;
            $(document).on('collapsed.pushMenu expanded.pushMenu', function() {
                setTimeout(function() {
                    that._refreshWaypointAndWidth();
                }, that.settings.sidebarDuration);
            });
        },
        _bindWindowResizeEvent: function() {
            var that = this;
            $(window).on('resize', function() {
                var fnResize = function() {
                    setTimeout(function() {
                        that._refreshWaypointAndWidth();
                    }, that.settings.sidebarDuration);
                };

                that._debounce(fnResize(), 250);
            });
        },
        _bindCkEditorEvent: function() {
            if (typeof CKEDITOR !== 'undefined') {
                var that = this;
                CKEDITOR.on("instanceReady", function(event) {
                    that.waypoint.context.refresh();
                });
            }
        },
        _createStickyFooter: function() {
            var $element = $(this.element);
            this.$placeholder.height($element.height());
                
            $element
                .addClass(this.settings.stickyClass)
                .width(this.$placeholder.width());
        },
        _refreshWaypointAndWidth: function() {
            this.waypoint.context.refresh();
            $(this.element).width(this.$placeholder.width());
        },
        // http://davidwalsh.name/javascript-debounce-function
        _debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    });

    $.fn[ pluginName ] = function (options) {
        return this.each(function () {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new Plugin(this, options));
            }
        });
    };

})(jQuery, window, document);