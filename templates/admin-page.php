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

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only notice, no data processing.
    if (isset($_GET['deleted'])):
    ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Event(s) deleted successfully.', 'acs-agenda-manager'); ?></p>
        </div>
    <?php endif; ?>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only notice, no data processing.
    if (isset($_GET['created'])):
    ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Event created successfully.', 'acs-agenda-manager'); ?></p>
        </div>
    <?php endif; ?>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only notice, no data processing.
    if (isset($_GET['updated'])):
    ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Event updated successfully.', 'acs-agenda-manager'); ?></p>
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
        <?php wp_nonce_field('acsagma_agenda_admin_form', 'acsagma_agenda_admin_form_nonce'); ?>
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
    <div id="acs-dialog-notices"></div>
    <form id="acs-event-form" class="acs-modern-form">
        <input type="hidden" name="id" id="event-id" value="" />
        <input type="hidden" name="action" id="event-action" value="acsagma_add_item_agenda" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('acs_agenda_admin_nonce')); ?>" />

        <!-- Basic Info Section -->
        <div class="acs-form-section">
            <h3 class="acs-form-section-title">
                <span class="dashicons dashicons-info-outline"></span>
                <?php esc_html_e('Basic Information', 'acs-agenda-manager'); ?>
            </h3>

            <div class="acs-form-row">
                <div class="acs-form-field acs-form-field-half">
                    <label for="event-categorie" class="acs-form-label">
                        <?php esc_html_e('Category', 'acs-agenda-manager'); ?>
                        <span class="acs-required">*</span>
                    </label>
                    <input type="text" id="event-categorie" name="categorie" class="acs-form-input" required placeholder="<?php esc_attr_e('e.g., Workshop, Course, Seminar', 'acs-agenda-manager'); ?>" />
                </div>
                <div class="acs-form-field acs-form-field-half">
                    <label for="event-emplacement" class="acs-form-label">
                        <span class="dashicons dashicons-location"></span>
                        <?php esc_html_e('Location', 'acs-agenda-manager'); ?>
                    </label>
                    <input type="text" id="event-emplacement" name="emplacement" class="acs-form-input" placeholder="<?php esc_attr_e('e.g., Conference Room A', 'acs-agenda-manager'); ?>" />
                </div>
            </div>

            <div class="acs-form-field">
                <label for="event-title" class="acs-form-label">
                    <?php esc_html_e('Title', 'acs-agenda-manager'); ?>
                    <span class="acs-required">*</span>
                </label>
                <input type="text" id="event-title" name="title" class="acs-form-input" required placeholder="<?php esc_attr_e('Enter event title...', 'acs-agenda-manager'); ?>" />
            </div>

            <div class="acs-form-field">
                <label for="event-intro" class="acs-form-label">
                    <span class="dashicons dashicons-editor-paragraph"></span>
                    <?php esc_html_e('Description', 'acs-agenda-manager'); ?>
                </label>
                <textarea id="event-intro" name="intro" rows="3" class="acs-form-textarea" placeholder="<?php esc_attr_e('A brief description of the event...', 'acs-agenda-manager'); ?>"></textarea>
            </div>
        </div>

        <!-- Schedule Section -->
        <div class="acs-form-section">
            <h3 class="acs-form-section-title">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Schedule', 'acs-agenda-manager'); ?>
            </h3>

            <div class="acs-form-field">
                <label for="event-date" class="acs-form-label">
                    <?php esc_html_e('Event Dates', 'acs-agenda-manager'); ?>
                    <span class="acs-required">*</span>
                </label>
                <div class="acs-date-input-wrapper">
                    <div id="acs-datepicker-container"></div>
                    <input type="text" id="event-date" name="date" class="acs-form-input" required placeholder="<?php esc_attr_e('Click calendar to select dates', 'acs-agenda-manager'); ?>" />
                    <button type="button" class="button button-secondary acs-open-calendar">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php esc_html_e('Calendar', 'acs-agenda-manager'); ?>
                    </button>
                </div>
                <p class="acs-form-hint"><?php esc_html_e('Select one or more dates. Use the calendar for easy multi-date selection.', 'acs-agenda-manager'); ?></p>
            </div>

            <div class="acs-form-field">
                <label for="event-candopartial" class="acs-form-label">
                    <?php esc_html_e('Partial Attendance', 'acs-agenda-manager'); ?>
                </label>
                <select id="event-candopartial" name="candopartial" class="acs-form-select">
                    <option value="0"><?php esc_html_e('No - Hide after first date', 'acs-agenda-manager'); ?></option>
                    <option value="1"><?php esc_html_e('Yes - Hide past dates only', 'acs-agenda-manager'); ?></option>
                    <option value="2"><?php esc_html_e('Keep until end - Show all dates', 'acs-agenda-manager'); ?></option>
                </select>
            </div>
        </div>

        <!-- Media Section -->
        <div class="acs-form-section">
            <h3 class="acs-form-section-title">
                <span class="dashicons dashicons-format-image"></span>
                <?php esc_html_e('Media', 'acs-agenda-manager'); ?>
            </h3>

            <div class="acs-form-field">
                <label for="event-image" class="acs-form-label">
                    <?php esc_html_e('Event Image', 'acs-agenda-manager'); ?>
                </label>
                <div class="acs-image-upload-wrapper">
                    <div class="acs-image-preview" id="event-image-preview">
                        <span class="dashicons dashicons-format-image"></span>
                        <span class="acs-image-preview-text"><?php esc_html_e('No image selected', 'acs-agenda-manager'); ?></span>
                    </div>
                    <div class="acs-image-input-group">
                        <input type="text" id="event-image" name="image" class="acs-form-input" placeholder="<?php esc_attr_e('Image URL or select from library', 'acs-agenda-manager'); ?>" />
                        <button type="button" class="button button-secondary acs-upload-image">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Select', 'acs-agenda-manager'); ?>
                        </button>
                        <button type="button" class="button acs-remove-image" style="display:none;">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Links & Pricing Section -->
        <div class="acs-form-section">
            <h3 class="acs-form-section-title">
                <span class="dashicons dashicons-admin-links"></span>
                <?php esc_html_e('Links & Pricing', 'acs-agenda-manager'); ?>
            </h3>

            <div class="acs-form-row">
                <div class="acs-form-field acs-form-field-half">
                    <label for="event-link" class="acs-form-label">
                        <?php esc_html_e('Page Link', 'acs-agenda-manager'); ?>
                    </label>
                    <input type="url" id="event-link" name="link" class="acs-form-input" placeholder="<?php esc_attr_e('https://...', 'acs-agenda-manager'); ?>" />
                    <p class="acs-form-hint"><?php esc_html_e('Link to event details page', 'acs-agenda-manager'); ?></p>
                </div>
                <div class="acs-form-field acs-form-field-half">
                    <label for="event-redirect" class="acs-form-label">
                        <?php esc_html_e('External URL', 'acs-agenda-manager'); ?>
                    </label>
                    <input type="url" id="event-redirect" name="redirect" class="acs-form-input" placeholder="<?php esc_attr_e('https://...', 'acs-agenda-manager'); ?>" />
                    <p class="acs-form-hint"><?php esc_html_e('External registration link', 'acs-agenda-manager'); ?></p>
                </div>
            </div>

            <div class="acs-form-row">
                <div class="acs-form-field acs-form-field-half">
                    <label for="event-price" class="acs-form-label">
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php esc_html_e('Price', 'acs-agenda-manager'); ?>
                    </label>
                    <input type="text" id="event-price" name="price" class="acs-form-input" placeholder="<?php esc_attr_e('e.g., CHF 150.-', 'acs-agenda-manager'); ?>" />
                </div>
                <div class="acs-form-field acs-form-field-half">
                    <label for="event-account" class="acs-form-label">
                        <?php esc_html_e('Advance Payment', 'acs-agenda-manager'); ?>
                    </label>
                    <select id="event-account" name="account" class="acs-form-select">
                        <option value="0"><?php esc_html_e('No', 'acs-agenda-manager'); ?></option>
                        <option value="1"><?php esc_html_e('Yes - Required', 'acs-agenda-manager'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="spinner2" style="display: none;"></div>
