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

            // Date field validation on blur
            $(document).on('blur', '#event-date', this.validateDateField.bind(this));
        },

        /**
         * Validate date field format
         */
        validateDateField: function (e) {
            const $input = $(e.currentTarget);
            const value = $input.val().trim();

            if (!value) {
                $input.removeClass('error');
                return true;
            }

            // Split by comma and validate each date
            const dates = value.split(',').map(function(d) {
                return d.trim();
            }).filter(function(d) {
                return d;
            });
            const validDates = [];
            const dateRegex = /^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/;

            for (let i = 0; i < dates.length; i++) {
                const match = dates[i].match(dateRegex);
                if (!match) {
                    $input.addClass('error');
                    return false;
                }

                const day = parseInt(match[1], 10);
                const month = parseInt(match[2], 10);
                let year = parseInt(match[3], 10);

                // Validate ranges
                if (month < 1 || month > 12 || day < 1 || day > 31) {
                    $input.addClass('error');
                    return false;
                }

                // Normalize year to 2-digit
                if (year >= 2000) {
                    year = year - 2000;
                }

                // Format consistently
                const formatted = ('0' + day).slice(-2) + '/' + ('0' + month).slice(-2) + '/' + ('0' + year).slice(-2);
                validDates.push(formatted);
            }

            // Update with cleaned values
            $input.val(validDates.join(', '));
            $input.removeClass('error');
            return true;
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

            // Get today at midnight for minDate check
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Helper function to get current dates from input
            function getCurrentDates() {
                const val = $input.val();
                if (!val) {
                    return [];
                }
                return val.split(',').map(function(d) {
                    return d.trim();
                }).filter(function(d) {
                    return d;
                });
            }

            // Check if we're editing an event with past dates (allow past dates if editing)
            const initialDates = getCurrentDates();
            let hasPastDates = false;
            initialDates.forEach(function(dateStr) {
                const parts = dateStr.split('/');
                if (parts.length === 3) {
                    let year = parseInt(parts[2], 10);
                    if (year < 100) {
                        year += 2000;
                    }
                    const date = new Date(year, parseInt(parts[1], 10) - 1, parseInt(parts[0], 10));
                    if (date < today) {
                        hasPastDates = true;
                    }
                }
            });

            const options = {
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-10:c+10',
                minDate: hasPastDates ? null : new Date(),
                beforeShowDay: function(date) {
                    // Format date to compare
                    const d = ('0' + date.getDate()).slice(-2);
                    const m = ('0' + (date.getMonth() + 1)).slice(-2);
                    const y = date.getFullYear().toString().slice(-2);
                    const dateStr = d + '/' + m + '/' + y;

                    // Read current dates from input each time
                    const currentDates = getCurrentDates();
                    const isSelected = currentDates.indexOf(dateStr) !== -1;

                    // Disable past dates for new events
                    if (!hasPastDates) {
                        const dateAtMidnight = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                        if (dateAtMidnight < today) {
                            return [false, 'ui-datepicker-unselectable ui-state-disabled acs-past-date', 'Date passée'];
                        }
                    }

                    return [true, isSelected ? 'ui-state-highlight' : '', ''];
                },
                onSelect: function(dateText) {
                    // Read current dates from input (not from cached array)
                    const currentDates = getCurrentDates();

                    const idx = currentDates.indexOf(dateText);
                    if (idx === -1) {
                        // Add date
                        currentDates.push(dateText);
                    } else {
                        // Remove date
                        currentDates.splice(idx, 1);
                    }

                    // Sort dates chronologically
                    currentDates.sort(function(a, b) {
                        const pa = a.split('/');
                        const pb = b.split('/');
                        const da = new Date(2000 + parseInt(pa[2], 10), parseInt(pa[1], 10) - 1, parseInt(pa[0], 10));
                        const db = new Date(2000 + parseInt(pb[2], 10), parseInt(pb[1], 10) - 1, parseInt(pb[0], 10));
                        return da - db;
                    });

                    // Update input
                    $input.val(currentDates.join(', '));

                    // Refresh datepicker to update highlighting
                    $container.datepicker('refresh');
                },
            };

            $container.addClass('active').datepicker(options);

            // Add close button after datepicker is created
            const self = this;
            setTimeout(function() {
                if ($container.find('.acs-datepicker-close').length === 0) {
                    const closeBtn = $('<button type="button" class="acs-datepicker-close">✕ ' + (acsAgendaAdmin.i18n.close || 'Close') + '</button>');
                    $container.append(closeBtn);
                    closeBtn.on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        self.destroyDatepicker();
                    });
                }
            }, 50);
        },

        /**
         * Destroy datepicker
         */
        destroyDatepicker: function () {
            const $container = $('#acs-datepicker-container');

            if ($container.hasClass('active')) {
                $container.removeClass('active').datepicker('destroy').empty();
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
