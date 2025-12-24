/**
 * ACS Agenda Manager - Frontend JavaScript
 *
 * @package ACSAgendaManager
 */

(function($) {
    'use strict';

    const ACSAgendaFrontend = {
        /**
         * Initialize the frontend functionality
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).on('click', '.readmore', this.handleReadMore.bind(this));
            $(window).on('scroll', this.trackScroll);
        },

        /**
         * Handle "Read More" button click
         */
        handleReadMore: function(e) {
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
                    href: href
                },
                success: function(response) {
                    $('#postid').html(response);
                    ACSAgendaFrontend.showDialog(sectionId);
                },
                error: function() {
                    console.error('Failed to load content');
                }
            });
        },

        /**
         * Show the dialog
         */
        showDialog: function(sectionId) {
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
                width: '100%'
            });

            // Store section ID for scroll-back
            $dialog.data('section-id', sectionId);
        },

        /**
         * Track scroll position
         */
        trackScroll: function() {
            document.documentElement.style.setProperty('--scroll-y', window.scrollY + 'px');
        }
    };

    /**
     * Close the dialog (global function for onclick handler)
     */
    window.closeDialog = function() {
        const $body = $('body');
        const scrollY = $body.css('top');
        const $dialog = $('#dialog');
        const sectionId = $dialog.data('section-id');
        
        // Unlock body scroll
        $body.css({
            position: '',
            top: '',
            width: ''
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
    $(document).ready(function() {
        ACSAgendaFrontend.init();
    });

})(jQuery);
