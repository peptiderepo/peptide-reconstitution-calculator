# Changelog

All notable changes to Peptide Reconstitution Calculator are documented here.
Format: [Semantic Versioning](https://semver.org/).

## [1.0.0] — 2026-04-17

### Added
- Interactive reconstitution calculator via `[prc_calculator]` shortcode.
- Client-side instant calculation: concentration (mg/mL), mcg per syringe unit, injection volume (mL), syringe units (IU), doses per vial.
- Peptide-specific presets from Peptide Repo Core (when active) with live dosing data.
- 8 built-in default presets: BPC-157, TB-500, Semaglutide, CJC-1295 (DAC), Ipamorelin, PT-141, MK-677, GHRP-6.
- Preset pre-selection via shortcode attribute: `[prc_calculator peptide="bpc-157"]`.
- REST API (`prc/v1`): GET /presets, GET /presets/{slug}, POST /calculate.
- Server-side math engine (`PRC_Math`) mirroring client-side calculations.
- Transient-cached presets with event-driven invalidation on PR Core data changes.
- Admin notice when PR Core is not installed (informational, not blocking).
- Dark mode support via `prefers-color-scheme`.
- Responsive design down to 320px viewport.
- Full teardown in `uninstall.php` (options + transients).
- CI workflow: PHP lint (8.1/8.2/8.3), PHPCS WordPress, 300-line file check.
