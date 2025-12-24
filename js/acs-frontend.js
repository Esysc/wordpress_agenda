/**
 * ACS Agenda Manager - Frontend JavaScript
 *
 * @package ACSAgendaManager
 */

(function ($) {
    'use strict';

    const ACSAgendaFrontend = {
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

            const $button = $(e.currentTarget);
            const postId = $button.data('postid');
            const sectionId = $button.data('id');
            const href = $button.data('href');

            if (!postId) {
                return;
            }

            $.ajax({
                url: acsAgenda.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'read_more',
                    postid: postId,
                    href: href,
                },
                success: function (response) {
                    $('#postid').html(response);
                    ACSAgendaFrontend.showDialog(sectionId);
                },
                error: function () {
                    console.error('Failed to load content');
                },
            });
        },

        /**
         * Show the dialog
         */
        showDialog: function (sectionId) {
            const $dialog = $('#dialog');

            if (!$dialog.length) {
                return;
            }

            $dialog.addClass('shown');

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
