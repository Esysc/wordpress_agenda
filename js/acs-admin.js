/**
 * ACS Agenda Manager - Admin JavaScript
 *
 * @package ACSAgendaManager
 */

(function ($) {
    'use strict';

    /**
     * Fallback synchronizer for date UI state in case stale handlers are still bound.
     */
    const setupDateFallbackSync = function () {
        const $doc = $(document);

        const parseDateToken = function (token) {
            const match = token.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
            if (!match) {
                return null;
            }

            const day = parseInt(match[1], 10);
            const month = parseInt(match[2], 10);
            let year = parseInt(match[3], 10);

            if (month < 1 || month > 12 || day < 1 || day > 31) {
                return null;
            }

            if (year < 100) {
                year += 2000;
            }

            const date = new Date(year, month - 1, day);
            if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
                return null;
            }

            return {
                canonical:
                    ('0' + day).slice(-2) + '/' + ('0' + month).slice(-2) + '/' + ('0' + (year % 100)).slice(-2),
                timestamp: date.getTime(),
            };
        };

        const normalizeDateList = function (rawValue) {
            const tokens = rawValue
                .split(',')
                .map(function (d) {
                    return d.trim();
                })
                .filter(function (d) {
                    return d;
                });

            const parsed = [];
            for (let i = 0; i < tokens.length; i++) {
                const dateInfo = parseDateToken(tokens[i]);
                if (!dateInfo) {
                    return { valid: false, dates: [] };
                }
                parsed.push(dateInfo);
            }

            parsed.sort(function (a, b) {
                return a.timestamp - b.timestamp;
            });

            const seen = {};
            const unique = [];
            parsed.forEach(function (item) {
                if (!seen[item.canonical]) {
                    seen[item.canonical] = true;
                    unique.push(item.canonical);
                }
            });

            return {
                valid: true,
                dates: unique,
            };
        };

        const renderChips = function (dates) {
            const $selectedDates = $('#acs-selected-dates');
            if (!$selectedDates.length) {
                return;
            }

            if (!dates.length) {
                $selectedDates.html(
                    '<span class="acs-selected-dates-empty">' +
                        (acsagmaAgendaAdmin.i18n.noDatesSelected || 'No dates selected yet') +
                        '</span>'
                );
                return;
            }

            const chips = dates
                .map(function (dateValue) {
                    return (
                        '<span class="acs-date-chip" data-date="' +
                        dateValue +
                        '">' +
                        '<span class="acs-date-chip-label">' +
                        dateValue +
                        '</span>' +
                        '<button type="button" class="acs-date-chip-remove" aria-label="' +
                        (acsagmaAgendaAdmin.i18n.removeDate || 'Remove date') +
                        ': ' +
                        dateValue +
                        '">&times;</button>' +
                        '</span>'
                    );
                })
                .join('');

            $selectedDates.html(chips);
        };

        const syncDateUI = function () {
            const $dateInput = $('#event-date');
            const $dateContainer = $('#acs-datepicker-container');
            const $calendarButton = $('.acs-open-calendar');

            if ($calendarButton.length) {
                $calendarButton.attr('aria-expanded', $dateContainer.hasClass('active') ? 'true' : 'false');
            }

            if (!$dateInput.length) {
                return;
            }

            const rawValue = $dateInput.val().trim();
            if (!rawValue) {
                renderChips([]);
                return;
            }

            const normalized = normalizeDateList(rawValue);
            if (!normalized.valid) {
                renderChips([]);
                return;
            }

            const canonicalValue = normalized.dates.join(', ');
            if (rawValue !== canonicalValue) {
                $dateInput.val(canonicalValue);
            }

            renderChips(normalized.dates);
        };

        const scheduleSync = function () {
            setTimeout(function () {
                syncDateUI();
            }, 0);
        };

        $doc.on(
            'input change blur click',
            '#event-date, .acs-open-calendar, .acs-datepicker-close, .ui-datepicker-calendar td a',
            scheduleSync
        );

        $doc.on('keydown', function (e) {
            const $dateContainer = $('#acs-datepicker-container');
            if (e.key === 'Escape' && $dateContainer.hasClass('active')) {
                e.preventDefault();
                $('.acs-open-calendar').trigger('click');
                scheduleSync();
            }
        });

        syncDateUI();
    };

    const ACSAgendaAdmin = {
        /**
         * Initialize admin functionality
         */
        init: function () {
            this.cacheElements();
            this.bindEvents();
            this.initDialogs();
            this.initPlacesAutocomplete();
            this.updateSelectedDatesUI();
            this.syncDateUIState();
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
            this.$advancedSection = $('#acs-advanced-settings');
            this.$dateInput = $('#event-date');
            this.$dateContainer = $('#acs-datepicker-container');
            this.$calendarButton = $('.acs-open-calendar');
            this.$selectedDates = $('#acs-selected-dates');
            this.$dateError = $('#event-date-error');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            const self = this;
            const scheduleDateUISync = function () {
                setTimeout(function () {
                    self.syncDateUIState();
                }, 0);
            };

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

            // Image remove button
            $(document).on('click', '.acs-remove-image', this.removeImage.bind(this));

            // Image URL change - update preview
            $(document).on('input', '#event-image', this.updateImagePreview.bind(this));

            // Keep advanced section open when the user adds advanced data
            $(document).on('input change', '#event-image, #event-link, #event-redirect, #event-price, #event-account, #event-candopartial', function () {
                self.syncAdvancedSectionState();
            });

            // Calendar button
            $(document).on('click', '.acs-open-calendar', this.openCalendar.bind(this));

            // Remove selected date chip
            $(document).on('click', '.acs-date-chip-remove', this.removeSelectedDate.bind(this));

            // Read more / hide buttons
            $(document).on('click', '.read_more, .hide_more', this.toggleDescription.bind(this));

            // Event filter change
            $('.ewc-filter-event').on('change', this.filterEvents.bind(this));

            // Date field validation on blur
            $(document).on('blur', '#event-date', this.validateDateField.bind(this));

            // Defensive synchronization for stale browser callback paths
            $(document).on('input change blur', '#event-date', scheduleDateUISync);
            $(document).on('click', '.acs-open-calendar, .acs-datepicker-close, .ui-datepicker-calendar td a', scheduleDateUISync);

            // Close datepicker on escape and outside click
            $(document).on('keydown', this.handleGlobalKeydown.bind(this));
            $(document).on('mousedown', this.handleOutsideClick.bind(this));
        },

        /**
         * Handle global keyboard shortcuts
         */
        handleGlobalKeydown: function (e) {
            if (e.key === 'Escape' && this.$dateContainer.hasClass('active')) {
                e.preventDefault();
                this.destroyDatepicker(true);
            }
        },

        /**
         * Close datepicker when clicking outside its controls
         */
        handleOutsideClick: function (e) {
            if (!this.$dateContainer.hasClass('active')) {
                return;
            }

            const $target = $(e.target);
            const isInsideDatepicker = $target.closest('#acs-datepicker-container').length > 0;
            const isCalendarButton = $target.closest('.acs-open-calendar').length > 0;

            if (!isInsideDatepicker && !isCalendarButton) {
                this.destroyDatepicker(false);
            }
        },

        /**
         * Parse a date token and return canonical and timestamp values
         */
        parseDateToken: function (token) {
            const match = token.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
            if (!match) {
                return null;
            }

            const day = parseInt(match[1], 10);
            const month = parseInt(match[2], 10);
            let year = parseInt(match[3], 10);

            if (month < 1 || month > 12 || day < 1 || day > 31) {
                return null;
            }

            if (year < 100) {
                year += 2000;
            }

            const date = new Date(year, month - 1, day);
            if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
                return null;
            }

            const canonical =
                ('0' + day).slice(-2) +
                '/' +
                ('0' + month).slice(-2) +
                '/' +
                ('0' + (year % 100)).slice(-2);

            return {
                canonical: canonical,
                timestamp: date.getTime(),
            };
        },

        /**
         * Validate and normalize comma-separated date list
         */
        normalizeDateList: function (rawValue) {
            const tokens = rawValue
                .split(',')
                .map(function (d) {
                    return d.trim();
                })
                .filter(function (d) {
                    return d;
                });

            const parsed = [];
            for (let i = 0; i < tokens.length; i++) {
                const dateInfo = this.parseDateToken(tokens[i]);
                if (!dateInfo) {
                    return { valid: false, dates: [] };
                }
                parsed.push(dateInfo);
            }

            parsed.sort(function (a, b) {
                return a.timestamp - b.timestamp;
            });

            const seen = {};
            const unique = [];
            parsed.forEach(function (item) {
                if (!seen[item.canonical]) {
                    seen[item.canonical] = true;
                    unique.push(item.canonical);
                }
            });

            return {
                valid: true,
                dates: unique,
            };
        },

        /**
         * Read current date input as normalized list
         */
        getCurrentDates: function () {
            const value = this.$dateInput.val().trim();
            if (!value) {
                return [];
            }

            const normalized = this.normalizeDateList(value);
            return normalized.valid ? normalized.dates : [];
        },

        /**
         * Persist date list to input and chip UI
         */
        setDateValue: function (dates) {
            this.$dateInput.val(dates.join(', '));
            this.updateSelectedDatesUI(dates);
        },

        /**
         * Keep date chips and aria-expanded in sync with current DOM state
         */
        syncDateUIState: function () {
            const isCalendarOpen = this.$dateContainer.hasClass('active');
            this.$calendarButton.attr('aria-expanded', isCalendarOpen ? 'true' : 'false');

            const rawValue = this.$dateInput.val().trim();
            if (!rawValue) {
                this.updateSelectedDatesUI([]);
                return;
            }

            const normalized = this.normalizeDateList(rawValue);
            if (!normalized.valid) {
                this.updateSelectedDatesUI([]);
                return;
            }

            const canonicalValue = normalized.dates.join(', ');
            if (rawValue !== canonicalValue) {
                this.$dateInput.val(canonicalValue);
            }

            this.updateSelectedDatesUI(normalized.dates);
        },

        /**
         * Show inline date field error message
         */
        showDateError: function (message) {
            if (!message) {
                this.$dateError.text('').attr('hidden', true);
                return;
            }

            this.$dateError.text(message).removeAttr('hidden');
        },

        /**
         * Validate date field format
         */
        validateDateField: function (e) {
            const $input = $(e.currentTarget);
            const value = $input.val().trim();

            if (!value) {
                $input.removeClass('error');
                this.showDateError('');
                this.updateSelectedDatesUI([]);
                return true;
            }

            const normalized = this.normalizeDateList(value);
            if (!normalized.valid) {
                $input.addClass('error');
                this.showDateError(acsagmaAgendaAdmin.i18n.invalidDate || 'Invalid date format. Use dd/mm/yy');
                return false;
            }

            $input.removeClass('error');
            this.showDateError('');
            this.setDateValue(normalized.dates);
            return true;
        },

        /**
         * Render selected date chips below the input
         */
        updateSelectedDatesUI: function (dates) {
            const values = Array.isArray(dates) ? dates : this.getCurrentDates();

            if (!values.length) {
                this.$selectedDates.html(
                    '<span class="acs-selected-dates-empty">' +
                        (acsagmaAgendaAdmin.i18n.noDatesSelected || 'No dates selected yet') +
                        '</span>'
                );
                return;
            }

            const chips = values
                .map(function (dateValue) {
                    return (
                        '<span class="acs-date-chip" data-date="' +
                        dateValue +
                        '">' +
                        '<span class="acs-date-chip-label">' +
                        dateValue +
                        '</span>' +
                        '<button type="button" class="acs-date-chip-remove" aria-label="' +
                        (acsagmaAgendaAdmin.i18n.removeDate || 'Remove date') +
                        ': ' +
                        dateValue +
                        '">&times;</button>' +
                        '</span>'
                    );
                })
                .join('');

            this.$selectedDates.html(chips);
        },

        /**
         * Remove a selected date using the chip action
         */
        removeSelectedDate: function (e) {
            e.preventDefault();

            const targetDate = $(e.currentTarget).closest('.acs-date-chip').data('date');
            const currentDates = this.getCurrentDates().filter(function (item) {
                return item !== targetDate;
            });

            this.setDateValue(currentDates);
            this.$dateInput.removeClass('error');
            this.showDateError('');
            this.syncDateUIState();

            if (this.$dateContainer.hasClass('active')) {
                this.$dateContainer.datepicker('refresh');
            }
        },

        /**
         * Initialize Google Places Autocomplete
         */
        initPlacesAutocomplete: function () {
            if (!acsagmaAgendaAdmin.hasGoogleMaps || typeof google === 'undefined') {
                return;
            }

            const self = this;
            const input = document.getElementById('event-emplacement');

            if (!input) {
                return;
            }

            // Wait for Google Maps API to load
            if (typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
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
            } catch (error) {
                console.warn('Google Places Autocomplete initialization failed:', error);
            }
        },

        /**
         * Initialize jQuery UI dialogs
         */
        initDialogs: function () {
            this.$eventDialog.dialog({
                autoOpen: false,
                modal: true,
                width: 600,
                maxHeight: $(window).height() - 100,
                buttons: {},
                open: this.updateCalendarPosition.bind(this),
            });
            this.$eventDialog.on('dialogopen', () => {
                this.syncImagePreviewText();
            });

            const preview = document.getElementById('event-image-preview');
            if (preview && typeof MutationObserver !== 'undefined') {
                const desiredText = acsagmaAgendaAdmin.i18n.noImageSelected || '';
                const observer = new MutationObserver(() => {
                    const previewText = preview.querySelector('.acs-image-preview-text');
                    if (previewText && previewText.textContent !== desiredText) {
                        previewText.textContent = desiredText;
                    }
                });

                observer.observe(preview, {
                    childList: true,
                    subtree: true,
                    characterData: true,
                });

                this.imagePreviewObserver = observer;
            }

            this.$deleteDialog.dialog({
                autoOpen: false,
                modal: true,
                width: 400,
                buttons: {},
            });

            this.$helpDialog.dialog({
                autoOpen: false,
                modal: true,
                width: 700,
                buttons: {
                    [acsagmaAgendaAdmin.i18n.close]: function () {
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
            $('#event-action').val('acsagma_add_item_agenda');

            this.$eventDialog.dialog('option', 'title', acsagmaAgendaAdmin.i18n.addEvent);
            this.$eventDialog.dialog('option', 'buttons', {
                [acsagmaAgendaAdmin.i18n.add]: this.submitEvent.bind(this),
                [acsagmaAgendaAdmin.i18n.cancel]: function () {
                    $(this).dialog('close');
                },
            });

            this.$eventDialog.dialog('open');
            window.setTimeout(() => {
                this.$eventDialog.find('.acs-image-preview-text').text(acsagmaAgendaAdmin.i18n.noImageSelected || '');
            }, 0);
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
            $('#event-action').val('acsagma_update_agenda');

            this.$eventDialog.dialog('option', 'title', acsagmaAgendaAdmin.i18n.editor);
            this.$eventDialog.dialog('option', 'buttons', {
                [acsagmaAgendaAdmin.i18n.update]: this.submitEvent.bind(this),
                [acsagmaAgendaAdmin.i18n.cancel]: function () {
                    $(this).dialog('close');
                },
            });

            this.$eventDialog.dialog('open');
        },

        /**
         * Populate form with existing event data
         */
        populateForm: function (eventId) {
            const self = this;
            const itemClass = '.origItem_' + eventId;

            $(itemClass).each(function () {
                const $el = $(this);
                const name = $el.data('name');
                const value = $el.text().trim();

                if (name) {
                    const $input = $('#event-' + name);

                    if ($input.is('select')) {
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

            self.updateImagePreview();
            self.syncAdvancedSectionState();
            self.validateDateField({ currentTarget: self.$dateInput[0] });
        },

        /**
         * Reset form to empty state
         */
        resetForm: function () {
            this.$eventForm[0].reset();
            $('#event-id').val('');
            this.destroyDatepicker(false);
            this.clearImagePreview();
            this.showDateError('');
            this.$dateInput.removeClass('error');
            this.updateSelectedDatesUI([]);
            this.syncAdvancedSectionState(false);
            this.$eventForm.find('.error').removeClass('error');
            $('#acs-dialog-notices').empty();
        },

        /**
         * Determine whether advanced fields contain meaningful values
         */
        hasAdvancedValues: function () {
            const image = $('#event-image').val().trim();
            const link = $('#event-link').val().trim();
            const redirect = $('#event-redirect').val().trim();
            const price = $('#event-price').val().trim();
            const account = $('#event-account').val();
            const partialAttendance = $('#event-candopartial').val();

            return Boolean(
                image ||
                    link ||
                    redirect ||
                    price ||
                    account !== '0' ||
                    partialAttendance !== '0'
            );
        },

        /**
         * Expand or collapse the advanced section based on current form state
         */
        syncAdvancedSectionState: function (forceOpen) {
            if (!this.$advancedSection || !this.$advancedSection.length) {
                return;
            }

            if (typeof forceOpen === 'boolean') {
                this.$advancedSection.prop('open', forceOpen);
                return;
            }

            this.$advancedSection.prop('open', this.hasAdvancedValues());
        },

        /**
         * Submit event form
         */
        submitEvent: function () {
            const self = this;

            if (!this.validateForm()) {
                return;
            }

            this.$spinner.show();

            const formData = this.$eventForm.serialize();

            $.ajax({
                url: acsagmaAgendaAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    self.$spinner.hide();

                    if (response.success) {
                        self.$eventDialog.dialog('close');
                        const isUpdate = self.$eventForm.find('input[name="id"]').val();
                        const url = new URL(window.location.href);
                        url.searchParams.delete('deleted');
                        url.searchParams.delete('updated');
                        url.searchParams.delete('created');
                        url.searchParams.set(isUpdate ? 'updated' : 'created', '1');
                        window.location.href = url.toString();
                    } else {
                        self.showNotice(response.data || 'Error occurred', 'error', true);
                    }
                },
                error: function () {
                    self.$spinner.hide();
                    self.showNotice(acsagmaAgendaAdmin.i18n.requestFailed || 'Request failed', 'error', true);
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

            if (!this.validateDateField({ currentTarget: this.$dateInput[0] })) {
                isValid = false;
            }

            if (!isValid) {
                this.showNotice(acsagmaAgendaAdmin.i18n.fieldEmpty, 'error', true);
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
                [acsagmaAgendaAdmin.i18n.confirm]: function () {
                    window.location.href = targetUrl;
                },
                [acsagmaAgendaAdmin.i18n.cancel]: function () {
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

            const self = this;
            const $input = $('#event-image');

            const frame = wp.media({
                title: acsagmaAgendaAdmin.i18n.selectImage,
                library: { type: 'image' },
                button: { text: acsagmaAgendaAdmin.i18n.select || 'Select' },
                multiple: false,
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url);
                self.updateImagePreview();
            });

            frame.open();
        },

        /**
         * Update image preview
         */
        updateImagePreview: function () {
            const $input = $('#event-image');
            const $preview = $('#event-image-preview');
            const $removeBtn = $('.acs-remove-image');
            const imageUrl = $input.val().trim();

            if (imageUrl) {
                $preview.addClass('has-image').html('<img src="' + imageUrl + '" alt="Preview" />');
                $removeBtn.show();
            } else {
                this.clearImagePreview();
            }
        },

        /**
         * Clear image preview
         */
        clearImagePreview: function () {
            const $preview = $('#event-image-preview');
            const $removeBtn = $('.acs-remove-image');

            $preview
                .removeClass('has-image')
                .html('<span class="dashicons dashicons-format-image"></span><span class="acs-image-preview-text"></span>');
            this.syncImagePreviewText();
            $removeBtn.hide();
        },

        /**
         * Keep the empty image preview label localized.
         */
        syncImagePreviewText: function () {
            const $text = $('#event-image-preview .acs-image-preview-text');
            if (!$text.length) {
                return;
            }

            $text.text(acsagmaAgendaAdmin.i18n.noImageSelected || '');
        },

        /**
         * Remove image
         */
        removeImage: function (e) {
            e.preventDefault();
            $('#event-image').val('');
            this.clearImagePreview();
        },

        /**
         * Open multi-date picker
         */
        openCalendar: function (e) {
            e.preventDefault();

            if (this.$dateContainer.hasClass('active')) {
                this.destroyDatepicker(true);
                return;
            }

            const currentDates = this.getCurrentDates();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            let hasPastDates = false;
            currentDates.forEach(function (dateStr) {
                const parts = dateStr.split('/');
                const year = 2000 + parseInt(parts[2], 10);
                const date = new Date(year, parseInt(parts[1], 10) - 1, parseInt(parts[0], 10));
                if (date < today) {
                    hasPastDates = true;
                }
            });

            const self = this;
            const options = {
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-10:c+10',
                minDate: hasPastDates ? null : new Date(),
                beforeShowDay: function (date) {
                    const d = ('0' + date.getDate()).slice(-2);
                    const m = ('0' + (date.getMonth() + 1)).slice(-2);
                    const y = date.getFullYear().toString().slice(-2);
                    const dateStr = d + '/' + m + '/' + y;

                    const selected = self.getCurrentDates().indexOf(dateStr) !== -1;

                    if (!hasPastDates) {
                        const dateAtMidnight = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                        if (dateAtMidnight < today) {
                            return [
                                false,
                                'ui-datepicker-unselectable ui-state-disabled acs-past-date',
                                acsagmaAgendaAdmin.i18n.pastDateUnavailable || 'Past date unavailable',
                            ];
                        }
                    }

                    return [true, selected ? 'ui-state-highlight' : '', ''];
                },
                onSelect: function (dateText) {
                    const selectedDate = self.parseDateToken(dateText);
                    const canonicalDate = selectedDate ? selectedDate.canonical : dateText;
                    const dates = self.getCurrentDates();
                    const index = dates.indexOf(canonicalDate);

                    if (index === -1) {
                        dates.push(canonicalDate);
                    } else {
                        dates.splice(index, 1);
                    }

                    const normalized = self.normalizeDateList(dates.join(', '));
                    self.setDateValue(normalized.valid ? normalized.dates : dates);
                    self.showDateError('');
                    self.$dateInput.removeClass('error');
                    self.$dateContainer.datepicker('refresh');
                },
            };

            this.$dateContainer.addClass('active').datepicker(options);
            this.updateCalendarPosition();
            this.syncDateUIState();

            if (this.$dateContainer.find('.acs-datepicker-close').length === 0) {
                const closeBtn =
                    '<button type="button" class="acs-datepicker-close">' +
                    (acsagmaAgendaAdmin.i18n.close || 'Close') +
                    '</button>';
                this.$dateContainer.append(closeBtn);
                this.$dateContainer.find('.acs-datepicker-close').on('click', function (event) {
                    event.preventDefault();
                    self.destroyDatepicker(true);
                });
            }
        },

        /**
         * Position datepicker above or below input based on available space
         */
        updateCalendarPosition: function () {
            if (!this.$dateContainer.hasClass('active')) {
                return;
            }

            this.$dateContainer.removeClass('is-below is-above');

            if (window.innerWidth <= 782) {
                this.$dateContainer.addClass('is-below');
                return;
            }

            const inputRect = this.$dateInput[0].getBoundingClientRect();
            const estimatedHeight = Math.max(this.$dateContainer.outerHeight(), 320);
            const availableTop = inputRect.top;
            const availableBottom = window.innerHeight - inputRect.bottom;

            if (availableTop >= estimatedHeight || availableTop > availableBottom) {
                this.$dateContainer.addClass('is-above');
            } else {
                this.$dateContainer.addClass('is-below');
            }
        },

        /**
         * Destroy datepicker
         */
        destroyDatepicker: function (focusButton) {
            if (this.$dateContainer.hasClass('active')) {
                this.$dateContainer.removeClass('active is-below is-above').datepicker('destroy').empty();
            }

            this.syncDateUIState();
            if (focusButton) {
                this.$calendarButton.trigger('focus');
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
            const baseUrl = 'admin.php?page=acsagma-agenda';

            if (filter) {
                window.location.href = baseUrl + '&event-filter=' + encodeURIComponent(filter);
            } else {
                window.location.href = baseUrl;
            }
        },

        /**
         * Show admin notice
         * @param {string} message - The message to display
         * @param {string} type - 'success' or 'error'
         * @param {boolean} inDialog - If true, show inside the dialog instead of main page
         */
        showNotice: function (message, type, inDialog) {
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

            const $container = inDialog ? $('#acs-dialog-notices') : this.$notices;
            $container.html($notice);

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

        $('#ACSmessage' + textareaId).html('<strong>' + acsagmaAgendaAdmin.i18n.copied + '!</strong>');
        $('#MSGWrapper' + textareaId).show();
    };

    $(document).ready(function () {
        ACSAgendaAdmin.init();
        setupDateFallbackSync();
    });
})(jQuery);
