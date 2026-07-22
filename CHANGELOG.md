# Changelog

All notable changes to `filament-logs-explorer` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-22

First stable release, after a full review and security audit. The plugin's own
public surface (methods, config keys, translation keys) is unchanged from
`0.3.0` and is now covered by semantic versioning. The only compatibility break
is dropping Laravel 11, which is out of security support; see *Changed* below.

### Fixed

- Search highlighting no longer corrupts lines containing `&`, `<`, `>`, `"` or
  `'`. The line was escaped *before* the query was matched against it, so a
  query such as `39`, `quot` or `amp` could match inside an HTML entity and
  split it, rendering `Can&#39;t` instead of `Can't`. Matches are now located in
  the raw text and every segment is escaped on its own.
- Log lines containing invalid UTF-8 (a binary payload in an exception dump,
  legacy latin-1 input, a multibyte character cut by the truncation window) no
  longer render as a completely blank line. Blade escapes with
  `htmlspecialchars(ENT_QUOTES)`, which returns an empty string for an invalid
  sequence, so those bytes are now replaced with U+FFFD instead.
- Searching a large file no longer freezes the browser. Every matching line had
  its markup replaced, which does not scale to the tens of thousands of lines a
  5 MB file can produce. Marking is now bounded, applied on demand past the
  bound, and tracking the current match is constant time.
- The delete action can no longer be mounted without permission. It used to open
  its confirmation modal and then refuse silently on submit; it is now hidden
  outright, and a refused deletion reports why.

### Added

- `navigation.parent_item` in the published config file. The plugin already read
  the key, but it was missing from the stub, so it could only be set through the
  fluent `->navigationParentItem()`.
- Translation keys `delete.denied_title`, `delete.denied_body` and
  `delete.missing_body`, in English, French and Spanish.

### Changed

- **Laravel 11 is no longer supported.** It left security support in March 2026
  and carries an advisory with no fix on the `11.x` branch, so Composer refuses
  to install it under its default advisory policy. `illuminate/contracts` is now
  `^12.0|^13.0`. Laravel 11 users should upgrade the framework; nothing else in
  this release requires it.
- CI now pins Laravel explicitly and covers 12 and 13 against Filament 4 and 5
  on PHP 8.2 to 8.5, instead of only ever resolving the newest Laravel. A
  separate job runs PHPStan and Pint.
- The README has been rewritten: compatibility table, full configuration
  reference, documented keyboard behaviour, and the previously undocumented
  `cluster()` option.

## [0.3.0] - 2026-07-18

### Added

- **Delete a log file** from the file list or from the viewer slide-over. The
  trash button asks for confirmation before permanently removing the file from
  disk. The feature is enabled by default and has its own authorization,
  independent of read access, configurable through `deletion.enabled` /
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
