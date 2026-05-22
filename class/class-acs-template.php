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
        $output .= '<div id="acs-pagination" class="acs-pagination" aria-label="'
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
        $first_date = $event['dates'][0] ?? '';
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

        // Contact form shortcode if available
        $output .= self::render_contact_form($event);

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

        $read_more_html = '';
        if (self::should_show_read_more($post_id)) {
            $read_more_html = sprintf(
                '<button data-href="%s" class="readmore show" data-postid="%d" data-id="%s">
                    %s <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>',
                esc_url($event['link']),
                $post_id,
                esc_attr($section_id),
                esc_html__('Read more', 'acs-agenda-manager')
            );
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
            $read_more_html
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
     * Render contact form shortcode if available
     */
    private static function render_contact_form(array $event): string {
        if (!shortcode_exists('ACScontactform')) {
            return '';
        }

        $shortcode = sprintf(
            '[ACScontactform dates="%s" subject="%s" price="%s" account="%s" candopartial="%s" redirect="%s"]',
            esc_attr(implode(',', $event['dates'])),
            esc_attr($event['title']),
            esc_attr($event['price']),
            esc_attr($event['account']),
            esc_attr($event['candopartial']),
            esc_attr($event['redirect'])
        );

        return do_shortcode($shortcode);
    }

    /**
     * Render the read more dialog content
     */
    public static function render_read_more_dialog(WP_Post $post, string $href): string {
        return sprintf(
            '<div id="postdata">
                <div id="dialog" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="acs-readmore-title">
                    <div class="acs-dialog-panel">
                        <button id="close" type="button" onclick="closeDialog()" aria-label="%s">&times;</button>
                        <h2 id="acs-readmore-title">%s</h2>
                        <p class="acs-dialog-link">
                            <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>
                        </p>
                        %s
                    </div>
                </div>
            </div>',
            esc_attr__('Close dialog', 'acs-agenda-manager'),
            esc_html(get_the_title($post)),
            esc_url($href),
            esc_html__('Go to page', 'acs-agenda-manager'),
            do_shortcode($post->post_content)
        );
    }
}
