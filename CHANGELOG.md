# Changelog

All notable changes to this project will be documented in this file.

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