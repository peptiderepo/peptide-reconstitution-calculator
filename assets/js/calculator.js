/**
 * Peptide Reconstitution Calculator — Client-side logic.
 *
 * What: Drives the interactive calculator widget. Populates the peptide
 *       dropdown from inline presets, auto-fills fields on selection,
 *       runs reconstitution math on every input change, and renders results.
 * Dependencies: prcConfig global (set by wp_localize_script in PRC_Shortcode).
 *
 * @see includes/frontend/class-prc-shortcode.php — HTML structure + config injection.
 * @see includes/class-prc-math.php              — Server-side mirror of the math.
 */

(function () {
	'use strict';

	/* ── Constants ────────────────────────────────────────────────────── */

	// Must match PRC_Math::SYRINGE_UNITS_PER_ML in class-prc-math.php
	// and prcConfig.syringeUnits in class-prc-shortcode.php.
	var SYRINGE_UNITS_PER_ML = 100;

	/* ── State ────────────────────────────────────────────────────────── */

	var presets = [];
	var currentPreset = null;

	/* ── DOM References ──────────────────────────────────────────────── */

	var els = {};

	/* ── Init ─────────────────────────────────────────────────────────── */

	function init() {
		var container = document.querySelector('.prc-calculator');
		if (!container) {
			return;
		}

		cacheDomRefs();
		loadPresets();
		populatePeptideDropdown();
		bindEvents();
		renderDisclaimer();

		// Auto-select peptide if data attribute is set.
		var preselected = container.getAttribute('data-selected-slug');
		if (preselected) {
			els.peptideSelect.value = preselected;
			onPeptideChange();
		}
	}

	function cacheDomRefs() {
		els.peptideSelect = document.getElementById('prc-peptide-select');
		els.vialSelect = document.getElementById('prc-vial-select');
		els.vialMg = document.getElementById('prc-vial-mg');
		els.waterMl = document.getElementById('prc-water-ml');
		els.desiredDose = document.getElementById('prc-desired-dose');
		els.doseUnit = document.getElementById('prc-dose-unit');
		els.doseRange = document.getElementById('prc-dose-range');
		els.frequencyRow = document.getElementById('prc-frequency-row');
		els.frequencyValue = document.getElementById('prc-frequency-value');
		els.results = document.getElementById('prc-results');
		els.concentration = document.getElementById('prc-result-concentration');
		els.mcgPerUnit = document.getElementById('prc-result-mcg-per-unit');
		els.syringeUnits = document.getElementById('prc-result-syringe-units');
		els.injectionMl = document.getElementById('prc-result-injection-ml');
		els.doses = document.getElementById('prc-result-doses');
		els.disclaimer = document.getElementById('prc-disclaimer');
	}

	function loadPresets() {
		if (typeof prcConfig !== 'undefined' && prcConfig.presets) {
			presets = prcConfig.presets;
		}
	}

	/* ── Dropdown Population ─────────────────────────────────────────── */

	function populatePeptideDropdown() {
		var select = els.peptideSelect;
		if (!select) {
			return;
		}

		// Clear existing options except the first placeholder.
		while (select.options.length > 1) {
			select.remove(1);
		}

		// Add preset options.
		presets.forEach(function (preset) {
			var opt = document.createElement('option');
			opt.value = preset.slug;
			opt.textContent = preset.name;
			if (preset.source === 'pr_core') {
				opt.textContent += ' ★';
			}
			select.appendChild(opt);
		});

		// Add custom entry option.
		var customOpt = document.createElement('option');
		customOpt.value = '__custom__';
		var i18n = (typeof prcConfig !== 'undefined' && prcConfig.i18n) ? prcConfig.i18n : {};
		customOpt.textContent = i18n.customEntry || 'Custom / Manual Entry';
		select.appendChild(customOpt);
	}

	/* ── Event Binding ───────────────────────────────────────────────── */

	function bindEvents() {
		if (els.peptideSelect) {
			els.peptideSelect.addEventListener('change', onPeptideChange);
		}
		if (els.vialSelect) {
			els.vialSelect.addEventListener('change', onVialSelectChange);
		}
		if (els.vialMg) {
			els.vialMg.addEventListener('input', calculate);
		}
		if (els.waterMl) {
			els.waterMl.addEventListener('input', calculate);
		}
		if (els.desiredDose) {
			els.desiredDose.addEventListener('input', calculate);
		}
		if (els.doseUnit) {
			els.doseUnit.addEventListener('change', calculate);
		}
	}

	/* ── Event Handlers ──────────────────────────────────────────────── */

	function onPeptideChange() {
		var slug = els.peptideSelect.value;

		if (slug === '__custom__' || slug === '') {
			currentPreset = null;
			clearPresetFields();
			hideResults();
			return;
		}

		currentPreset = presets.find(function (p) { return p.slug === slug; });
		if (!currentPreset) {
			clearPresetFields();
			return;
		}

		applyPreset(currentPreset);
		calculate();
	}

	function onVialSelectChange() {
		var val = els.vialSelect.value;
		if (val === '__custom__') {
			els.vialMg.value = '';
			els.vialMg.focus();
		} else {
			els.vialMg.value = val;
		}
		calculate();
	}

	/* ── Preset Application ──────────────────────────────────────────── */

	function applyPreset(preset) {
		// Populate vial size dropdown.
		populateVialDropdown(preset.vial_sizes_mg, preset.default_vial_mg);

		// Set vial mg input.
		els.vialMg.value = preset.default_vial_mg;

		// Set recommended water volume.
		els.waterMl.value = preset.recommended_water_ml;

		// Set dose unit.
		els.doseUnit.value = preset.dose_unit;

		// Set a sensible default dose (midpoint of range).
		if (preset.dose_range_min !== null && preset.dose_range_max !== null) {
			var mid = (preset.dose_range_min + preset.dose_range_max) / 2;
			// Round to nearest sensible number.
			if (preset.dose_unit === 'mcg') {
				els.desiredDose.value = Math.round(mid / 25) * 25;
			} else {
				els.desiredDose.value = Math.round(mid * 100) / 100;
			}
		}

		// Show dose range hint.
		if (preset.dose_range_min !== null && preset.dose_range_max !== null) {
			els.doseRange.textContent =
				'Typical range: ' + preset.dose_range_min + '–' + preset.dose_range_max + ' ' + preset.dose_unit;
		} else {
			els.doseRange.textContent = '';
		}

		// Show frequency if available.
		if (preset.typical_frequency) {
			els.frequencyRow.classList.remove('prc-field--hidden');
			els.frequencyValue.textContent = preset.typical_frequency;
		} else {
			els.frequencyRow.classList.add('prc-field--hidden');
		}
	}

	function populateVialDropdown(sizes, defaultSize) {
		var select = els.vialSelect;
		select.innerHTML = '';

		sizes.forEach(function (size) {
			var opt = document.createElement('option');
			opt.value = size;
			opt.textContent = size + ' mg';
			if (size === defaultSize) {
				opt.selected = true;
			}
			select.appendChild(opt);
		});

		// Add custom option.
		var customOpt = document.createElement('option');
		customOpt.value = '__custom__';
		customOpt.textContent = 'Custom…';
		select.appendChild(customOpt);
	}

	function clearPresetFields() {
		els.vialSelect.innerHTML = '';
		els.vialMg.value = '';
		els.waterMl.value = '';
		els.desiredDose.value = '';
		els.doseRange.textContent = '';
		els.frequencyRow.classList.add('prc-field--hidden');
	}

	/* ── Calculation ─────────────────────────────────────────────────── */

	function calculate() {
		var vialMg = parseFloat(els.vialMg.value);
		var waterMl = parseFloat(els.waterMl.value);
		var desiredDose = parseFloat(els.desiredDose.value);
		var doseUnit = els.doseUnit.value;

		// Need all three inputs to calculate.
		if (isNaN(vialMg) || isNaN(waterMl) || isNaN(desiredDose)) {
			hideResults();
			return;
		}

		if (vialMg <= 0 || waterMl <= 0 || desiredDose <= 0) {
			hideResults();
			return;
		}

		// Normalize dose to mg.
		var doseMg = (doseUnit === 'mcg') ? desiredDose / 1000 : desiredDose;

		// Core math — mirrors PRC_Math::calculate().
		var concentrationMgMl = vialMg / waterMl;
		var totalMcg = vialMg * 1000;
		var totalUnits = waterMl * SYRINGE_UNITS_PER_ML;
		var mcgPerUnit = totalMcg / totalUnits;
		var injectionMl = doseMg / concentrationMgMl;
		var syringeUnits = injectionMl * SYRINGE_UNITS_PER_ML;
		var dosesPerVial = Math.floor(vialMg / doseMg);

		// Render results.
		els.concentration.textContent = round(concentrationMgMl, 2) + ' mg/mL';
		els.mcgPerUnit.textContent = round(mcgPerUnit, 2) + ' mcg';
		els.syringeUnits.textContent = round(syringeUnits, 1) + ' IU';
		els.injectionMl.textContent = round(injectionMl, 3) + ' mL';
		els.doses.textContent = dosesPerVial;

		showResults();
	}

	/* ── UI Helpers ───────────────────────────────────────────────────── */

	function showResults() {
		els.results.classList.remove('prc-results--hidden');
	}

	function hideResults() {
		els.results.classList.add('prc-results--hidden');
	}

	function renderDisclaimer() {
		var i18n = (typeof prcConfig !== 'undefined' && prcConfig.i18n) ? prcConfig.i18n : {};
		var text = i18n.disclaimer ||
			'For informational purposes only. Not medical advice. Always consult a qualified healthcare professional.';
		if (els.disclaimer) {
			els.disclaimer.textContent = text;
		}
	}

	function round(value, decimals) {
		var factor = Math.pow(10, decimals);
		return Math.round(value * factor) / factor;
	}

	/* ── Boot ─────────────────────────────────────────────────────────── */

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
