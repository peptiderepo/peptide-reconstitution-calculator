# Peptide Reconstitution Calculator — Conventions

## Naming

- **Class prefix:** `PRC_` — all classes use this prefix.
- **Hook prefix:** `prc_` — all actions and filters start with this.
- **Option prefix:** `prc_` — all wp_options keys start with this.
- **Transient prefix:** `prc_` — all transients start with this.
- **CSS prefix:** `.prc-` — all CSS classes.
- **JS global:** `prcConfig` — inline configuration object.
- **Text domain:** `peptide-reconstitution-calculator`.
- **REST namespace:** `prc/v1`.

## File Naming

Autoloader convention: `PRC_Foo_Bar` maps to `class-prc-foo-bar.php`.
Files live in `includes/` or one level of subdirectory under `includes/`.

## How To: Add a New Preset

1. Add an entry to `PRC_Default_Presets::get_all()` in `includes/class-prc-default-presets.php`.
2. Each preset must include: slug, name, vial_sizes_mg, default_vial_mg, recommended_water_ml, dose_range_min, dose_range_max, dose_unit, typical_frequency, evidence_strength, dosing_row_count, source.

## How To: Modify Calculation Logic

1. Update the calculation in `PRC_Math` (`includes/class-prc-math.php`).
2. Mirror the same change in `assets/js/calculator.js` — the `calculate()` function.
3. Both must produce identical results for the same inputs.

## How To: Add a New Result Field

1. Add the calculation to `PRC_Math::calculate()`.
2. Add the JS mirror in `calculator.js` `calculate()` function.
3. Add a result card in `PRC_Shortcode::render_calculator_html()`.
4. Add CSS for the new card if needed.

## Error Handling

- Math functions return 0 for invalid inputs (division by zero, negative values).
- REST endpoints use WordPress validation callbacks on args.
- The JS calculator hides the results panel when inputs are incomplete.
- The preset provider returns built-in defaults on any PR Core failure.

## Cache Strategy

- Presets are cached in a WordPress transient (`prc_presets_all`) for 1 hour.
- Cache is invalidated by `PRC_Cache_Listener` on PR Core dosing/peptide publish events.
- Cache is also invalidated on plugin activation (flush on fresh start).
