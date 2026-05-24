/**
 * ACS Agenda Manager - Frontend JavaScript
 *
 * @package ACSAgendaManager
 */

(function ($) {
    'use strict';

    const DEFAULT_PAGE_SIZE = 8;

    const ACSAgendaFrontend = {
        lastFocusedElement: null,
        cards: [],
        state: {
            search: '',
            category: '',
            dateRange: 'all',
            sort: 'soonest',
            page: 1,
            compact: false,
        },

        /**
         * Initialize the frontend functionality
         */
        init: function () {
            this.bindEvents();
            this.initImageFallback();
            this.initAgendaExperience();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            const self = this;

            $(document).on('click', '.readmore, .acs-contact-trigger', this.handleDialogTrigger.bind(this));
            $(window).on('scroll', this.trackScroll);

            // Read more dialog close handlers
            $(document).on('click', '#dialog', function (e) {
                if (e.target === e.currentTarget) {
                    window.closeDialog();
                }
            });

            $(document).on('click', '#dialog #close', function (e) {
                e.preventDefault();
                e.stopPropagation();
                window.closeDialog();
            });

            $(document).on('submit', '.acs-contact-form', function (e) {
                e.preventDefault();
                self.handleContactFormSubmit(e);
            });

            $(document).on('input change', '.acs-contact-form input, .acs-contact-form textarea', function (e) {
                const $field = $(e.currentTarget);
                $field.removeClass('is-invalid').removeAttr('aria-invalid');
            });

            $(document).on('keydown', function (e) {
                if ($('#dialog').hasClass('shown') && e.key === 'Escape') {
                    window.closeDialog();
                }
            });

            // Image lightbox - use delegation on document
            $(document).on('click', '.image-agenda', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.openLightbox(e);
            });

            $(document).on('keydown', '.image-agenda', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self.openLightbox(e);
                }
            });

            // Lightbox close handlers
            $(document).on('click', '#acs-lightbox-overlay', function(e) {
                self.closeLightbox(e);
            });

            $(document).on('click', '.acs-lightbox-close', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.closeLightbox();
            });

            $(document).on('keydown', function(e) {
                if ($('#acs-lightbox-overlay').hasClass('active') && e.key === 'Escape') {
                    self.closeLightbox();
                }
            });

            // Toolbar controls
            $(document).on('input', '#acs-filter-search', function () {
                self.state.search = ($(this).val() || '').toString().trim().toLowerCase();
                self.state.page = 1;
                self.applyAgendaView();
            });

            $(document).on('change', '#acs-filter-category', function () {
                self.state.category = ($(this).val() || '').toString().toLowerCase();
                self.state.page = 1;
                self.applyAgendaView();
            });

            $(document).on('change', '#acs-filter-date', function () {
                self.state.dateRange = ($(this).val() || 'all').toString();
                self.state.page = 1;
                self.applyAgendaView();
            });

            $(document).on('change', '#acs-sort-order', function () {
                self.state.sort = ($(this).val() || 'soonest').toString();
                self.state.page = 1;
                self.applyAgendaView();
            });

            $(document).on('click', '#acs-compact-toggle', function () {
                self.state.compact = !self.state.compact;
                self.applyAgendaView();
            });

            $(document).on('click', '.acs-page-link', function () {
                const page = parseInt($(this).data('page'), 10);
                if (!Number.isNaN(page) && page > 0) {
                    self.state.page = page;
                    self.applyAgendaView();

                    const offset = $('#acs-agenda-list').offset();
                    const top = offset && typeof offset.top === 'number' ? offset.top : 0;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
            });
        },

        /**
         * Set fallback image source when external image fails.
         */
        initImageFallback: function () {
            const agendaConfig = window.acsagmaAgenda || window.acsAgenda || {};
            const fallbackImage = agendaConfig.fallbackImage || '';

            if (!fallbackImage) {
                return;
            }

            document.addEventListener('error', function (event) {
                const target = event.target;

                if (!(target instanceof HTMLImageElement) || !target.classList.contains('image-agenda')) {
                    return;
                }

                const $img = $(target);
                if ($img.attr('src') !== fallbackImage) {
                    $img.attr('src', fallbackImage);
                    $img.attr('data-full-src', fallbackImage);
                    $img.addClass('is-fallback');
                }
            }, true);
        },

        /**
         * Initialize agenda controls and card metadata.
         */
        initAgendaExperience: function () {
            const $cards = $('#acs-agenda-list .acsagenda');
            if (!$cards.length) {
                return;
            }

            this.cards = $cards.toArray();
            this.populateCategoryFilter();
            this.loadStateFromUrl();
            this.syncControls();
            this.applyAgendaView();
        },

        /**
         * Fill category dropdown from rendered cards.
         */
        populateCategoryFilter: function () {
            const $category = $('#acs-filter-category');
            if (!$category.length) {
                return;
            }

            const categories = new Set();
            this.cards.forEach(function (card) {
                const value = ($(card).data('category') || '').toString().trim();
                if (value) {
                    categories.add(value);
                }
            });

            Array.from(categories)
                .sort(function (a, b) {
                    return a.localeCompare(b);
                })
                .forEach(function (category) {
                    $category.append($('<option></option>').val(category.toLowerCase()).text(category));
                });
        },

        /**
         * Sync controls from current state.
         */
        syncControls: function () {
            $('#acs-filter-search').val(this.state.search);
            $('#acs-filter-category').val(this.state.category);
            $('#acs-filter-date').val(this.state.dateRange);
            $('#acs-sort-order').val(this.state.sort);
        },

        /**
         * Apply current filters, sorting, pagination, and grouping.
         */
        applyAgendaView: function () {
            const self = this;
            const $list = $('#acs-agenda-list');
            const $noResults = $('#acs-no-results');
            const now = Date.now();
            // data-date-ts is PHP mktime(0,0,0,...) — midnight of the event day.
            // Use start-of-today for lower-bound comparisons so today's events
            // are not excluded from week/month views mid-day.
            const todayStart = new Date(now);
            todayStart.setHours(0, 0, 0, 0);
            const todayStartMs = todayStart.getTime();
            const weekEnd = todayStartMs + (7 * 24 * 60 * 60 * 1000);
            const monthEndDate = new Date(todayStart);
            monthEndDate.setMonth(monthEndDate.getMonth() + 1);
            const monthEnd = monthEndDate.getTime();

            let filtered = this.cards.filter(function (card) {
                const $card = $(card);
                const category = ($card.data('category') || '').toString().toLowerCase();
                const title = ($card.data('title') || '').toString().toLowerCase();
                const intro = ($card.data('intro') || '').toString().toLowerCase();
                const location = ($card.data('location') || '').toString().toLowerCase();
                const dateTs = Number($card.data('date-ts')) * 1000;

                if (self.state.category && category !== self.state.category) {
                    return false;
                }

                if (self.state.search) {
                    const haystack = [title, intro, location].join(' ');
                    if (haystack.indexOf(self.state.search) === -1) {
                        return false;
                    }
                }

                if (self.state.dateRange === 'today') {
                    const date = new Date(dateTs);
                    const n = new Date(now);
                    if (
                        date.getFullYear() !== n.getFullYear() ||
                        date.getMonth() !== n.getMonth() ||
                        date.getDate() !== n.getDate()
                    ) {
                        return false;
                    }
                }

                if (self.state.dateRange === 'week' && (dateTs < todayStartMs || dateTs > weekEnd)) {
                    return false;
                }

                if (self.state.dateRange === 'month' && (dateTs < todayStartMs || dateTs > monthEnd)) {
                    return false;
                }

                return true;
            });

            filtered = filtered.sort(function (a, b) {
                const $a = $(a);
                const $b = $(b);
                const aDate = Number($a.data('date-ts'));
                const bDate = Number($b.data('date-ts'));
                const aTitle = ($a.data('title') || '').toString().toLowerCase();
                const bTitle = ($b.data('title') || '').toString().toLowerCase();

                if (self.state.sort === 'latest') {
                    return bDate - aDate;
                }

                if (self.state.sort === 'title') {
                    return aTitle.localeCompare(bTitle);
                }

                return aDate - bDate;
            });

            filtered.forEach(function (card) {
                $list.append(card);
            });

            const pageSize = DEFAULT_PAGE_SIZE;
            const total = filtered.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            if (this.state.page > totalPages) {
                this.state.page = totalPages;
            }

            const startIndex = (this.state.page - 1) * pageSize;
            const endIndex = startIndex + pageSize;

            this.cards.forEach(function (card) {
                $(card).hide();
            });

            const pageCards = filtered.slice(startIndex, endIndex);
            pageCards.forEach(function (card) {
                $(card).show();
            });

            this.renderMonthHeadings(pageCards);
            this.renderPagination(totalPages);
            this.updateResultsCount(total, startIndex, pageCards.length);

            if (total === 0) {
                $noResults.removeAttr('hidden').show();
            } else {
                $noResults.attr('hidden', 'hidden').hide();
            }

            $list.toggleClass('acs-compact-mode', this.state.compact);
            $('#acs-compact-toggle').attr('aria-pressed', this.state.compact ? 'true' : 'false');

            this.writeStateToUrl();
        },

        /**
         * Group visible cards by month heading.
         */
        renderMonthHeadings: function (visibleCards) {
            $('.acs-month-heading').remove();

            let lastGroup = '';
            visibleCards.forEach(function (card) {
                const $card = $(card);
                const monthGroup = ($card.data('month-group') || '').toString();

                if (monthGroup && monthGroup !== lastGroup) {
                    const $heading = $('<h2 class="acs-month-heading"></h2>').text(monthGroup);
                    $card.before($heading);
                    lastGroup = monthGroup;
                }
            });
        },

        /**
         * Render pagination buttons.
         *
         * Uses a windowed layout (first, current±1, last, with ellipses) so that
         * the number of DOM nodes stays constant regardless of total page count.
         */
        renderPagination: function (totalPages) {
            const agendaConfig = window.acsagmaAgenda || window.acsAgenda || {};
            const i18n = agendaConfig.i18n || {};
            const prevLabel = i18n.prev || 'Previous';
            const nextLabel = i18n.next || 'Next';
            const $pagination = $('#acs-pagination');

            if (!$pagination.length) {
                return;
            }

            $pagination.empty();

            if (totalPages <= 1) {
                return;
            }

            const currentPage = this.state.page;
            const prevDisabled = currentPage <= 1 ? ' disabled' : '';
            const nextDisabled = currentPage >= totalPages ? ' disabled' : '';

            $pagination.append('<button type="button" class="acs-page-link acs-page-prev" data-page="' + (currentPage - 1) + '"' + prevDisabled + '>' + prevLabel + '</button>');

            // Build a windowed page list: always show page 1, page totalPages, and
            // currentPage±1.  Insert ellipsis spans where there are hidden pages.
            const delta = 1;
            const rangeStart = Math.max(2, currentPage - delta);
            const rangeEnd   = Math.min(totalPages - 1, currentPage + delta);
            const pages = [1];

            if (rangeStart > 3) {
                pages.push(null); // ellipsis
            } else if (rangeStart === 3) {
                pages.push(2);   // only one page hidden — show it directly
            }

            for (let i = rangeStart; i <= rangeEnd; i++) {
                pages.push(i);
            }

            if (rangeEnd < totalPages - 2) {
                pages.push(null); // ellipsis
            } else if (rangeEnd === totalPages - 2) {
                pages.push(totalPages - 1); // only one page hidden — show it directly
            }

            pages.push(totalPages);

            pages.forEach(function (page) {
                if (page === null) {
                    $pagination.append($('<span class="acs-page-ellipsis" aria-hidden="true">\u2026</span>'));
                } else {
                    const activeClass  = page === currentPage ? ' is-active' : '';
                    const ariaCurrent  = page === currentPage ? ' aria-current="page"' : '';
                    $pagination.append('<button type="button" class="acs-page-link acs-page-number' + activeClass + '" data-page="' + page + '"' + ariaCurrent + '>' + page + '</button>');
                }
            });

            $pagination.append('<button type="button" class="acs-page-link acs-page-next" data-page="' + (currentPage + 1) + '"' + nextDisabled + '>' + nextLabel + '</button>');
        },

        /**
         * Update list summary text.
         */
        updateResultsCount: function (total, startIndex, countOnPage) {
            const agendaConfig = window.acsagmaAgenda || window.acsAgenda || {};
            const i18n = agendaConfig.i18n || {};
            const labelTemplate = i18n.resultsLabel || 'Showing %1$d-%2$d of %3$d events';
            const start = total === 0 ? 0 : startIndex + 1;
            const end = startIndex + countOnPage;
            const text = labelTemplate
                .replace('%1$d', String(start))
                .replace('%2$d', String(end))
                .replace('%3$d', String(total));

            $('#acs-results-count').text(text);
        },

        /**
         * Restore filter state from URL query params.
         */
        loadStateFromUrl: function () {
            const params = new URLSearchParams(window.location.search);
            this.state.search = (params.get('acs_search') || '').trim().toLowerCase();
            this.state.category = (params.get('acs_category') || '').trim().toLowerCase();
            this.state.dateRange = (params.get('acs_date') || 'all').trim();
            this.state.sort = (params.get('acs_sort') || 'soonest').trim();
            this.state.page = Math.max(1, parseInt(params.get('acs_page') || '1', 10));
            this.state.compact = params.get('acs_compact') === '1';
        },

        /**
         * Persist current state to URL query params.
         */
        writeStateToUrl: function () {
            const params = new URLSearchParams(window.location.search);

            const write = function (key, value, defaultValue) {
                if (!value || value === defaultValue) {
                    params.delete(key);
                } else {
                    params.set(key, value);
                }
            };

            write('acs_search', this.state.search, '');
            write('acs_category', this.state.category, '');
            write('acs_date', this.state.dateRange, 'all');
            write('acs_sort', this.state.sort, 'soonest');
            write('acs_page', String(this.state.page), '1');
            write('acs_compact', this.state.compact ? '1' : '', '');

            const query = params.toString();
            const nextUrl = query ? window.location.pathname + '?' + query : window.location.pathname;
            window.history.replaceState({}, '', nextUrl);
        },

        /**
         * Open lightbox with full-size image
         */
        openLightbox: function (e) {
            const $img = $(e.currentTarget);
            const fullSrc = $img.data('full-src') || $img.attr('src');
            const alt = $img.attr('alt') || '';

            // Create lightbox if it doesn't exist
            if ($('#acs-lightbox-overlay').length === 0) {
                $('body').append(
                    '<div id="acs-lightbox-overlay" role="dialog" aria-modal="true" aria-label="Image preview">' +
                    '<button class="acs-lightbox-close" aria-label="Close">&times;</button>' +
                    '<img class="acs-lightbox-image" src="" alt="" />' +
                    '</div>'
                );
            }

            const $overlay = $('#acs-lightbox-overlay');
            const $lightboxImg = $overlay.find('.acs-lightbox-image');

            $lightboxImg.attr('src', fullSrc).attr('alt', alt);
            $overlay.addClass('active');

            // Lock body scroll
            $('body').addClass('acs-lightbox-open');

            // Focus the close button for accessibility
            $overlay.find('.acs-lightbox-close').focus();
        },

        /**
         * Close lightbox
         */
        closeLightbox: function (e) {
            // Only close if clicking overlay background or close button
            if (e && $(e.target).hasClass('acs-lightbox-image')) {
                return;
            }

            $('#acs-lightbox-overlay').removeClass('active');
            $('body').removeClass('acs-lightbox-open');
        },

        /**
         * Handle Read More and Contact button clicks.
         */
        handleDialogTrigger: function (e) {
            e.preventDefault();

            const agendaConfig = window.acsagmaAgenda || window.acsAgenda;
            const $button = $(e.currentTarget);
            const postId = $button.data('postid');
            const sectionId = $button.data('id');
            const href = $button.data('href');
            const eventTitle = ($button.data('event-title') || '').toString();
            const eventDates = ($button.data('event-dates') || '').toString();
            const eventIntro = ($button.data('event-intro') || '').toString();
            const dialogMode = $button.hasClass('acs-contact-trigger') ? 'contact' : 'readmore';

            if (!agendaConfig || !agendaConfig.ajaxUrl) {
                return;
            }

            $button.prop('disabled', true).addClass('is-loading').attr('aria-busy', 'true');

            $.ajax({
                url: agendaConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acsagma_read_more',
                    postid: postId,
                    href: href,
                    event_title: eventTitle,
                    event_dates: eventDates,
                    event_intro: eventIntro,
                    dialog_mode: dialogMode,
                    nonce: agendaConfig.nonce,
                },
                success: function (response) {
                    if (response && typeof response === 'object' && response.success === false) {
                        ACSAgendaFrontend.showReadMoreError();
                        return;
                    }

                    $('#postid').html(response);
                    ACSAgendaFrontend.showDialog(sectionId);
                },
                error: function () {
                    ACSAgendaFrontend.showReadMoreError();
                },
                complete: function () {
                    $button.prop('disabled', false).removeClass('is-loading').removeAttr('aria-busy');
                }
            });
        },

        /**
         * Submit the built-in contact form from read-more dialog.
         */
        handleContactFormSubmit: function (e) {
            const agendaConfig = window.acsagmaAgenda || window.acsAgenda || {};
            const i18n = agendaConfig.i18n || {};
            const $form = $(e.currentTarget);
            const $message = $form.find('.acs-contact-form-message');
            const $submit = $form.find('.acs-contact-submit');
            const submitLabel = i18n.contactFormSubmitLabel || 'Send message';

            if (!$form.length || !agendaConfig.ajaxUrl) {
                return;
            }

            $message.removeClass('is-error is-success').empty();
            const validation = this.validateContactForm($form, i18n);
            if (!validation.valid) {
                $message.addClass('is-error').text(validation.message);
                return;
            }

            $submit.prop('disabled', true).attr('aria-busy', 'true').text(i18n.contactFormSending || 'Sending...');

            const payload = $form.serialize();

            $.ajax({
                url: agendaConfig.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (response) {
                    if (response && response.success) {
                        const successMessage = response.data && response.data.message
                            ? response.data.message
                            : (i18n.contactFormSuccess || 'Thanks! Your message has been sent.');

                        $message.addClass('is-success').text(successMessage);
                        $form[0].reset();
                        return;
                    }

                    const errorMessage = response && response.data && response.data.message
                        ? response.data.message
                        : (i18n.contactFormError || 'Unable to send your message. Please try again.');
                    $message.addClass('is-error').text(errorMessage);
                },
                error: function () {
                    $message.addClass('is-error').text(i18n.contactFormError || 'Unable to send your message. Please try again.');
                },
                complete: function () {
                    $submit.prop('disabled', false).removeAttr('aria-busy').text(submitLabel);
                }
            });
        },

        /**
         * Validate contact form fields and highlight invalid inputs.
         */
        validateContactForm: function ($form, i18n) {
            const $name = $form.find('input[name="name"]');
            const $email = $form.find('input[name="email"]');
            const $message = $form.find('textarea[name="message"]');

            const nameValue = ($name.val() || '').toString().trim();
            const emailValue = ($email.val() || '').toString().trim();
            const messageValue = ($message.val() || '').toString().trim();

            let firstError = '';

            const markInvalid = function ($field, errorMessage) {
                $field.addClass('is-invalid').attr('aria-invalid', 'true');
                if (!firstError) {
                    firstError = errorMessage;
                }
            };

            $form.find('input, textarea').removeClass('is-invalid').removeAttr('aria-invalid');

            if (!nameValue) {
                markInvalid($name, i18n.contactFormNameRequired || 'Please enter your name.');
            }

            if (!emailValue) {
                markInvalid($email, i18n.contactFormEmailRequired || 'Please enter your email address.');
            } else if (!this.isValidContactEmail(emailValue)) {
                markInvalid($email, i18n.contactFormInvalidEmail || 'Please enter a valid email address.');
            }

            if (!messageValue) {
                markInvalid($message, i18n.contactFormMessageRequired || 'Please enter your message.');
            }

            if (firstError) {
                return {
                    valid: false,
                    message: firstError,
                };
            }

            return {
                valid: true,
                message: '',
            };
        },

        /**
         * Validate contact email with stricter domain rules (requires multi-char TLD).
         */
        isValidContactEmail: function (email) {
            const domainLabel = '[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?';
            const tldLabel = '(?:[A-Za-z]{2,63}|xn--[A-Za-z0-9](?:[A-Za-z0-9-]{0,57}[A-Za-z0-9])?)';
            const emailPattern = new RegExp(
                '^(?=.{1,254}$)(?=.{1,64}@)[A-Za-z0-9.!#$%&\'*+/=?^_`{|}~-]+@(?:' + domainLabel + '\\.)+' + tldLabel + '$'
            );
            return emailPattern.test((email || '').toString().trim());
        },

        /**
         * Show a non-blocking error if details cannot be loaded.
         */
        showReadMoreError: function () {
            const agendaConfig = window.acsagmaAgenda || window.acsAgenda || {};
            const i18n = agendaConfig.i18n || {};
            const message = i18n.readMoreError || 'Unable to load details. Please try again.';

            if ($('#acs-readmore-error').length) {
                return;
            }

            const $message = $('<div id="acs-readmore-error" role="status" aria-live="polite"></div>');
            $message.text(message);
            $('body').append($message);

            setTimeout(function () {
                $message.fadeOut(200, function () {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Show the dialog
         */
        showDialog: function (sectionId) {
            const $dialog = $('#dialog');

            if (!$dialog.length) {
                return;
            }

            this.lastFocusedElement = document.activeElement;
            $dialog.addClass('shown');
            $dialog.attr('aria-hidden', 'false');

            // Store scroll position
            const scrollY = window.scrollY;
            document.documentElement.style.setProperty('--scroll-y', scrollY + 'px');

            // Lock body scroll
            $('body').css({
                position: 'fixed',
                top: '-' + scrollY + 'px',
                width: '100%',
            });

            // Store section ID for scroll-back
            $dialog.data('section-id', sectionId);

            const $closeButton = $dialog.find('#close').first();
            if ($closeButton.length) {
                $closeButton.trigger('focus');
            }
        },

        /**
         * Track scroll position
         */
        trackScroll: function () {
            document.documentElement.style.setProperty('--scroll-y', window.scrollY + 'px');
        },
    };

    /**
     * Close the dialog (global function for onclick handler)
     */
    window.closeDialog = function () {
        const $body = $('body');
        const scrollY = $body.css('top');
        const $dialog = $('#dialog');
        const sectionId = $dialog.data('section-id');

        // Unlock body scroll
        $body.css({
            position: '',
            top: '',
            width: '',
        });

        // Restore scroll position
        window.scrollTo(0, parseInt(scrollY || '0') * -1);

        // Hide dialog
        $dialog.removeClass('shown');
        $dialog.attr('aria-hidden', 'true');

        if (ACSAgendaFrontend.lastFocusedElement && typeof ACSAgendaFrontend.lastFocusedElement.focus === 'function') {
            ACSAgendaFrontend.lastFocusedElement.focus();
        }
        ACSAgendaFrontend.lastFocusedElement = null;

        // Scroll to original section
        if (sectionId) {
            const $section = $('#' + sectionId);
            if ($section.length) {
                $section[0].scrollIntoView({ behavior: 'smooth' });
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        ACSAgendaFrontend.init();
    });
})(jQuery);
