/**
 * ACS Agenda Manager - Admin JavaScript
 *
 * @package ACSAgendaManager
 */

(function ($) {
    'use strict';

    const ACSAgendaAdmin = {
        /**
         * Initialize admin functionality
         */
        init: function () {
            this.cacheElements();
            this.bindEvents();
            this.initDialogs();
            this.initPlacesAutocomplete();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function () {
            this.$spinner = $('.spinner2');
            this.$eventForm = $('#acs-event-form');
            this.$eventDialog = $('#acs-event-dialog');
            this.$deleteDialog = $('#acs-delete-dialog');
            this.$helpDialog = $('#acs-help-dialog');
            this.$notices = $('#acs-admin-notices');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            // Add event button
            $('#acs-add-event').on('click', this.openAddDialog.bind(this));

            // Edit event button
            $(document).on('click', '.editItems', this.openEditDialog.bind(this));

            // Delete event button
            $(document).on('click', '.ACSdelete', this.confirmDelete.bind(this));

            // Help button
            $('#acs-show-help').on('click', this.showHelp.bind(this));

            // Image upload button
            $(document).on('click', '.acs-upload-image', this.openMediaLibrary.bind(this));

            // Calendar button
            $(document).on('click', '.acs-open-calendar', this.openCalendar.bind(this));

            // Read more / hide buttons
            $(document).on('click', '.read_more, .hide_more', this.toggleDescription.bind(this));

            // Event filter change
            $('.ewc-filter-event').on('change', this.filterEvents.bind(this));
        },

        /**
         * Initialize Google Places Autocomplete
         */
        initPlacesAutocomplete: function () {
            if (!acsAgendaAdmin.hasGoogleMaps || typeof google === 'undefined') {
                return;
            }

            const self = this;
            const input = document.getElementById('event-emplacement');

            if (!input) {
                return;
            }

            // Wait for Google Maps API to load
            if (typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
                // Retry after a short delay
                setTimeout(function () {
                    self.initPlacesAutocomplete();
                }, 500);
                return;
            }

            try {
                const autocomplete = new google.maps.places.Autocomplete(input, {
                    types: ['establishment', 'geocode'],
                    fields: ['formatted_address', 'name', 'geometry'],
                });

                autocomplete.addListener('place_changed', function () {
                    const place = autocomplete.getPlace();
                    if (place.name && place.formatted_address) {
                        input.value = place.name + ', ' + place.formatted_address;
                    } else if (place.formatted_address) {
                        input.value = place.formatted_address;
                    }
                });
            } catch (e) {
                console.warn('Google Places Autocomplete initialization failed:', e);
            }
        },

        /**
         * Initialize jQuery UI dialogs
         */
        initDialogs: function () {
            // Event dialog
            this.$eventDialog.dialog({
                autoOpen: false,
                modal: true,
                width: 600,
                maxHeight: $(window).height() - 100,
                buttons: {},
            });

            // Delete confirmation dialog
            this.$deleteDialog.dialog({
                autoOpen: false,
                modal: true,
                width: 400,
                buttons: {},
            });

            // Help dialog
            this.$helpDialog.dialog({
                autoOpen: false,
                modal: true,
                width: 700,
                buttons: {
                    [acsAgendaAdmin.i18n.close]: function () {
                        $(this).dialog('close');
                    },
                },
            });
        },

        /**
         * Open add event dialog
         */
        openAddDialog: function (e) {
            e.preventDefault();

            this.resetForm();
            $('#event-action').val('add_item_agenda');

            this.$eventDialog.dialog('option', 'title', acsAgendaAdmin.i18n.addEvent);
            this.$eventDialog.dialog('option', 'buttons', {
                [acsAgendaAdmin.i18n.add]: this.submitEvent.bind(this),
                [acsAgendaAdmin.i18n.cancel]: function () {
                    $(this).dialog('close');
                },
            });

            this.$eventDialog.dialog('open');
        },

        /**
         * Open edit event dialog
         */
        openEditDialog: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const eventId = $button.data('id');

            this.resetForm();
            this.populateForm(eventId);

            $('#event-id').val(eventId);
            $('#event-action').val('update_agenda');

            this.$eventDialog.dialog('option', 'title', acsAgendaAdmin.i18n.editor);
            this.$eventDialog.dialog('option', 'buttons', {
                [acsAgendaAdmin.i18n.update]: this.submitEvent.bind(this),
                [acsAgendaAdmin.i18n.cancel]: function () {
                    $(this).dialog('close');
                },
            });

            this.$eventDialog.dialog('open');
        },

        /**
         * Populate form with existing event data
         */
        populateForm: function (eventId) {
            const itemClass = '.origItem_' + eventId;

            $(itemClass).each(function () {
                const $el = $(this);
                const name = $el.data('name');
                const value = $el.text().trim();

                if (name) {
                    const $input = $('#event-' + name);

                    if ($input.is('select')) {
                        // Find matching option by text
                        $input.find('option').each(function () {
                            if ($(this).text().trim().toLowerCase() === value.toLowerCase()) {
                                $(this).prop('selected', true);
                            }
                        });
                    } else {
                        $input.val(value);
                    }
                }
            });
        },

        /**
         * Reset form to empty state
         */
        resetForm: function () {
            this.$eventForm[0].reset();
            $('#event-id').val('');
            this.destroyDatepicker();
        },

        /**
         * Submit event form
         */
        submitEvent: function () {
            const self = this;

            // Validate required fields
            if (!this.validateForm()) {
                return;
            }

            this.$spinner.show();

            const formData = this.$eventForm.serialize();

            $.ajax({
                url: acsAgendaAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    self.$spinner.hide();

                    if (response.success) {
                        self.showNotice(response.data, 'success');
                        self.$eventDialog.dialog('close');
                        location.reload();
                    } else {
                        self.showNotice(response.data || 'Error occurred', 'error');
                    }
                },
                error: function () {
                    self.$spinner.hide();
                    self.showNotice('Request failed', 'error');
                },
            });
        },

        /**
         * Validate form fields
         */
        validateForm: function () {
            let isValid = true;

            this.$eventForm.find('[required]').each(function () {
                const $field = $(this);

                if (!$field.val().trim()) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });

            if (!isValid) {
                this.showNotice(acsAgendaAdmin.i18n.fieldEmpty, 'error');
            }

            return isValid;
        },

        /**
         * Confirm delete action
         */
        confirmDelete: function (e) {
            e.preventDefault();

            const $link = $(e.currentTarget);
            const targetUrl = $link.attr('href');
            const eventId = new URLSearchParams(targetUrl.split('?')[1]).get('id');
            const eventName = $('#categorie' + eventId).text();

            $('#acs-delete-event-name').text(eventName);

            this.$deleteDialog.dialog('option', 'buttons', {
                [acsAgendaAdmin.i18n.confirm]: function () {
                    window.location.href = targetUrl;
                },
                [acsAgendaAdmin.i18n.cancel]: function () {
                    $(this).dialog('close');
                },
            });

            this.$deleteDialog.dialog('open');
        },

        /**
         * Show help dialog
         */
        showHelp: function (e) {
            e.preventDefault();
            this.$helpDialog.dialog('open');
        },

        /**
         * Open WordPress media library
         */
        openMediaLibrary: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $input = $button.siblings('input[type="text"]');

            const frame = wp.media({
                title: acsAgendaAdmin.i18n.selectImage,
                library: { type: 'image' },
                button: { text: 'Select' },
                multiple: false,
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url);
            });

            frame.open();
        },

        /**
         * Open multi-date picker
         */
        openCalendar: function (e) {
            e.preventDefault();

            const $container = $('#acs-datepicker-container');
            const $input = $('#event-date');

            if ($container.hasClass('active')) {
                this.destroyDatepicker();
                return;
            }

            const currentDates = $input.val() ? $input.val().split(',') : [];

            const options = {
                dateFormat: 'dd/mm/yy',
                altField: '#event-date',
                separator: ',',
                onSelect: function () {
                    $input.val($container.multiDatesPicker('getDates').join(','));
                },
            };

            if (currentDates.length) {
                options.defaultDate = currentDates[0];
                options.addDates = currentDates;
            }

            $container.addClass('active').multiDatesPicker(options);
        },

        /**
         * Destroy datepicker
         */
        destroyDatepicker: function () {
            const $container = $('#acs-datepicker-container');

            if ($container.hasClass('active')) {
                $container.removeClass('active').multiDatesPicker('destroy').empty();
            }
        },

        /**
         * Toggle description visibility
         */
        toggleDescription: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const isReadMore = $button.hasClass('read_more');

            $button.toggle();

            if (isReadMore) {
                $button.nextAll('.hide_more:first').toggle();
            } else {
                $button.prev('.read_more').toggle();
            }

            $button.nextAll('.fullcontent:first').toggle();
        },

        /**
         * Handle event filter change
         */
        filterEvents: function (e) {
            const filter = $(e.currentTarget).val();
            const baseUrl = 'admin.php?page=agenda';

            if (filter) {
                window.location.href = baseUrl + '&event-filter=' + encodeURIComponent(filter);
            } else {
                window.location.href = baseUrl;
            }
        },

        /**
         * Show admin notice
         */
        showNotice: function (message, type) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';

            const $notice = $(
                '<div class="notice ' +
                    noticeClass +
                    ' is-dismissible">' +
                    '<p>' +
                    message +
                    '</p>' +
                    '<button type="button" class="notice-dismiss"></button>' +
                    '</div>'
            );

            this.$notices.html($notice);

            $notice.find('.notice-dismiss').on('click', function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            });
        },
    };

    /**
     * Copy shortcode to clipboard
     */
    window.copyShortcode = function (textareaId) {
        const $textarea = $('#' + textareaId);
        $textarea.prop('disabled', false).select();
        document.execCommand('copy');
        $textarea.prop('disabled', true);

        $('#ACSmessage' + textareaId).html('<strong>' + acsAgendaAdmin.i18n.copied + '!</strong>');
        $('#MSGWrapper' + textareaId).show();
    };

    // Initialize on document ready
    $(document).ready(function () {
        ACSAgendaAdmin.init();
    });
})(jQuery);
