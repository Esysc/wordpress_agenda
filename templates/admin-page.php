<?php
/**
 * Admin page template
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Agenda Manager', 'acs-agenda-manager'); ?></h1>

    <a href="<?php echo esc_url($agenda_url); ?>" class="page-title-action" target="_blank">
        <?php esc_html_e('View Agenda', 'acs-agenda-manager'); ?>
    </a>

    <hr class="wp-header-end">

    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Event(s) deleted successfully.', 'acs-agenda-manager'); ?></p>
        </div>
    <?php endif; ?>

    <div id="acs-admin-notices"></div>

    <div class="tablenav top">
        <div class="alignleft actions">
            <button type="button" class="button button-primary" id="acs-add-event">
                <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                <?php esc_html_e('Add New Event', 'acs-agenda-manager'); ?>
            </button>
        </div>
        <div class="alignright">
            <button type="button" class="button" id="acs-show-help">
                <span class="dashicons dashicons-editor-help" style="vertical-align: middle;"></span>
                <?php esc_html_e('Help', 'acs-agenda-manager'); ?>
            </button>
        </div>
    </div>

    <form method="post" id="acs-events-form">
        <?php
        $this->list_table->prepare_items();
        $this->list_table->search_box(__('Search Events', 'acs-agenda-manager'), 'event-search');
        $this->list_table->display();
        ?>
    </form>
</div>

<!-- Help Dialog -->
<div id="acs-help-dialog" style="display: none;" title="<?php esc_attr_e('Help', 'acs-agenda-manager'); ?>">
    <table class="widefat">
        <thead>
            <tr>
                <th colspan="2"><h3><?php esc_html_e('Field Descriptions', 'acs-agenda-manager'); ?></h3></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th><strong><?php esc_html_e('Category', 'acs-agenda-manager'); ?></strong></th>
                <td><?php esc_html_e('How you want to categorize your event. Displayed in the widget title.', 'acs-agenda-manager'); ?></td>
            </tr>
            <tr>
                <th><strong><?php esc_html_e('Title', 'acs-agenda-manager'); ?></strong></th>
                <td><?php esc_html_e('The event title shown in the middle column.', 'acs-agenda-manager'); ?></td>
            </tr>
            <tr>
                <th><strong><?php esc_html_e('Location', 'acs-agenda-manager'); ?></strong></th>
                <td><?php esc_html_e('Where the event will be held. Shown in the left column.', 'acs-agenda-manager'); ?></td>
            </tr>
            <tr>
                <th><strong><?php esc_html_e('Image', 'acs-agenda-manager'); ?></strong></th>
                <td><?php esc_html_e('An image associated with the event. Shown in the right column.', 'acs-agenda-manager'); ?></td>
            </tr>
            <tr>
                <th><strong><?php esc_html_e('Description', 'acs-agenda-manager'); ?></strong></th>
                <td><?php esc_html_e('A short introduction to the event.', 'acs-agenda-manager'); ?></td>
            </tr>
            <tr>
                <th><strong><?php esc_html_e('Page Link', 'acs-agenda-manager'); ?></strong></th>
                <td><?php esc_html_e('Internal or external link to the full event page.', 'acs-agenda-manager'); ?></td>
            </tr>
            <tr>
                <th><strong><?php esc_html_e('Schedule', 'acs-agenda-manager'); ?></strong></th>
                <td><?php esc_html_e('Event dates. Can be a single day, a range, or multiple dates.', 'acs-agenda-manager'); ?></td>
            </tr>
            <tr>
                <th><strong><?php esc_html_e('Partial Attendance', 'acs-agenda-manager'); ?></strong></th>
                <td><?php esc_html_e('No: Event disappears after start. Yes: Past days are hidden. Keep until end: All days shown until expiration.', 'acs-agenda-manager'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Delete Confirmation Dialog -->
<div id="acs-delete-dialog" style="display: none;" title="<?php esc_attr_e('Confirm Delete', 'acs-agenda-manager'); ?>">
    <p>
        <span class="dashicons dashicons-warning" style="color: #d63638; font-size: 24px; float: left; margin-right: 10px;"></span>
        <?php esc_html_e('Are you sure you want to delete this event?', 'acs-agenda-manager'); ?>
    </p>
    <p><strong id="acs-delete-event-name"></strong></p>
</div>

<!-- Event Form Dialog -->
<div id="acs-event-dialog" style="display: none;" title="<?php esc_attr_e('Event', 'acs-agenda-manager'); ?>">
    <form id="acs-event-form">
        <input type="hidden" name="id" id="event-id" value="" />
        <input type="hidden" name="action" id="event-action" value="add_item_agenda" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('acs_agenda_admin_nonce')); ?>" />

        <table class="form-table">
            <tr>
                <th><label for="event-categorie"><?php esc_html_e('Category', 'acs-agenda-manager'); ?> *</label></th>
                <td><input type="text" id="event-categorie" name="categorie" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="event-title"><?php esc_html_e('Title', 'acs-agenda-manager'); ?> *</label></th>
                <td><input type="text" id="event-title" name="title" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="event-emplacement"><?php esc_html_e('Location', 'acs-agenda-manager'); ?></label></th>
                <td><input type="text" id="event-emplacement" name="emplacement" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event-image"><?php esc_html_e('Image', 'acs-agenda-manager'); ?></label></th>
                <td>
                    <input type="text" id="event-image" name="image" class="regular-text" />
                    <button type="button" class="button acs-upload-image"><?php esc_html_e('Select Image', 'acs-agenda-manager'); ?></button>
                </td>
            </tr>
            <tr>
                <th><label for="event-intro"><?php esc_html_e('Description', 'acs-agenda-manager'); ?></label></th>
                <td><textarea id="event-intro" name="intro" rows="4" class="large-text"></textarea></td>
            </tr>
            <tr>
                <th><label for="event-link"><?php esc_html_e('Page Link', 'acs-agenda-manager'); ?></label></th>
                <td><input type="url" id="event-link" name="link" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event-date"><?php esc_html_e('Schedule', 'acs-agenda-manager'); ?> *</label></th>
                <td>
                    <input type="text" id="event-date" name="date" class="regular-text" required readonly />
                    <button type="button" class="button acs-open-calendar"><?php esc_html_e('Open Calendar', 'acs-agenda-manager'); ?></button>
                    <div id="acs-datepicker-container"></div>
                </td>
            </tr>
            <tr>
                <th><label for="event-price"><?php esc_html_e('Price', 'acs-agenda-manager'); ?></label></th>
                <td><input type="text" id="event-price" name="price" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="event-account"><?php esc_html_e('Advance Payment', 'acs-agenda-manager'); ?></label></th>
                <td>
                    <select id="event-account" name="account">
                        <option value="0"><?php esc_html_e('No', 'acs-agenda-manager'); ?></option>
                        <option value="1"><?php esc_html_e('Yes', 'acs-agenda-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="event-candopartial"><?php esc_html_e('Partial Attendance', 'acs-agenda-manager'); ?></label></th>
                <td>
                    <select id="event-candopartial" name="candopartial">
                        <option value="0"><?php esc_html_e('No', 'acs-agenda-manager'); ?></option>
                        <option value="1"><?php esc_html_e('Yes', 'acs-agenda-manager'); ?></option>
                        <option value="2"><?php esc_html_e('Keep until end', 'acs-agenda-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="event-redirect"><?php esc_html_e('External URL', 'acs-agenda-manager'); ?></label></th>
                <td><input type="url" id="event-redirect" name="redirect" class="regular-text" /></td>
            </tr>
        </table>
    </form>
</div>

<div class="spinner2" style="display: none;"></div>
