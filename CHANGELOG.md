# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2025-12-24

### Added in 3.0.0

- Complete plugin rewrite with modern PHP 7.4+ code
- New class-based architecture with proper separation of concerns
- Dedicated classes: ACS_Database, ACS_Event, ACS_Template, ACS_Admin, ACS_Options
- Separated JavaScript files for admin and frontend
- Template files for admin pages
- Comprehensive inline PHPDoc documentation
- Pre-commit hooks configuration for code quality
- Docker-based testing environment
- User guide integrated in admin panel
- WordPress.org compatible readme.txt

### Changed

- Improved security with better input sanitization and nonce verification
- Modernized admin UI with updated dialogs and form handling
- Optimized database queries for better performance
- Better internationalization support
- CSS refactored with CSS variables for easy customization

### Removed

- Legacy monolithic code structure
- Inline JavaScript in PHP files
- Deprecated WordPress function calls

## [2.1.1] - 2023-06-15

### Added in 2.1.1

- French (fr_FR, fr_CH) translations
- Italian translation support

### Fixed

- Various bug fixes for date handling
- Improved compatibility with PHP 8.0

## [2.0.0] - 2022-01-10

### Added in 2.0.0

- Initial public release
- Multi-date event support
- Media library integration
- Shortcode for agenda display
- Admin interface for event management

## [1.0.0] - 2021-06-01

### Added in 1.0.0

- Initial development version
