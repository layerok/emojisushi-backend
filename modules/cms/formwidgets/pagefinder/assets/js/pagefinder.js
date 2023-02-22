/*
 * PageFinder page
 */
'use strict';

oc.Module.register('cms.formwidget.pagefinder', function() {
    class PageFinderFormWidget extends oc.FoundationPlugin
    {
        constructor(element, config) {
            super(element, config);

            this.$dataLocker = document.querySelector(this.config.dataLocker);

            oc.Events.on(this.element, 'click', '.toolbar-find-button', this.proxy(this.onClickFindButton));
            oc.Events.on(this.element, 'dblclick', this.proxy(this.onDoubleClick));
            this.markDisposable();
        }

        dispose() {
            oc.Events.off(this.element, 'click', '.toolbar-find-button', this.proxy(this.onClickFindButton));
            oc.Events.off(this.element, 'dblclick', this.proxy(this.onDoubleClick));
            super.dispose();
        }

        static get DATANAME() {
            return 'ocPageFinderFormWidget';
        }

        static get DEFAULTS() {
            return {
                dataLocker: null,
                refreshHandler: null,
                singleMode: false
            }
        }

        onDoubleClick = function() {
            this.element.querySelector('.toolbar-find-button').click();
        }

        onInsertPage(item) {
            if (!this.$dataLocker) {
                return;
            }

            this.$dataLocker.value = item.link;

            if (!this.config.refreshHandler) {
                return;
            }

            oc.request(this.element, this.config.refreshHandler, {
                afterUpdate: function() {
                    $(this.$dataLocker).trigger('change');
                }
            });
        }

        onClickFindButton(ev) {
            var currentValue = this.$dataLocker ? this.$dataLocker.value : '';

            oc.pageLookup.popup({
                alias: 'ocpagelookup',
                value: currentValue,
                onInsert: this.proxy(this.onInsertPage),
                includeTitle: true,
                singleMode: this.config.singleMode
            });
        }
    }

    addEventListener('render', function() {
        document.querySelectorAll('[data-control=pagefinder]').forEach(function(el) {
            PageFinderFormWidget.getOrCreateInstance(el);
        });
    });
});
