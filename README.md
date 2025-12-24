# ACS Agenda Manager

A WordPress plugin for managing and displaying event agendas. Perfect for workshops, courses, conferences, and event organizers.

![WordPress Version](https://img.shields.io/badge/WordPress-6.2%2B-blue)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green)
![Version](https://img.shields.io/badge/Version-3.0.0-orange)

## Description

ACS Agenda Manager provides an easy way to create and manage an event agenda on your WordPress site. Display upcoming events with dates, locations, images, and descriptions in a clean, responsive layout.

### Features

- **Multi-date support**: Events can span multiple days or have multiple occurrences
- **Media integration**: Upload and display event images via WordPress Media Library
- **Location display**: Show event locations prominently
- **Linked pages**: Connect events to detailed WordPress pages
- **Automatic expiration**: Past events are automatically hidden
- **Responsive design**: Looks great on all devices
- **Internationalization**: Supports French and English translations
- **Easy administration**: Intuitive admin interface with inline editing

## Requirements

- WordPress 6.2 or higher
- PHP 7.4 or higher

## Installation

### From GitHub

1. Download the latest release from the [releases page](https://github.com/Esysc/wordpress_agenda/releases)
2. Upload the `ACSagendaManager` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Agenda** in the admin menu to start adding events

### Manual Installation

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/Esysc/wordpress_agenda.git ACSagendaManager
```

Then activate the plugin in your WordPress admin panel.

## Usage

### Shortcode

Display the agenda on any page or post using the shortcode:

```
[agenda]
```

The plugin automatically creates an "Agenda" page upon activation with the shortcode included.

### Adding Events

1. Go to **Agenda** in the WordPress admin menu
2. Click **Add New Event**
3. Fill in the event details:
   - **Category**: Group your events (e.g., "Workshop", "Conference")
   - **Title**: Event name
   - **Location**: Where the event takes place
   - **Image**: Upload or select an event image
   - **Description**: Brief event description
   - **Page Link**: Link to a detailed event page
   - **Schedule**: Select event date(s) using the calendar
   - **Price**: Event cost (optional)
   - **Advance Payment**: Whether advance payment is accepted
   - **Partial Attendance**: How to handle multi-day events
4. Click **Add** to save

### Partial Attendance Options

- **No**: Event disappears after the first date passes
- **Yes**: Past dates are hidden, but future dates remain visible
- **Keep until end**: All dates remain visible until the last date passes

### Settings

Navigate to **Agenda → Settings** to:

- Change the default agenda page name
- View usage instructions

## File Structure

```
ACSagendaManager/
├── ACSagendaManager.php          # Main plugin file
├── class/
│   ├── class-acs-admin.php       # Admin functionality
│   ├── class-acs-database.php    # Database operations
│   ├── class-acs-event.php       # Event model
│   ├── class-acs-options.php     # Settings page
│   └── class-acs-template.php    # Template rendering
├── css/
│   ├── acs.css                   # Styles
│   └── images/                   # CSS images
├── js/
│   ├── acs-admin.js              # Admin JavaScript
│   └── acs-frontend.js           # Frontend JavaScript
├── lang/                         # Translations
├── templates/
│   ├── admin-page.php            # Admin page template
│   └── settings-page.php         # Settings page template
└── themefiles/
    └── page-agenda.php           # Agenda page template
```

## Customization

### CSS

Override default styles by adding custom CSS to your theme:

```css
/* Example: Change event card background */
.acsagenda {
    background-color: #f5f5f5;
}

/* Example: Customize date display */
.ACSdate {
    background-color: #your-color;
}
```

### Template Override

Copy `themefiles/page-agenda.php` to your theme directory to customize the agenda page template.

## Integration with ACScontactform

This plugin integrates with the [ACScontactform](https://github.com/Esysc) plugin to provide event registration forms. When ACScontactform is active, a shortcode button appears in the event list for easy form generation.

## Changelog

### 3.0.0 (2024)

- **Complete rewrite**: Modern PHP 7.4+ code with proper class structure
- **Separated concerns**: Database, templates, and admin logic in separate classes
- **Improved security**: Better input sanitization and nonce verification
- **Clean JavaScript**: Separated admin and frontend scripts
- **Modern admin UI**: Updated dialogs and form handling
- **Better translations**: Improved i18n support
- **Performance**: Optimized database queries

### 2.1.1

- Added French and Italian translations
- Various bug fixes

### 2.0.0

- Initial public release

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- **Issues**: [GitHub Issues](https://github.com/Esysc/wordpress_agenda/issues)
- **Author**: [Andrea Cristalli](https://www.linkedin.com/in/andrea-cristalli-72427213/)

## Donations

If you find this plugin useful, please consider making a donation to support development:

[![Donate](https://img.shields.io/badge/Donate-PayPal-blue.svg)](https://www.paypal.com/donate?business=KG9SDHM7VUP6Y&currency_code=CHF)

## License

This project is licensed under the GPL-2.0-or-later License - see the [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) file for details.

## Credits

- **jQuery UI Multi Dates Picker**: Loaded from [jsDelivr CDN](https://www.jsdelivr.com/package/npm/jquery-ui-multidatespicker)
- **Icons**: WordPress Dashicons
