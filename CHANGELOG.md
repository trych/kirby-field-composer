# Changelog

All notable changes to this project will be documented in this file.

## [2.0.3] - 2026-02-02

### Fixed
- Fixed `format()` and `str()` methods to handle null values gracefully by normalizing them to empty strings

## [2.0.0] - 2025-01-05

### Added
- New `match()` method to compare field values against conditions with fallback options
- New `list()` method to format fields as lists with powerful processing options
- New `count()` method to count items in fields with the same options as `list()`
- New `encode` parameter for `tag()` method to support nesting of HTML tags

### Changed
- Added optional `$when` parameter to `format()`, `dump()` and `log()` methods for conditional execution

### Breaking Changes
- Reordered parameters in `tag()` method: The newly introduced `$encode` parameter now precedes the `$when` parameter to maintain consistency with other methods. If you were calling the `tag()` method with positional arguments for the `$when` parameter, you will need to update your code to either use named arguments or add an explicit extra `$encode` parameter before your `$when` parameter.

## [1.5.0] - 2024-12-23

### Changed
- Improved argument handling: `merge()`, `field()` and `f()` never treat single string arguments
  as separator

## [1.4.0] - 2024-11-10

### Added
- New `dump()` method for debugging field values and objects with customizable message templates
- New `log()` method for logging field values to files with timestamp and message support
- CHANGELOG.md file

## [1.3.0] - 2024-09-16

### Changed
- Improved argument handling: last string argument is now consistently treated as separator
- Enhanced nested array support in field composition

## [1.2.0] - 2024-09-05

### Changed
- Renamed parameters for better named argument support
- Simplified complex usage examples in documentation

### Removed
- Legacy separator option

## [1.1.0] - 2024-09-05

### Added
- Conditional parameter support for `prefix()`, `suffix()`, `wrap()` and `tag()` methods

### Changed
- Split `defaultSeparator` option into `mergeSeparator` and `affixSeparator`

### Fixed
- Field helper output with single string argument

## [1.0.0] - 2024-09-03

### Added
- Initial release
