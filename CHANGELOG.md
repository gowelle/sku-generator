# Changelog

All notable changes to `sku-generator` will be documented in this file.

## [Unreleased]

## [1.2.1] - 2025-12-XX

### Changed
- Enhanced documentation with instructions for customizing migrations when using non-integer primary keys (UUID, string, etc.)

## [1.2.0] - 2025-11-14

### Added
- **SKU History & Audit Trail**: Comprehensive audit trail system for tracking all SKU lifecycle events
- Added `SkuHistory` model with query scopes and relationships
- Added `SkuHistoryLogger` service for logging SKU changes
- Added events: `SkuCreated`, `SkuRegenerated`, `SkuModified`, `SkuDeleted`
- Added `sku:history` command for viewing SKU history with filtering options
- Added `sku:history:cleanup` command for removing old history records
- Added history configuration options (enabled, track_user, track_ip, track_user_agent, retention_days)
- Added `skuHistory()` relationship method to models using `HasSku` trait
- Added `getSkuHistory()` and `getLatestSkuHistory()` helper methods
- Added migration for `sku_histories` table
- Added comprehensive test suite for history functionality
- Added automatic event dispatching for all SKU operations
- Added optional reason parameter to `forceRegenerateSku()` method

### Changed
- Updated `HasSku` trait to automatically log all SKU changes
- Updated service provider to register new commands and migrations
- Enhanced documentation with history feature usage examples
- Updated ROADMAP to reflect completed v1.2.0 milestone

### Features
- Track SKU creation, regeneration, modification, and deletion
- Query history by model, SKU, event type, date range, or user
- Configurable retention policy for automatic cleanup
- Optional tracking of user, IP address, and user agent
- Event-driven architecture for extensibility
- Full backward compatibility (opt-in feature)

## [1.1.1] - 2025-05-13

### Fixed
- Fixed missing property values in variant SKUs
- Fixed order of property codes in variant SKU pattern
- Fixed configuration issue with property values accessor
- Fixed test assertions for variant SKU pattern matching
- Fixed variant SKU format with proper property code sorting

## [1.1.0] - 2025-05-12

### Added
- Added `--force` flag to `sku:regenerate` command
- Added interactive model selection using Laravel Prompts
- Added progress reporting during SKU regeneration
- Added failure logging for failed SKU updates
- Added better error messages and validation

### Changed
- Improved documentation with better examples
- Enhanced error handling in `sku:regenerate` command
- Updated command output to use Laravel Prompts
- Improved unique constraint handling

### Fixed
- Fixed issue with SKU regeneration failing silently
- Fixed potential memory issues with large datasets

## [1.0.5] - 2025-05-11
- Initial release with basic SKU generation

## [v1.0.0] - 2025-05-11
- First stable version
