(function ($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.LinkTypeSwitcher = Garnish.Base.extend(
        {
            $typeSelect: null,
            $spinner: null,
            $container: null,

            init: function () {
                this.$typeSelect = $('#linkType');
                this.$container = $('#link-types');

                if ($('.spinner').length) {
                    this.$spinner = $('.spinner');
                } else {
                    this.$spinner = $('<div class="spinner hidden"/>').insertAfter(this.$typeSelect.parent());
                }

                this.addListener(this.$typeSelect, 'change', 'onTypeChange');
            },

            onTypeChange: function (ev) {
                this.$spinner.removeClass('hidden');

                Craft.postActionRequest('navie/lists/switch-link-type', Craft.cp.$primaryForm.serialize(), $.proxy(function(response, textStatus) {
                    this.$spinner.addClass('hidden');

                    if (textStatus === 'success') {
                        this.trigger('beforeTypeChange');

                        this.$container.html(response.fieldsHtml);
                        Craft.initUiElements(this.$container);
                        Craft.appendHeadHtml(response.headHtml);
                        Craft.appendFootHtml(response.bodyHtml);

                        this.trigger('typeChange');
                    }
                }, this));
            }

        });
})(jQuery);
