# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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