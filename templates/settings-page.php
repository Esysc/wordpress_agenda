<?php
/**
 * Settings page template
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php esc_html_e('Agenda Settings', 'acs-agenda-manager'); ?></h1>

    <?php settings_errors('acsagma_agenda_settings'); ?>

    <div class="notice notice-info">
        <p>
            <strong><?php esc_html_e('Support this plugin!', 'acs-agenda-manager'); ?></strong>
            <?php esc_html_e('If you find this plugin useful, please consider making a donation to support development.', 'acs-agenda-manager'); ?>
        </p>
        <p>
            <a href="https://www.paypal.com/donate?business=KG9SDHM7VUP6Y&currency_code=CHF" target="_blank" class="button button-primary">
                <?php esc_html_e('Donate via PayPal', 'acs-agenda-manager'); ?>
            </a>
        </p>
    </div>

    <div class="card">
        <h2><?php esc_html_e('About This Plugin', 'acs-agenda-manager'); ?></h2>
        <p>
            <?php esc_html_e('This plugin creates a simple agenda page where you can display events. The default page is "Agenda" and is created upon activation.', 'acs-agenda-manager'); ?>
        </p>
        <p>
            <?php esc_html_e('You can change the page name below. The old page will be deleted and a new one created with the shortcode [agenda].', 'acs-agenda-manager'); ?>
        </p>
        <p>
            <?php esc_html_e('For consistent styling across themes, this plugin uses its own template. Tested with default themes and others.', 'acs-agenda-manager'); ?>
        </p>
        <p>
            <?php esc_html_e('Questions or issues?', 'acs-agenda-manager'); ?>
            <a href="https://github.com/Esysc/wordpress_agenda/issues" target="_blank"><?php esc_html_e('Report on GitHub', 'acs-agenda-manager'); ?></a>
            <?php esc_html_e('or contact', 'acs-agenda-manager'); ?>
            <a href="https://www.linkedin.com/in/andrea-cristalli-72427213/" target="_blank">Andrea Cristalli</a>
        </p>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('acsagma_agenda_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="acsagma_page"><?php esc_html_e('Agenda Page Name', 'acs-agenda-manager'); ?></label>
                </th>
                <td>
                    <input type="text"
                           name="acsagma_page"
                           id="acsagma_page"
                           value="<?php echo esc_attr($agenda_page); ?>"
                           class="regular-text"
                           required />
                    <p class="description">
                        <?php esc_html_e('The name of the page where the agenda will be displayed.', 'acs-agenda-manager'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="acsagma_google_maps_api_key"><?php esc_html_e('Google Maps API Key', 'acs-agenda-manager'); ?></label>
                </th>
                <td>
                    <input type="text"
                           name="acsagma_google_maps_api_key"
                           id="acsagma_google_maps_api_key"
                           value="<?php echo esc_attr($google_maps_api_key); ?>"
                           class="regular-text"
                           placeholder="AIza..." />
                    <p class="description">
                        <?php esc_html_e('Optional. Enter your Google Maps API key to enable location autocomplete.', 'acs-agenda-manager'); ?>
                        <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
                            <?php esc_html_e('Get an API key', 'acs-agenda-manager'); ?>
                        </a>
                    </p>
                    <p class="description">
                        <?php esc_html_e('Required APIs: Maps JavaScript API, Places API', 'acs-agenda-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'acs-agenda-manager')); ?>
    </form>

    <div class="card">
        <h2><?php esc_html_e('Usage', 'acs-agenda-manager'); ?></h2>
        <p><?php esc_html_e('Use the shortcode below to display the agenda on any page:', 'acs-agenda-manager'); ?></p>
        <code>[agenda]</code>
    </div>
</div>
