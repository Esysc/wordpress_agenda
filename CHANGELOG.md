# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.3.2] - 2026-01-01

### Fixed

- **WordPress.org Compliance**: Updated all prefixes from 'acs' (3 chars) to 'acsagma' (7 chars) for uniqueness requirements
- Fixed AJAX action names to use proper `acsagma_` prefix instead of generic names
- Fixed menu slugs throughout the plugin after prefix rename (`agenda` → `acsagma-agenda`)
- Fixed JavaScript `filterEvents()` function to use correct menu slug
- Fixed E2E tests after identifier changes:
  - Calendar navigation tests now read dropdown values instead of concatenated text
  - Frontend test creates events with future dates for proper visibility
  - Search/filter tests use `waitForPageReload()` for better reliability
  - Updated all test fixtures with new menu slugs

### Changed

- All constants now use `ACSAGMA_` prefix (was `ACS_`)
- All classes now use `ACSAGMA_` prefix (was `ACS_`)
- All options now use `acsagma_` prefix (was `acs_` or mixed)
- All menu/submenu slugs now use `acsagma-` prefix
- All AJAX actions now use `acsagma_` prefix
- JavaScript localized objects now use `acsagma` prefix

## [3.3.1] - 2025-12-25

### Added

- Automated screenshot generation for WordPress.org plugin assets
- GitHub Actions workflow for automated WordPress.org deployment
- SELinux compatibility for Docker volume mounts (`:z` flag)

### Fixed

- MySQL 8.4 authentication compatibility with WP-CLI container
- Docker test environment setup script permissions

## [3.3.0] - 2025-12-25

### Added

- Japanese language support (ja)
- Success notifications for event create/update operations
- In-dialog validation error messages (no longer appear behind modal)
- Backend date validation to sanitize invalid date formats
- New translation strings for validation messages

### Changed

- Split CSS into 3 files for better organization:
  - `acs-common.css`: Shared variables, buttons, spinner
  - `acs.css`: Frontend-only styles
  - `acs-admin.css`: Admin-only styles
- Modernized admin form with card-based sections (Basic Info, Schedule, Media, Links & Pricing)
- Improved jQuery UI dialog button styling with calculated min-height
- Updated all translation files (.pot, .po, .mo) with new strings

### Fixed

- Date field now filters out invalid/garbage text when using calendar picker
- Dialog buttons no longer clipped at bottom
- Form validation errors now display inside the modal dialog

## [3.2.0] - 2025-12-24

### Added

- Image lightbox: Click on event images to view full-size in a popup overlay
- Close button for calendar date picker
- Calendar tests in E2E test suite (15 new tests)
- Modern frontend design with CSS Grid layout
- Enhanced image display with hover effects and shadows
- Accessibility improvements for images (keyboard navigation, focus states)

### Changed

- Redesigned frontend agenda page with modern color palette
- Improved date column with gradient background and glass-morphism badges
- Updated responsive design for better mobile experience
- Refactored calendar date picker to read field value dynamically
- Enhanced "Read more" button styling with outline design

### Fixed

- Calendar now properly grays out and disables past dates for new events
- Calendar respects manually cleared date field
- ESLint formatting issues in JavaScript files
- Build exclusions for Plugin Check compliance

## [3.1.0] - 2025-12-24

### Added

- E2E test suite using Playwright
  - Event management tests (create, edit, delete, bulk delete)
  - Frontend display tests
  - Search and filter tests
- Convenience script `test/run-e2e.sh` for running E2E tests

### Fixed

- "Headers already sent" error when deleting events from admin panel

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
- Complete internationalization with 6 locales:
  - French (fr_FR, fr_CH)
  - Italian (it_IT, it_CH)
  - German (de_DE, de_CH)
- New POT template file for translators

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
