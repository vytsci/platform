define(function(require) {
    'use strict';

    var StickedScrollbarPlugin;
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var viewportManager = require('oroui/js/viewport-manager');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    require('jquery.mousewheel');
    require('styled-scroll-bar');

    StickedScrollbarPlugin = BasePlugin.extend({
        viewport: {
            minScreenType: 'any'
        },

        domCache: null,

        /**
         * @inheritDoc
         */
        initialize: function(grid, options) {
            _.extend(this, _.pick(options || {}, ['viewport']));
            this.grid = grid;
            this.listenTo(this.grid, 'shown', this.enable);
            this.listenTo(mediator, 'viewport:change', this.onViewportChange);

            return StickedScrollbarPlugin.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        eventNamespace: function() {
            return StickedScrollbarPlugin.__super__.eventNamespace.apply(this, arguments) + '.stickedScrollbar';
        },

        /**
         * @inheritDoc
         */
        enable: function() {
            if (this.enabled || !this.grid.rendered || !this.isApplicable(viewportManager.getViewport())) {
                return;
            }

            this.setupDomCache();

            this.domCache.$container.styledScrollBar({
                overflowBehavior: {
                    y: 'hidden'
                },
                callbacks: {
                    onScroll: _.debounce(function(event) {
                        this.domCache.$container.trigger('updateScroll', event);
                    }.bind(this), 5)
                }
            });
            this.domCache.$scrollbar = $(this.domCache.$container
                .styledScrollBar('getElements').scrollbarHorizontal.scrollbar);

            this.delegateEvents();

            var displayScrollbar = this.checkScrollbarDisplay();

            this.domCache.$container.styledScrollBar(displayScrollbar ? 'update': 'sleep');

            StickedScrollbarPlugin.__super__.enable.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        disable: function() {
            if (!this.enabled) {
                return;
            }

            this.undelegateEvents();
            this.domCache.$container.styledScrollBar('dispose');

            return StickedScrollbarPlugin.__super__.disable.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disable();
            delete this.domCache;

            return StickedScrollbarPlugin.__super__.dispose.apply(this, arguments);
        },

        setupDomCache: function() {
            this.domCache = {
                $window: $(window),
                $document: $(document),
                $grid: this.grid.$grid,
                $container: this.grid.$grid.parents('.grid-scrollable-container:first'),
                $spyScroll: this.grid.$grid.parents('[data-spy="scroll"]:first'),
                $oroTabs: this.grid.$grid.parents('.oro-tabs:first'),
                $collapsible: this.grid.$grid.parents('.collapse:first'),
                $thead: this.grid.$grid.find('thead:first')
            };
        },

        delegateEvents: function() {
            var manageScroll = _.bind(this.manageScroll, this);
            var updateCustomScrollbar = _.debounce(_.bind(this.updateCustomScrollbar, this), 50);

            /*
            * For cases, when layout has full screen container with own scrollbar and window doesn't have scrollbar
            */
            this.domCache.$spyScroll.on('scroll' + this.eventNamespace(), manageScroll);
            /*
            * For cases when grid is inside collapsible block
            */
            this.domCache.$collapsible.on('hidden' + this.eventNamespace(), manageScroll);
            this.domCache.$collapsible.on('shown' + this.eventNamespace(), manageScroll);
            this.domCache.$document.on('scroll' + this.eventNamespace(), manageScroll);
            this.domCache.$window.on('resize' + this.eventNamespace(), updateCustomScrollbar);
            this.domCache.$oroTabs.on('shown' + this.eventNamespace(), updateCustomScrollbar);

            this.listenTo(mediator, 'layout:reposition', this.updateCustomScrollbar);
            this.listenTo(mediator, 'gridHeaderCellWidth:beforeUpdate', this.onGridHeaderCellWidthBeforeUpdate);
            this.listenTo(mediator, 'gridHeaderCellWidth:updated', this.updateCustomScrollbar);
            this.listenTo(this.grid, 'content:update', this.updateCustomScrollbar);
        },

        undelegateEvents: function() {
            _.each(this.domCache, function($element) {
                $element.off(this.eventNamespace());
            }, this);

            this.stopListening();
            // Need reenable event for wake up plugin
            mediator.on('viewport:change', this.onViewportChange, this);
        },

        manageScroll: function() {
            if (!this.checkScrollbarDisplay()) {
                return;
            }

            if (!this.inViewport()) {
                this.detachScrollbar();
            } else {
                this.attachScrollbar();
            }
        },

        checkScrollbarDisplay: function() {
            var $grid = this.domCache.$grid;
            var $container = this.domCache.$container;
            var display = $grid.width() > $container.width();

            if (display && this.isGridHiddenUnderCollapse()) {
                display = false;
                this.domCache.$scrollbar.css('display', 'none');
            }

            return display;
        },

        isGridHiddenUnderCollapse: function() {
            return _.some(this.domCache.$grid.parents(), function(el) {
                return $(el).height() === 0;
            });
        },

        inViewport: function() {
            var containerOffsetTop = this.domCache.$container.offset().top;
            var containerHeight = this.domCache.$container.height();
            var windowHeight = this.domCache.$window.height();
            var windowScrollTop = this.domCache.$window.scrollTop();
            var tHeadHeight = this.domCache.$thead.height();
            var scrollBarHeight = this.domCache.$scrollbar.height();

            var viewportTop = containerOffsetTop - windowScrollTop;
            var viewportBottom = windowHeight - viewportTop - containerHeight;
            var viewportLowLevel = windowHeight + windowScrollTop - tHeadHeight - scrollBarHeight;

            return viewportBottom > 0 || viewportLowLevel < containerOffsetTop;
        },

        isApplicable: function(viewport) {
            return viewport.isApplicable(this.viewport);
        },

        attachScrollbar: function() {
            this.domCache.$scrollbar.removeAttr('style');
        },

        detachScrollbar: function() {
            var $scrollbar = this.domCache.$scrollbar;
            var containerWidth = this.domCache.$container.width();
            var containerLeftOffset = this.domCache.$container.offset().left;
            $scrollbar.removeAttr('style');

            $scrollbar.css({
                'position': 'fixed',
                'top': 'auto',
                'right': 'auto',
                'left': containerLeftOffset + 'px',
                'bottom': 0,
                'z-index': 999,
                'width': containerWidth + 'px'
            });
        },

        updateCustomScrollbar: function() {
            this.manageScroll();
            if (this.domCache.$container.data('oro.styledScrollBar')) {
                this.domCache.$container.styledScrollBar('update');
            }
        },

        onGridHeaderCellWidthBeforeUpdate: function() {
            if (this.domCache.$container.data('oro.styledScrollBar')) {
                this.domCache.$container.styledScrollBar('update');
            }
        },

        onViewportChange: function(viewport) {
            if (this.isApplicable(viewport)) {
                this.enable();
            } else {
                this.disable();
            }
        }
    });

    return StickedScrollbarPlugin;
});
