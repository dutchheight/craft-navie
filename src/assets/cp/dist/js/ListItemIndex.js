(function($){

if (typeof Craft.Navie === 'undefined') {
    Craft.Navie = {};
}

var elementTypeClass = 'dutchheight\\navie\\elements\\ListItem';

/**
 * List Item index class
 */
Craft.Navie.ListItemIndex = Craft.BaseElementIndex.extend({

    lists: null,
    $newListItemBtnGroup: null,
    $newListItemBtn: null,

    afterInit: function() {
        // Find which lists are being shown as sources
        this.lists = [];

        for (var i = 0; i < this.$sources.length; i++) {
            var $source = this.$sources.eq(i),
                key = $source.data('key'),
                match = key.match(/^list:(.+)$/);

            if (match) {
                this.lists.push({
                    handle: $source.data('handle'),
                    id: parseInt(1),
                    name: $source.text(),
                    uid: match[1],
                });
            }
        }

        this.on('selectSource', $.proxy(this, 'updateButton'));
        this.on('selectSite', $.proxy(this, 'updateButton'));
        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific list in the URL?
        if (this.settings.context === 'index' && typeof defaultListHandle !== 'undefined') {
            for (var i = 0; i < this.$sources.length; i++) {
                var $source = $(this.$sources[i]);

                if ($source.data('handle') === defaultListHandle) {
                    return $source.data('key');
                }
            }
        }

        return this.base();
    },

    updateButton: function() {
        if (!this.$source) {
            return;
        }

        // Get the handle of the selected source
        var selectedSourceHandle = this.$source.data('handle');

        // Update the New List Item button
        // ---------------------------------------------------------------------

        if (this.lists.length) {
            // Remove the old button, if there is one
            if (this.$newListItemBtnGroup) {
                this.$newListItemBtnGroup.remove();
            }

            // Are they viewing a list source?
            var selectedList;
            if (selectedSourceHandle) {
                for (var i = 0; i < this.lists.length; i++) {
                    if (this.lists[i].handle === selectedSourceHandle) {
                        selectedList = this.lists[i];
                        break;
                    }
                }
            }

            this.$newListItemBtnGroup = $('<div class="btngroup submit"/>');
            var $menuBtn;

            // If they are, show a primary "New list item" button, and a dropdown of the other lists (if any).
            // Otherwise only show a menu button
            if (selectedList) {
                var href = this._getListTriggerHref(selectedList),
                    label = (this.settings.context === 'index' ? Craft.t('navie', 'New list item') : Craft.t('navie', 'New {list} list item', { list: selectedList.name }));
                this.$newListItemBtn = $('<a class="btn submit add icon" ' + href + '>' + label + '</a>').appendTo(this.$newListItemBtnGroup);

                if (this.settings.context !== 'index') {
                    this.addListener(this.$newListItemBtn, 'click', function(ev) {
                        this._openCreateListItemModal(ev.currentTarget.getAttribute('data-id'));
                    });
                }

                if (this.lists.length > 1) {
                    $menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newListItemBtnGroup);
                }
            } else {
                this.$newListItemBtn = $menuBtn = $('<div class="btn submit add icon menubtn">' + Craft.t('navie', 'New list item') + '</div>').appendTo(this.$newListItemBtnGroup);
            }

            if ($menuBtn) {
                var menuHtml = '<div class="menu"><ul>';

                for (var i = 0; i < this.lists.length; i++) {
                    var list = this.lists[i];

                    if (this.settings.context === 'index' || list !== selectedList) {
                        var href = this._getListTriggerHref(list),
                            label = (this.settings.context === 'index' ? list.name : Craft.t('navie', 'New {list} list item', { list: list.name }));
                        menuHtml += '<li><a ' + href + '">' + label + '</a></li>';
                    }
                }

                menuHtml += '</ul></div>';

                $(menuHtml).appendTo(this.$newListItemBtnGroup);
                var menuBtn = new Garnish.MenuBtn($menuBtn);

                if (this.settings.context !== 'index') {
                    menuBtn.on('optionSelect', $.proxy(function(ev) {
                        this._openCreateListItemModal(ev.option.getAttribute('data-id'));
                    }, this));
                }
            }

            this.addButton(this.$newListItemBtnGroup);
        }

        // Update the URL if we're on the List Items index
        // ---------------------------------------------------------------------

        if (this.settings.context === 'index' && typeof history !== 'undefined') {
            var uri = 'navie';

            if (selectedSourceHandle) {
                uri += '/' + selectedSourceHandle;
            }

            history.replaceState({}, '', Craft.getUrl(uri));
        }
    },

    _getListTriggerHref: function(list)
    {
        if (this.settings.context === 'index') {
            var uri = 'navie/' + list.handle + '/new';

            if (this.siteId && this.siteId != Craft.primarySiteId) {
                for (var i = 0; i < Craft.sites.length; i++) {
                    if (Craft.sites[i].id == this.siteId) {
                        uri += '/' + Craft.sites[i].handle;
                    }
                }
            }
            return 'href="' + Craft.getUrl(uri) + '"';
        } else {
            return 'data-id="' + list.id + '"';
        }
    },

    _openCreateListItemModal: function(listId)
    {
        if (this.$newListItemBtn.hasClass('loading')) {
            return;
        }

        // Find the list
        var list;

        for (var i = 0; i < this.lists.length; i++) {
            if (this.lists[i].id === listId) {
                list = this.lists[i];
                break;
            }
        }

        if (!list) {
            return;
        }

        this.$newListItemBtn.addClass('inactive');
        var newListItemBtnText = this.$newListItemBtn.text();
        this.$newListItemBtn.text(Craft.t('navie', 'New {list} list item', { list: list.name }));

        new Craft.ElementEditor({
            hudTrigger: this.$newListItemBtnGroup,
            elementType: elementTypeClass,
            locale: this.locale,
            attributes: {
                listId: listId
            },
            onBeginLoading: $.proxy(function() {
                this.$newListItemBtn.addClass('loading');
            }, this),
            onEndLoading: $.proxy(function() {
                this.$newListItemBtn.removeClass('loading');
            }, this),
            onHideHud: $.proxy(function() {
                this.$newListItemBtn.removeClass('inactive').text(newListItemBtnText);
            }, this),
            onSaveElement: $.proxy(function(response) {
                // Make sure the right list is selected
                var listSourceKey = 'list:' + list.uid;

                if (this.sourceKey !== listSourceKey) {
                    this.selectSourceByKey(listSourceKey);
                }

                this.selectElementAfterUpdate(response.id);
                this.updateElements();
            }, this)
        });
    }
});

// Register it!
try {
    Craft.registerElementIndexClass(elementTypeClass, Craft.Navie.ListItemIndex);
}
catch (e) {
    // Already registered
}

})(jQuery);
