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
class ACS_Template {
    
    /**
     * Render the main agenda display
     */
    public static function render_agenda(array $events): string {
        $output = self::render_admin_link();
        $output .= '<div class="container-agenda">';
        
        foreach ($events as $index => $event) {
            $output .= self::render_event_card($event, $index);
        }
        
        $output .= '</div>';
        $output .= '<div id="postid"></div>';
        
        return $output;
    }
    
    /**
     * Render admin link if user has permissions
     */
    private static function render_admin_link(): string {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return '';
        }
        
        $admin_url = esc_url(admin_url('admin.php?page=agenda'));
        
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
        
        $output = '<div class="acsagenda">';
        
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
            $parsed = ACS_Event::parse_date($date_info['date']);
            
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
            ? implode(' - ', [min($unique_years), max($unique_years)])
            : (string) reset($unique_years);
        
        return sprintf(
            '<div class="column-left">
                <div class="acsAgenda-title">
                    <h4 style="color: white; font-weight:bold; padding-top: 0.4em;">%s</h4>
                </div>
                <div class="column-left-container">%s</div>
                <div class="placement">
                    <h5 style="padding-top:1em">
                        <span class="dashicons dashicons-location"></span>&nbsp;%s
                    </h5>
                </div>
            </div>',
            esc_html($year_display),
            $dates_html,
            esc_html($event['emplacement'])
        );
    }
    
    /**
     * Render the content column
     */
    private static function render_content_column(array $event, string $section_id, int $post_id): string {
        $status_badge = '';
        if ($event['status'] === 'today') {
            $status_badge = '<span class="blink_me">' . esc_html__('Today', 'acs-agenda-manager') . ':&nbsp;</span>';
        } elseif ($event['status'] === 'running') {
            $status_badge = '<span class="blink_me">' . esc_html__('Running', 'acs-agenda-manager') . ':&nbsp;</span>';
        }
        
        return sprintf(
            '<div class="column-center" id="%s">
                <div class="acsAgenda-title">
                    <h4 style="color: white; font-weight:bold; padding-top: 0.4em;">%s%s</h4>
                </div>
                <h3 style="text-align:center;">%s</h3>
                <div class="course-description acsAgenda">
                    <h5>%s</h5>
                    <button data-href="%s" class="readmore show" data-postid="%d" data-id="%s">%s</button>
                </div>
            </div>',
            esc_attr($section_id),
            $status_badge,
            esc_html($event['categorie']),
            esc_html($event['title']),
            esc_html($event['intro']),
            esc_url($event['link']),
            $post_id,
            esc_attr($section_id),
            esc_html__('Read more', 'acs-agenda-manager')
        );
    }
    
    /**
     * Render the image column
     */
    private static function render_image_column(array $event): string {
        return sprintf(
            '<div class="column-right">
                <img src="%s" class="image-agenda" alt="%s" loading="lazy" />
            </div>',
            esc_url($event['image']),
            esc_attr($event['title'])
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
                <div id="dialog">
                    <button id="close" onclick="closeDialog()">&times;</button>
                    <h2 style="text-align: center;">%s</h2>
                    <p style="text-align: center;">
                        <a href="%s" target="_blank">%s</a>
                    </p>
                    %s
                </div>
            </div>',
            esc_html(get_the_title($post)),
            esc_url($href),
            esc_html__('Go to page', 'acs-agenda-manager'),
            do_shortcode($post->post_content)
        );
    }
}
