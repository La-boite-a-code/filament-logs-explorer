# Changelog

All notable changes to `filament-logs-explorer` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.3.0] - 2026-07-18

### Added

- **Delete a log file** from the file list or from the viewer slide-over. The
  trash button asks for confirmation before permanently removing the file from
  disk. The feature is enabled by default and has its own authorization,
  independent of read access — configurable through `deletion.enabled` /
  `deletion.gate` in the config, or the plugin's `->deletable()` and
  `->canDeleteUsing()` methods.

### Fixed

- The log viewer no longer makes the whole slide-over scroll: the code area is
  capped so the toolbar (search / navigation) and the footer stay visible, and
  only the log content scrolls.

## [0.2.0] - 2026-07-17

### Fixed

- A custom navigation icon set through `->navigationIcon()` on the plugin is now
  also used for the page's **active** navigation state, instead of always falling
  back to the configured `active_icon` default.
- The previous / next match buttons are now disabled while a search has no
  matches, so they no longer appear interactive when there is nothing to
  navigate.

### Changed

- The log viewer slide-over is now wider (`7xl` instead of `3xl`) for more
  comfortable reading of long log lines.

## [0.1.0] - 2026-07-17

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
