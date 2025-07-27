# Changelog

---

## [0.5.5] - 2025-07-27

### Added
- Added server status indicators (e.g. online/offline) to the server list views.
- Displayed informative messages when a server is currently installing or reinstalling.

### Changed
- Improved validation, error handling, and instructional messages in both the Web Installer and CLI installer.
- Moved Categories and Products under a new collapsible Store section in the sidebar for improved navigation.

---

## [0.5.4] - 2025-07-13

### Added
- Added support for server subusers with permission management.
- Added server schedule management, including per-product/server schedule limits.
- Added CPU pinning support for both products and servers.
- Added missing translations across the panel.
- Added docker-compose.yml for both development and production environments to enable quick setup via a single command.

### Changed
- Improved the admin user management CRUD interface.
- Improved logging of certain user actions.
- Various minor performance and optimization improvements.

---

## [0.5.3] - 2025-06-02

### Added
- Added command to migrate servers from Pterodactyl to PteroCA.
- Added user account verification check before allowing voucher usage.
- Added server IP address and current expiration date to the server renewal view.
- Added my account tab with payment history and account settings.

### Changed
- Renamed the Servers menu item to My servers for improved clarity.
- Refactored some code to improve readability and maintainability.
- Improved styling of input placeholders.

### Fixed
- Fixed an issue where activating a voucher to top up balance did not work correctly.
- Fixed an error that occurred when deleting a server from the PteroCA panel.
- Fixed missing translations in the Server Logs section.

---

## [0.5.2] - 2025-05-12

### Added
- Added command to migrate servers from Pterodactyl to PteroCA.
- Added user account verification check before allowing voucher usage.
- Added server IP address and current expiration date to the server renewal view.
- Added my account tab with payment history and account settings.

### Changed
- Renamed the Servers menu item to My servers for improved clarity.
- Refactored some code to improve readability and maintainability.
- Improved styling of input placeholders.

### Fixed
- Fixed an issue where activating a voucher to top up balance did not work correctly.
- Fixed an error that occurred when deleting a server from the PteroCA panel.
- Fixed missing translations in the Server Logs section.

---

## [0.5.1] - 2025-05-07

### Fixed
- Fixed a bug on the balance top‑up page that threw an error whenever the entered amount exceeded the available balance.

---

## [0.5.0] - 2025-05-05

### Added
- Added server port (allocation) management interface.
- Added server backup management functionality.
- Added server database management functionality.
- Added support for assigning different prices to different billing periods.
- Added order finalization page with product configurator.
- Added voucher system (balance top-up and discount codes).
- Added the ability to edit the build configuration of existing servers.
- Added support for Dutch (`nl`) and Swiss German (`de_CH`) languages.  
  Thanks to @ninin06 and @brainshead from Crowdin.
- Added new filters to CRUD listings.
- Added deprecation notices limited to `dev` environment only.

### Changed
- Updated color scheme with minor visual improvements.
- Updated `README.md` with latest project information.

### Fixed
- Fixed a JavaScript error on the server variable edit page that occurred in certain language versions.
- Fixed an issue where incorrect allocation count was set during server creation.

---

## [0.4.4] - 2025-03-26

### Added
- Added TRUSTED_PROXIES environment variable to specify trusted proxies for Symfony.
- Added DISABLE_CSRF environment variable to allow disabling CSRF protection.

### Fixed
- Fixed an issue where the user with USER_ROLE could not access server console.

---

## [0.4.3] - 2025-03-24

### Added
- Introduced a new `app:update-system` command to automate project updates.
- Added configuration options to enable/disable dark mode and specify a default mode.

### Changed
- Refined default theme colors for dark mode.
- Minor CSS changes in the default theme for improved layout consistency.

### Fixed
- Resolved an issue preventing emails from being sent.
- Addressed a bug that caused Stripe payments to fail.

---

## [0.4.2] - 2025-03-16

### Added
- Introduced a web wizard for the initial setup. The CLI setup remains available as an alternative.
- Added a CLI notification when attempting to create a user with an email address that already exists in Pterodactyl.

### Changed
- The Pterodactyl Client API Key is no longer required to access server management pages.

### Fixed
- Resolved an issue with logging in the production environment.

---

## [0.4.1] - 2025-03-10

### Added
- Added titles to all pages.
- Implemented a loading spinner when sending requests on the server management page.
- Added a confirmation prompt in the user creation command for creating a user without a Pterodactyl Client API key.
- Added database indexes to improve performance.

### Changed
- Updated UI styling.
- Redesigned the product creation page.
- Modified theme loading behavior to fall back to the default view if a custom theme view is not found.

### Fixed
- Refactored and cleaned up the codebase.
- Fixed JavaScript console errors.
- Resolved responsive design issues on the servers list page.
- Fixed styling issues in the responsive header.
- Updated functions to remove deprecated dependencies.
- Resolved issues with server activity logs details layout.

---

## [0.4.0] - 2025-02-20

### Added
- Descriptions are now displayed for each setting when editing.
- Automatic server renewal has been introduced.
- Users can now upload an avatar in profile settings.
- A configuration option has been added to specify the number of days after which suspended (unpaid) servers are removed.
- SSO login support has been implemented for the Pterodactyl plugin.
- An option to enable or disable SSO login for the Pterodactyl panel has been introduced.
- Custom templates can now be created and uploaded.
- Several buttons in the default template now feature icons.
- A command for generating new templates has been added.
- Hindi has been added as a supported language.

### Changed
- The Settings tab has been redesigned and is now divided into five categories.
- Settings are now displayed in a structured order.
- JavaScript files controlling the panel’s behavior have been moved into dedicated JS files, making them dependent on the selected template.

### Fixed
- Missing translations have been added.

---

## [0.3.3] - 2025-02-10

### Changed
- Default color scheme is now set to light mode.

### Fixed
- Fixed cache issue in the app:configuration-system command.

---

## [0.3.2] - 2025-02-02

### Added
- Added a new command to change the user's password via CLI.
- Added a new language: Russian (thanks to @Futuraura for the translation).
- Added discord link in the admin overview page (as a support link).

### Changed
- Improved error handling during user registration.

### Fixed
- Cache issue with the current version of the application.

---

## [0.3.1] - 2025-01-28

### Added
- Added a role confirmation prompt to the user creation command.
- Added banner files to products.
- Added validation rules for updating server variables.

### Changed
- Improved error handling during user creation via CLI.
- Enhanced error handling when updating server variables.
- Updated translations for the "Add Balance" button.
- Revised the README.md file.

### Fixed
- Fixed an issue where missing images caused empty spaces to appear.
- Resolved a bug with email translations.
- Blocked access to the registration page for logged-in users.
- Corrected a layout issue by separating product images from banners.

---

## [0.3.0] - 2025-01-12

### Added
- Product egg configuration with startup variable permissions
- Server management page with server console, statistics, startup configuration, and activity logs
- Admin panel for server management
- Update check functionality on the admin overview page
- Data synchronization command
- Support for PteroCA plugin for Pterodactyl
- Admin management panel for server logs

### Changed
- Updated Composer dependencies
- The text allowing egg changes after product purchase on the product page now depends on product settings

### Fixed
- Caching issue with system settings
- Empty space on product page when no image is provided
- Grid layout issue on categories page

---

## [0.2.4] - 2024-11-25

### Changed
- Updated README.md with new demo credentials and additional information.
- Revised project documentation to include more comprehensive and useful details.
- Removed the "Edit Log" option from the log details view to simplify navigation and improve usability.
- Adjusted default database configurator variables (DB_HOST, DB_NAME, DB_USER) for a more streamlined setup experience.

---

## [0.2.3] - 2024-11-19

### Added
- Added Admin Dashboard with statistics & useful information.

### Fixed
- Fixed route name in url.

---

## [0.2.2] - 2024-09-09

### Added
- Added url to login page on the registration page.
- Added terms of service page.
- Added css files versioning based on the application version.

### Changed
- Refactored the enums.
- Split store template into multiple files.
- Moved css from the templates to the css file.
- Set default settings as a migration.

---

## [0.2.1] - 2024-09-08

### Added
- One main queue worker for all the queues.
- Deleting old expired servers from the database (after 3 months).
- Added created at and updated at timestamps to the user table.

### Changed
- Updated md files with proper email address and version.
- CRUDs are now sorted by the created date.
- Category is not necessary for the product creation.
- Changed placeholder images to local urls.
- Changed login and register document titles.
- Split large template views into components.
- Updated project information in the composer.json file.

### Fixed
- Fixed the issue with viewing price in the renewal product page.

---

## [0.2.0] - 2024-09-02

### Added
- Introduced unit and integration tests.
- Introduced PHPStan for static analysis.
- Introduced CI workflow for automated testing.
- Added CHANGELOG.md file.

### Changed
- Refactored and cleaned up a significant portion of the codebase.
- Updated the README.md file with more information.

### Fixed
- Bug fixes related to application logic.
- Dashboard template loading server data issue fix.

---

## [0.1.1] - 2024-08-28

### Fixed
- Minor css loading issue fix.

---

## [0.1.0] - 2024-08-27

### Added
- Initial release of the application with basic functionality.