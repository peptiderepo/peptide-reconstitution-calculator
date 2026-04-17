# Peptide Reconstitution Calculator — Architecture

Peptide Reconstitution Calculator is a WordPress plugin that provides an interactive tool for calculating reconstitution parameters — concentration, injection volume, syringe units, and doses per vial. It integrates with Peptide Repo Core (optional) to provide peptide-specific presets sourced from real dosing data, and falls back to built-in defaults when PR Core is not installed.

---

## Data Flow

```
                      ┌───────────────────────┐
                      │  Peptide Repo Core    │  (optional dependency)
                      │  PR_Core_Peptide_Repo │
                      │  PR_Core_Dosing_Repo  │
                      └──────────┬────────────┘
                                 │ read peptides + dosing rows
                                 ▼
┌──────────────────┐  ┌───────────────────────┐  ┌──────────────────┐
│  Default Presets │─▶│  PRC_Preset_Provider  │─▶│  Transient Cache │
│  (hardcoded)     │  │  (merge + cache)      │  │  prc_presets_*   │
└──────────────────┘  └──────────┬────────────┘  └──────────────────┘
                                 │
                    ┌────────────┼────────────┐
                    ▼                         ▼
            ┌──────────────┐          ┌──────────────┐
            │ REST API     │          │ Shortcode    │
            │ prc/v1       │          │ [prc_calc]   │
            └──────────────┘          │ wp_localize  │
                                      └──────┬───────┘
                                             │ inline presets
                                             ▼
                                      ┌──────────────┐
                                      │ calculator.js│
                                      │ (client math)│
                                      └──────────────┘
```

---

## File Tree

```
peptide-reconstitution-calculator/
├── peptide-reconstitution-calculator.php   # Plugin bootstrap — constants, autoloader, hooks
├── uninstall.php                           # Teardown: delete prc_ options and transients
├── composer.json                           # Dev dependencies (PHPCS, WPCS)
├── phpcs.xml.dist                          # PHPCS ruleset configuration
├── ARCHITECTURE.md                         # This file
├── CONVENTIONS.md                          # Naming patterns, extension guides
├── CHANGELOG.md                            # Semantic versioning changelog
│
├── .github/
│   └── workflows/
│       └── ci.yml                          # PHP lint (8.1-8.3), PHPCS, 300-line check
│
├── assets/
│   ├── css/
│   │   └── calculator.css                  # Calculator widget styles (dark mode, responsive)
│   └── js/
│       └── calculator.js                   # Client-side calculator logic + preset handling
│
└── includes/
    ├── class-prc-autoloader.php            # SPL autoloader for PRC_ classes
    ├── class-prc-calculator.php            # Main orchestrator — boots subsystems
    ├── class-prc-activator.php             # Activation: rewrite flush
    ├── class-prc-deactivator.php           # Deactivation: rewrite flush
    ├── class-prc-preset-provider.php       # Bridges PR Core dosing data to presets
    ├── class-prc-default-presets.php       # Hardcoded fallback presets (8 peptides)
    ├── class-prc-math.php                  # Pure calculation functions (mirrored in JS)
    ├── class-prc-cache-listener.php        # Invalidates presets on PR Core data changes
    │
    ├── api/
    │   └── class-prc-rest-controller.php   # REST endpoints: presets list/detail, calculate
    │
    └── frontend/
        └── class-prc-shortcode.php         # [prc_calculator] shortcode + asset enqueue
```

---

## External Dependencies

### Peptide Repo Core (optional)

- **What for:** Peptide list and subcutaneous dosing rows for live presets.
- **Integration code:** `PRC_Preset_Provider::build_presets_from_pr_core()`.
- **Detection:** `defined( 'PR_CORE_VERSION' )` — graceful fallback if absent.
- **Cache invalidation:** `PRC_Cache_Listener` hooks `pr_core_after_dosing_row_publish` and `pr_core_after_peptide_publish`.

---

## Public API

### Shortcode

- `[prc_calculator]` — Renders the full calculator widget.
- `[prc_calculator peptide="bpc-157"]` — Pre-selects a specific peptide.

### REST API (namespace: `prc/v1`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /presets | Public | List all available presets |
| GET | /presets/{slug} | Public | Get a single preset by slug |
| POST | /calculate | Public | Server-side reconstitution math |

### Filters

- `prc_preset_provider_presets` — (planned) Filter the final presets array before caching.

---

## Key Decisions

### #1: Client-side calculation with server-side mirror

The JS calculator computes results instantly on every input change (no round-trips). The REST /calculate endpoint provides the same math server-side for validation, API consumers, and future integrations.

### #2: Graceful degradation without PR Core

The calculator works standalone with 8 hardcoded popular peptide presets. When PR Core is active, live data is merged in and takes priority. This means the tool is useful from day one even before PR Core is deployed.

### #3: Inline presets via wp_localize_script

Presets are injected into the page as a JS variable rather than fetched via REST on page load. This eliminates a round-trip and means the calculator is interactive immediately. The preset payload is small (< 5KB).

### #4: Transient-cached presets with event-driven invalidation

PR Core data is cached for 1 hour via WordPress transients. Cache is busted immediately when dosing rows or peptides are published, so the calculator stays in sync without polling.
