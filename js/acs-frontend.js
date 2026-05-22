/**
 * ACS Agenda Manager - Frontend JavaScript
 *
 * @package ACSAgendaManager
 */

(function ($) {
    'use strict';

    const ACSAgendaFrontend = {
        lastFocusedElement: null,

        /**
         * Initialize the frontend functionality
         */
        init: function () {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            const self = this;

            $(document).on('click', '.readmore', this.handleReadMore.bind(this));
            $(window).on('scroll', this.trackScroll);

            // Read more dialog close handlers
            $(document).on('click', '#dialog', function (e) {
                if (e.target === e.currentTarget) {
                    window.closeDialog();
                }
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
         * Handle "Read More" button click
         */
        handleReadMore: function (e) {
            e.preventDefault();

            const agendaConfig = window.acsagmaAgenda || window.acsAgenda;
            const $button = $(e.currentTarget);
            const postId = $button.data('postid');
            const sectionId = $button.data('id');
            const href = $button.data('href');

            if (!postId || !agendaConfig || !agendaConfig.ajaxUrl) {
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
         * Show a non-blocking error if details cannot be loaded.
         */
        showReadMoreError: function () {
            if ($('#acs-readmore-error').length) {
                return;
            }

            const $message = $('<div id="acs-readmore-error" role="status" aria-live="polite">Unable to load details. Please try again.</div>');
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
