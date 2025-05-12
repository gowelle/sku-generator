# Changelog

All notable changes to `sku-generator` will be documented in this file.

## [Unreleased]

- Initial release: unique SKU generation, model mappings, locking.

## [1.1.0] - 2025-05-12

### Added
- Added interactive model selection using Laravel Prompts
- Added progress reporting during SKU regeneration
- Added failure logging for failed SKU updates
- Added better error messages and validation

### Changed
- Improved documentation with better examples and organization
- Enhanced error handling in `sku:regenerate` command
- Updated command output to use Laravel Prompts
- Improved unique constraint handling

### Fixed
- Fixed issue with SKU regeneration failing silently
- Fixed potential memory issues with large datasets

## [1.0.5] - 2025-05-11

## [v1.0.0] - 2025-05-11

- First stable version.
