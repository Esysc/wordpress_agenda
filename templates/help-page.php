<?php
/**
 * Help page template
 *
 * @package ACSAgendaManager
 * @since 3.0.0
 */

defined('ABSPATH') || exit;

$toc = ACS_Help::get_toc();
$agenda_page = get_option('acsagma_page', 'Agenda');
?>

<div class="wrap acs-help-page">
    <h1><?php esc_html_e('ACS Agenda Manager - User Guide', 'acs-agenda-manager'); ?></h1>

    <div class="acs-help-container">
        <!-- Table of Contents -->
        <div class="acs-help-toc">
            <h2><?php esc_html_e('Contents', 'acs-agenda-manager'); ?></h2>
            <ul>
                <?php foreach ($toc as $id => $title) : ?>
                    <li><a href="#<?php echo esc_attr($id); ?>"><?php echo esc_html($title); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="acs-help-content">

            <!-- Getting Started -->
            <section id="getting-started" class="acs-help-section">
                <h2><?php esc_html_e('Getting Started', 'acs-agenda-manager'); ?></h2>
                <p><?php esc_html_e('ACS Agenda Manager allows you to create and manage an event agenda on your WordPress site. When activated, the plugin automatically creates an Agenda page with the shortcode included.', 'acs-agenda-manager'); ?></p>

                <h3><?php esc_html_e('Quick Start', 'acs-agenda-manager'); ?></h3>
                <ol>
                    <li><?php esc_html_e('Go to Agenda in the admin menu to view your events list', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Click "Add New Event" to create your first event', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Fill in the event details and click Add', 'acs-agenda-manager'); ?></li>
                    <?php
                    /* translators: %s: agenda page name. */
                    $page_label = sprintf('<strong>%s</strong>', esc_html($agenda_page));
                    ?>
                    <li>
                        <?php
                        /* translators: %s: agenda page name. */
                        printf(
                            wp_kses(
                                /* translators: %s: agenda page name. */
                                esc_html__('Visit your %s page to see your events', 'acs-agenda-manager'),
                                ['strong' => []]
                            ),
                            wp_kses($page_label, ['strong' => []])
                        );
                        ?>
                    </li>
                </ol>
            </section>

            <!-- Adding Events -->
            <section id="adding-events" class="acs-help-section">
                <h2><?php esc_html_e('Adding Events', 'acs-agenda-manager'); ?></h2>
                <p><?php esc_html_e('To add a new event, click the "Add New Event" button on the Agenda admin page. A dialog will appear with the following fields:', 'acs-agenda-manager'); ?></p>

                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Field', 'acs-agenda-manager'); ?></th>
                            <th><?php esc_html_e('Description', 'acs-agenda-manager'); ?></th>
                            <th><?php esc_html_e('Required', 'acs-agenda-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e('Category', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Group your events (e.g., Workshop, Conference, Course)', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('Yes', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Title', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('The name of your event', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('Yes', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Location', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Where the event takes place', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('No', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Image', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Upload or select an image from the Media Library', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('No', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Description', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Brief description shown in the "Read More" dialog', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('No', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Page Link', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Link to a detailed event page on your site', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('No', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Schedule', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Select one or more dates using the calendar picker', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('Yes', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Price', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Event cost (e.g., CHF 150, Free, On request)', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('No', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Advance Payment', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Whether advance payment is accepted for this event', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('No', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Partial Attendance', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Controls how multi-day events behave when dates pass', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('No', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('External URL', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Optional external link (e.g., registration on another site)', 'acs-agenda-manager'); ?></td>
                            <td><?php esc_html_e('No', 'acs-agenda-manager'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Managing Events -->
            <section id="managing-events" class="acs-help-section">
                <h2><?php esc_html_e('Managing Events', 'acs-agenda-manager'); ?></h2>

                <h3><?php esc_html_e('Editing Events', 'acs-agenda-manager'); ?></h3>
                <p><?php esc_html_e('To edit an event, click on any field in the events list. A dialog will open allowing you to modify the event details. Click Update to save your changes.', 'acs-agenda-manager'); ?></p>

                <h3><?php esc_html_e('Deleting Events', 'acs-agenda-manager'); ?></h3>
                <p><?php esc_html_e('To delete a single event, hover over the event row and click the Delete link. To delete multiple events, select them using the checkboxes and choose "Delete" from the Bulk Actions dropdown.', 'acs-agenda-manager'); ?></p>

                <h3><?php esc_html_e('Filtering Events', 'acs-agenda-manager'); ?></h3>
                <p><?php esc_html_e('Use the filter dropdown above the events list to show only events matching a specific title and category combination.', 'acs-agenda-manager'); ?></p>

                <h3><?php esc_html_e('Searching Events', 'acs-agenda-manager'); ?></h3>
                <p><?php esc_html_e('Use the search box to find events by title, category, location, or description.', 'acs-agenda-manager'); ?></p>
            </section>

            <!-- Shortcode -->
            <section id="shortcode" class="acs-help-section">
                <h2><?php esc_html_e('Using the Shortcode', 'acs-agenda-manager'); ?></h2>
                <p><?php esc_html_e('Display your agenda on any page or post using the shortcode:', 'acs-agenda-manager'); ?></p>

                <div class="acs-code-block">
                    <code>[agenda]</code>
                </div>

                <p><?php esc_html_e('The shortcode will display all upcoming events in a responsive card layout. Past events are automatically hidden based on their Partial Attendance setting.', 'acs-agenda-manager'); ?></p>

                <h3><?php esc_html_e('Default Agenda Page', 'acs-agenda-manager'); ?></h3>
                <?php
                /* translators: %s: agenda page name. */
                $page_label = sprintf('<strong>%s</strong>', esc_html($agenda_page));
                ?>
                <p>
                    <?php
                    /* translators: %s: agenda page name. */
                    printf(
                        wp_kses(
                            /* translators: %s: agenda page name. */
                            esc_html__('The plugin creates a page called "%s" upon activation. You can change this name in Settings.', 'acs-agenda-manager'),
                            ['strong' => []]
                        ),
                        wp_kses($page_label, ['strong' => []])
                    );
                    ?>
                </p>
            </section>

            <!-- Partial Attendance -->
            <section id="partial-attendance" class="acs-help-section">
                <h2><?php esc_html_e('Partial Attendance', 'acs-agenda-manager'); ?></h2>
                <p><?php esc_html_e('The Partial Attendance setting controls how events with multiple dates behave when some dates have passed:', 'acs-agenda-manager'); ?></p>

                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Option', 'acs-agenda-manager'); ?></th>
                            <th><?php esc_html_e('Behavior', 'acs-agenda-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e('No', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('The entire event disappears after the first date passes. Use this for single-session events where late registration is not possible.', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Yes', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('Past dates are hidden, but future dates remain visible. Use this for events where participants can join for remaining sessions.', 'acs-agenda-manager'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Keep until end', 'acs-agenda-manager'); ?></strong></td>
                            <td><?php esc_html_e('All dates remain visible until the last date passes. Use this for multi-day events where you want to show the complete schedule.', 'acs-agenda-manager'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Customization -->
            <section id="customization" class="acs-help-section">
                <h2><?php esc_html_e('Customization', 'acs-agenda-manager'); ?></h2>

                <h3><?php esc_html_e('Google Maps Location Autocomplete', 'acs-agenda-manager'); ?></h3>
                <p><?php esc_html_e('You can enable Google Places Autocomplete for the Location field to help users find addresses easily.', 'acs-agenda-manager'); ?></p>
                <ol>
                    <li><?php esc_html_e('Go to the Google Cloud Console and create a project', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Enable the "Maps JavaScript API" and "Places API"', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Create an API key and restrict it to your domain', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Go to Agenda > Settings and enter your API key', 'acs-agenda-manager'); ?></li>
                </ol>
                <p><?php esc_html_e('Once configured, the Location field will show address suggestions as you type.', 'acs-agenda-manager'); ?></p>

                <h3><?php esc_html_e('CSS Styling', 'acs-agenda-manager'); ?></h3>
                <p><?php esc_html_e('Override the default styles by adding custom CSS to your theme. The plugin uses CSS variables for easy customization:', 'acs-agenda-manager'); ?></p>

                <div class="acs-code-block">
                    <pre>/* Customize primary color */
:root {
    --acs-primary-color: #0073aa;
    --acs-secondary-color: #23282d;
    --acs-accent-color: #00a0d2;
}

/* Customize event cards */
.acsagenda {
    background-color: #f5f5f5;
    border-radius: 8px;
}

/* Customize date display */
.ACSdate {
    background-color: #your-color;
}</pre>
                </div>

                <h3><?php esc_html_e('Template Override', 'acs-agenda-manager'); ?></h3>
                <p><?php esc_html_e('For complete control over the agenda display, copy the template file from the plugin to your theme:', 'acs-agenda-manager'); ?></p>
                <ol>
                    <li><?php esc_html_e('Copy: wp-content/plugins/acs-agenda-manager/themefiles/page-agenda.php', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Paste to: wp-content/themes/your-theme/page-agenda.php', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Customize the template as needed', 'acs-agenda-manager'); ?></li>
                </ol>
            </section>

            <!-- Troubleshooting -->
            <section id="troubleshooting" class="acs-help-section">
                <h2><?php esc_html_e('Troubleshooting', 'acs-agenda-manager'); ?></h2>

                <h3><?php esc_html_e('Events not showing', 'acs-agenda-manager'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Check that the event has dates in the future', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Verify the shortcode [agenda] is on the page', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Check the Partial Attendance setting', 'acs-agenda-manager'); ?></li>
                </ul>

                <h3><?php esc_html_e('Calendar not working', 'acs-agenda-manager'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Ensure jQuery UI is not being blocked by another plugin', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Check the browser console for JavaScript errors', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Try deactivating other plugins to check for conflicts', 'acs-agenda-manager'); ?></li>
                </ul>

                <h3><?php esc_html_e('Styles not loading', 'acs-agenda-manager'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Clear your browser cache', 'acs-agenda-manager'); ?></li>
                    <li><?php esc_html_e('Check if your theme or a caching plugin is minifying CSS incorrectly', 'acs-agenda-manager'); ?></li>
                </ul>

                <h3><?php esc_html_e('Need more help?', 'acs-agenda-manager'); ?></h3>
                <p>
                    <?php
                    /* translators: %s: link to the GitHub issues page. */
                    printf(
                        wp_kses(
                            /* translators: %s: link to the GitHub issues page. */
                            esc_html__('Visit our %s for support and feature requests.', 'acs-agenda-manager'),
                            ['a' => ['href' => [], 'target' => []]]
                        ),
                        wp_kses(
                            '<a href="https://github.com/Esysc/wordpress_agenda/issues" target="_blank">' . esc_html__('GitHub Issues page', 'acs-agenda-manager') . '</a>',
                            ['a' => ['href' => [], 'target' => []]]
                        )
                    );
                    ?>
                </p>
            </section>

        </div>
    </div>
</div>
