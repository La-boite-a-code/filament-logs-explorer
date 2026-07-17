# Changelog

All notable changes to `filament-logs-explorer` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial release.
- Filament page that lists log files grouped by logging channel
  (`single`, `daily`, `monolog` stream and the file based members of a `stack`).
- Slide-over viewer with in-file search (safe highlighting), match navigation,
  jump to start / end, previous / next file navigation, download and keyboard
  shortcuts.
- Fully configurable through a published config file and a fluent plugin API
  (navigation, channels, files per channel, reader limits, authorization).
- `canAccess()` authorization via a Gate ability or a custom closure.
- French, English and Spanish translations.
