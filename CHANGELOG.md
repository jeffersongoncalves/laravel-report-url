# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-10-18

First release.

### Added

- `ReportUrl::store()` / `ReportUrl::storeFile()`: keep a generated file on a private disk and get a temporary signed download link (`->url()`, `->expires_at`). Configurable lifetime (`ttl`, default 7 days), per-call override.
- Download route `report-url.download` (`signed` + configurable middleware): 410 once expired, optional owner check (`restrict_to_owner`), download counter, `last_downloaded_at` and the `ReportUrlDownloaded` event.
- `report-url:prune` (with `--dry-run`) deletes expired links and their files, scheduled daily on one server.
- laravel-saved-filter integration: with laravel-saved-filter 1.9+ installed, `HostsReportFiles` is bound so scheduled reports too large to attach are emailed as download links.

## [Unreleased]
