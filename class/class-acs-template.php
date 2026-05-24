<?php
/**
 * Template rendering for ACS Agenda Manager
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;

/**
 * Handles all template rendering
 */
class ACSAGMA_Template {

    /**
     * Return allowed HTML for the read-more dialog response.
     */
    public static function get_allowed_read_more_html(): array {
        $allowed = wp_kses_allowed_html('post');

        $extend_allowed_attributes = static function (string $tag, array $attributes) use (&$allowed): void {
            $existing = (isset($allowed[$tag]) && is_array($allowed[$tag])) ? $allowed[$tag] : [];
            $allowed[$tag] = array_merge($existing, $attributes);
        };

        $extend_allowed_attributes('form', [
            'action' => true,
            'method' => true,
            'class' => true,
            'id' => true,
            'novalidate' => true,
        ]);
        $extend_allowed_attributes('label', [
            'for' => true,
            'class' => true,
        ]);
        $extend_allowed_attributes('input', [
            'type' => true,
            'name' => true,
            'value' => true,
            'class' => true,
            'id' => true,
            'required' => true,
            'autocomplete' => true,
            'tabindex' => true,
            'aria-hidden' => true,
        ]);
        $extend_allowed_attributes('textarea', [
            'name' => true,
            'class' => true,
            'id' => true,
            'required' => true,
            'rows' => true,
        ]);
        $extend_allowed_attributes('button', [
            'type' => true,
            'class' => true,
            'id' => true,
            'aria-label' => true,
            'aria-busy' => true,
        ]);
        $extend_allowed_attributes('small', [
            'class' => true,
        ]);
        $extend_allowed_attributes('output', [
            'class' => true,
            'aria-live' => true,
        ]);
        $extend_allowed_attributes('div', [
            'class' => true,
            'id' => true,
            'role' => true,
            'aria-modal' => true,
            'aria-labelledby' => true,
            'aria-live' => true,
            'aria-hidden' => true,
        ]);
        $extend_allowed_attributes('span', [
            'class' => true,
            'aria-hidden' => true,
            'focusable' => true,
        ]);

        return $allowed;
    }

    /**
     * Render the main agenda display
     */
    public static function render_agenda(array $events): string {
        $output = self::render_admin_link();
        $output .= self::render_agenda_controls();
        $output .= '<div class="container-agenda" id="acs-agenda-list">';

        foreach ($events as $index => $event) {
            $output .= self::render_event_card($event, $index);
        }

        $output .= '</div>';
        $output .= '<p id="acs-no-results" class="acs-no-results" hidden>'
            . esc_html__('No events match your filters.', 'acs-agenda-manager')
            . '</p>';
        $output .= '<div id="acs-pagination" class="acs-pagination" role="navigation" aria-label="'
            . esc_attr__('Agenda pagination', 'acs-agenda-manager')
            . '"></div>';
        $output .= '<div id="postid"></div>';

        return $output;
    }

    /**
     * Render frontend toolbar controls.
     */
    private static function render_agenda_controls(): string {
        return sprintf(
            '<div class="acs-agenda-toolbar" role="region" aria-label="%s">
                <div class="acs-agenda-toolbar-grid">
                    <label class="acs-filter-item">
                        <span>%s</span>
                        <input type="search" id="acs-filter-search" placeholder="%s" />
                    </label>
                    <label class="acs-filter-item">
                        <span>%s</span>
                        <select id="acs-filter-category">
                            <option value="">%s</option>
                        </select>
                    </label>
                    <label class="acs-filter-item">
                        <span>%s</span>
                        <select id="acs-filter-date">
                            <option value="all">%s</option>
                            <option value="today">%s</option>
                            <option value="week">%s</option>
                            <option value="month">%s</option>
                        </select>
                    </label>
                    <label class="acs-filter-item">
                        <span>%s</span>
                        <select id="acs-sort-order">
                            <option value="soonest">%s</option>
                            <option value="latest">%s</option>
                            <option value="title">%s</option>
                        </select>
                    </label>
                    <button type="button" id="acs-compact-toggle" class="acs-compact-toggle" aria-pressed="false">%s</button>
                </div>
                <p id="acs-results-count" class="acs-results-count"></p>
            </div>',
            esc_attr__('Agenda filters and sorting', 'acs-agenda-manager'),
            esc_html__('Search', 'acs-agenda-manager'),
            esc_attr__('Search title or description', 'acs-agenda-manager'),
            esc_html__('Category', 'acs-agenda-manager'),
            esc_html__('All categories', 'acs-agenda-manager'),
            esc_html__('Date', 'acs-agenda-manager'),
            esc_html__('All dates', 'acs-agenda-manager'),
            esc_html__('Today', 'acs-agenda-manager'),
            esc_html__('This week', 'acs-agenda-manager'),
            esc_html__('This month', 'acs-agenda-manager'),
            esc_html__('Sort', 'acs-agenda-manager'),
            esc_html__('Soonest first', 'acs-agenda-manager'),
            esc_html__('Latest first', 'acs-agenda-manager'),
            esc_html__('Title A-Z', 'acs-agenda-manager'),
            esc_html__('Compact mode', 'acs-agenda-manager')
        );
    }

    /**
     * Render admin link if user has permissions
     */
    private static function render_admin_link(): string {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return '';
        }

        $admin_url = esc_url(admin_url('admin.php?page=acsagma-agenda'));

        return sprintf(
            '<h3><a href="%s" class="button4 warning">%s</a></h3>',
            $admin_url,
            esc_html__('Agenda Administration', 'acs-agenda-manager')
        );
    }

    /**
     * Render a single event card
     */
    private static function render_event_card(array $event, int $index): string {
        $post_id = url_to_postid($event['link']);
        $section_id = 'section-' . $index;

        // For candopartial=2 events, leading dates in dates_info may be expired.
        // Use the first non-expired date so data-date-ts reflects when the event
        // is actually relevant, not when it started.
        $first_date = '';
        foreach ($event['dates_info'] as $date_info) {
            if (empty($date_info['expired'])) {
                $first_date = $date_info['date'];
                break;
            }
        }
        if ('' === $first_date) {
            $first_date = $event['dates'][0] ?? '';
        }
        $parsed = !empty($first_date) ? ACSAGMA_Event::parse_date($first_date) : [];
        $date_ts = (int) ($parsed['timestamp'] ?? 0);
        $month_group = !empty($parsed)
            ? sprintf('%s %d', $parsed['month_name'], $parsed['year'])
            : '';

        $output = sprintf(
            '<div class="acsagenda" data-category="%s" data-title="%s" data-location="%s" data-intro="%s" data-date-ts="%d" data-month-group="%s">',
            esc_attr((string) ($event['categorie'] ?? '')),
            esc_attr((string) ($event['title'] ?? '')),
            esc_attr((string) ($event['emplacement'] ?? '')),
            esc_attr((string) ($event['intro'] ?? '')),
            $date_ts,
            esc_attr($month_group)
        );

        // Left column - dates
        $output .= self::render_date_column($event);

        // Center column - content
        $output .= self::render_content_column($event, $section_id, $post_id);

        // Right column - image
        $output .= self::render_image_column($event);

        $output .= '</div>';

        return $output;
    }

    /**
     * Render the date column
     */
    private static function render_date_column(array $event): string {
        $years = [];
        $dates_html = '';

        foreach ($event['dates_info'] as $date_info) {
            $parsed = ACSAGMA_Event::parse_date($date_info['date']);

            if (empty($parsed)) {
                continue;
            }

            $years[] = $parsed['year'];

            $class = 'ACSdate';
            if (!empty($date_info['today'])) {
                $class .= ' blink_me';
            } elseif (!empty($date_info['expired'])) {
                $class .= ' acsagendaexpired';
            }

            $dates_html .= sprintf(
                '<span class="%s">
                    <span class="month">%s</span>
                    <span class="day">%s</span>
                    <span class="week">%s</span>
                </span>',
                esc_attr($class),
                esc_html($parsed['month_name']),
                esc_html($parsed['day']),
                esc_html($parsed['weekday'])
            );
        }

        // Determine year display
        $unique_years = array_unique($years);
        $year_display = count($unique_years) > 1
            ? implode('-', [min($unique_years), max($unique_years)])
            : (string) reset($unique_years);

        // Build location HTML only if location exists
        $location_html = '';
        if (!empty($event['emplacement'])) {
            $location_html = sprintf(
                '<div class="placement">
                    <h5><span class="dashicons dashicons-location"></span> %s</h5>
                </div>',
                esc_html($event['emplacement'])
            );
        }

        return sprintf(
            '<div class="column-left">
                <div class="column-left-container">%s</div>
                <span class="ACSyear">%s</span>
                %s
            </div>',
            $dates_html,
            esc_html($year_display),
            $location_html
        );
    }

    /**
     * Render the content column
     */
    private static function render_content_column(array $event, string $section_id, int $post_id): string {
        $status_badge = '';
        if ($event['status'] === 'today') {
            $status_badge = '<span class="status-badge status-today">' . esc_html__('Today', 'acs-agenda-manager') . '</span>';
        } elseif ($event['status'] === 'running') {
            $status_badge = '<span class="status-badge status-running">' . esc_html__('Running', 'acs-agenda-manager') . '</span>';
        }

        // Build category badge if exists
        $category_html = '';
        if (!empty($event['categorie'])) {
            $category_html = sprintf(
                '<span class="category-badge">%s</span>',
                esc_html($event['categorie'])
            );
        }

        $event_dates = implode(', ', array_map('sanitize_text_field', $event['dates'] ?? []));

        $read_more_html = '';
        if (self::should_show_read_more($post_id)) {
            $read_more_html = sprintf(
                '<button type="button" data-href="%s" class="readmore show" data-postid="%d" data-id="%s" data-event-title="%s" data-event-dates="%s" data-event-intro="%s">
                    %s <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true" focusable="false"></span>
                </button>',
                esc_url($event['link']),
                $post_id,
                esc_attr($section_id),
                esc_attr((string) ($event['title'] ?? '')),
                esc_attr($event_dates),
                esc_attr((string) ($event['intro'] ?? '')),
                esc_html__('Read more', 'acs-agenda-manager')
            );
        }

        $contact_html = '';
        if ((bool) get_option('acsagma_contact_form_enabled', true)) {
            $contact_html = sprintf(
                '<button type="button" data-href="%s" class="acs-contact-trigger" data-postid="%d" data-id="%s" data-event-title="%s" data-event-dates="%s" data-event-intro="%s">
                    <span class="dashicons dashicons-email-alt" aria-hidden="true" focusable="false"></span>
                    %s
                </button>',
                esc_url($event['link']),
                $post_id,
                esc_attr($section_id),
                esc_attr((string) ($event['title'] ?? '')),
                esc_attr($event_dates),
                esc_attr((string) ($event['intro'] ?? '')),
                esc_html__('Ask a question', 'acs-agenda-manager')
            );
        }

        $actions_html = '';
        if ($read_more_html !== '' || $contact_html !== '') {
            $actions_html = '<div class="acs-event-actions">' . $read_more_html . $contact_html . '</div>';
        }

        return sprintf(
            '<div class="column-center" id="%s">
                <div class="event-header">
                    %s%s
                </div>
                <h3 class="event-title">%s</h3>
                <div class="event-intro">
                    <p>%s</p>
                </div>
                %s
            </div>',
            esc_attr($section_id),
            $status_badge,
            $category_html,
            esc_html($event['title']),
            esc_html($event['intro']),
            $actions_html
        );
    }

    /**
     * Determine whether Read more should be displayed.
     */
    private static function should_show_read_more(int $post_id): bool {
        if ($post_id <= 0) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post instanceof WP_Post) {
            return false;
        }

        $content_text = trim(wp_strip_all_tags(strip_shortcodes((string) $post->post_content)));

        return $content_text !== '';
    }

    /**
     * Render the image column
     */
    private static function render_image_column(array $event): string {
        // Skip if no image provided
        if (empty($event['image'])) {
            return '';
        }

        return sprintf(
            '<div class="column-right">
                <img src="%s" class="image-agenda" alt="%s" loading="lazy" data-full-src="%s" role="button" tabindex="0" />
                <span class="image-expand-hint">
                    <span class="dashicons dashicons-search"></span>
                </span>
            </div>',
            esc_url($event['image']),
            esc_attr($event['title']),
            esc_url($event['image'])
        );
    }

    /**
     * Render the read more dialog content
     */
    public static function render_read_more_dialog(?WP_Post $post, string $href, array $context = []): string {
        $event_title = sanitize_text_field((string) ($context['title'] ?? ''));
        $event_dates = sanitize_text_field((string) ($context['dates'] ?? ''));
        $event_intro = sanitize_text_field((string) ($context['intro'] ?? ''));
        $dialog_mode = sanitize_key((string) ($context['mode'] ?? 'readmore'));
        $post_id = absint($context['post_id'] ?? 0);

        $dialog_title = '';
        if ($dialog_mode === 'contact') {
            $dialog_title = $event_title;
        } elseif ($post instanceof WP_Post) {
            $dialog_title = get_the_title($post);
        }
        if ($dialog_title === '') {
            $dialog_title = $event_title;
        }
        if ($dialog_title === '') {
            $dialog_title = __('Event details', 'acs-agenda-manager');
        }

        $content_html = '';
        if ($dialog_mode !== 'contact' && $post instanceof WP_Post) {
            $content_html = do_shortcode($post->post_content);
        } elseif ($event_intro !== '') {
            $content_html = wpautop(esc_html($event_intro));
        }

        $link_html = '';
        if ($dialog_mode !== 'contact' && $href !== '') {
            $link_html = sprintf(
                '<p class="acs-dialog-link"><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
                esc_url($href),
                esc_html__('Go to page', 'acs-agenda-manager')
            );
        }

        return sprintf(
            '<div id="postdata">
                <div id="dialog" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="acs-readmore-title">
                    <div class="acs-dialog-panel">
                        <button id="close" type="button" aria-label="%s">&times;</button>
                        <h2 id="acs-readmore-title">%s</h2>
                        %s
                        <div class="acs-readmore-content">%s</div>
                        %s
                    </div>
                </div>
            </div>',
            esc_attr__('Close dialog', 'acs-agenda-manager'),
            esc_html($dialog_title),
            $link_html,
            $content_html,
            $dialog_mode === 'contact'
                ? self::render_contact_form([
                    'title' => $event_title,
                    'dates' => $event_dates,
                    'href' => $href,
                    'post_id' => $post_id,
                ])
                : ''
        );
    }

    /**
     * Render the built-in contact form in the read-more dialog.
     */
    private static function render_contact_form(array $context): string {
        $enabled = (bool) get_option('acsagma_contact_form_enabled', true);
        if (!$enabled) {
            return '';
        }

        $show_phone = (bool) get_option('acsagma_contact_form_show_phone', false);
        $event_title = sanitize_text_field((string) ($context['title'] ?? ''));
        $event_dates = sanitize_text_field((string) ($context['dates'] ?? ''));
        $event_href = esc_url_raw((string) ($context['href'] ?? ''));
        $post_id = absint($context['post_id'] ?? 0);

        ob_start();
        ?>
        <div class="acs-contact-form-wrap">
            <h3 class="acs-contact-form-title"><?php esc_html_e('Contact the organizer', 'acs-agenda-manager'); ?></h3>
            <p class="acs-contact-form-intro"><?php esc_html_e('Ask a question about this event and we will get back to you by email.', 'acs-agenda-manager'); ?></p>

            <form class="acs-contact-form" method="post" novalidate>
                <output class="acs-contact-form-message" aria-live="polite"></output>

                <input type="hidden" name="action" value="acsagma_contact_form_submit" />
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('acsagma_contact_form_nonce')); ?>" />
                <input type="hidden" name="event_title" value="<?php echo esc_attr($event_title); ?>" />
                <input type="hidden" name="event_dates" value="<?php echo esc_attr($event_dates); ?>" />
                <input type="hidden" name="event_href" value="<?php echo esc_attr($event_href); ?>" />
                <input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post_id); ?>" />
                <input type="text" name="acsagma_contact_company" value="" tabindex="-1" autocomplete="off" class="acs-contact-honeypot" aria-hidden="true" />

                <div class="acs-contact-form-grid">
                    <label>
                        <span><?php esc_html_e('Name', 'acs-agenda-manager'); ?></span>
                        <input type="text" name="name" required class="acs-contact-input" />
                    </label>
                    <label>
                        <span><?php esc_html_e('Email', 'acs-agenda-manager'); ?></span>
                        <input type="email" name="email" required class="acs-contact-input" />
                    </label>
                    <?php if ($show_phone) : ?>
                        <label>
                            <span><?php esc_html_e('Phone', 'acs-agenda-manager'); ?></span>
                            <input type="tel" name="phone" class="acs-contact-input" />
                        </label>
                    <?php endif; ?>
                    <label class="acs-contact-message-field">
                        <span><?php esc_html_e('Message', 'acs-agenda-manager'); ?></span>
                        <textarea name="message" rows="5" required class="acs-contact-textarea"></textarea>
                    </label>
                </div>

                <button type="submit" class="acs-contact-submit button button-primary"><?php esc_html_e('Send message', 'acs-agenda-manager'); ?></button>
            </form>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}
