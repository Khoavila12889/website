/**
 * GoldenFarm Product Filter — frontend behaviour.
 *
 * Default interaction is plain full-page navigation via the term anchor links
 * (?product_cat=<slugs>), so filtering works with JS disabled. This script
 * only (a) expands/collapses hierarchy branches via .toggle-handle and
 * (b) turns a checkbox toggle into navigation to the term URL.
 */
(function($) {
	'use strict';

	var $filters = $('#gf-pf-filters');

	if (!$filters.length) {
		return;
	}

	// Expand / collapse a branch without navigating.
	$filters.on('click', '.filter-item.level-0 > .toggle-handle', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).closest('.filter-item').toggleClass('opened');
	});

	// Toggling a checkbox navigates to the term URL (full page load so the
	// LiteSpeed page cache can serve the filtered result).
	$filters.on('change', '.filter-item input[type="checkbox"]', function(e) {
		var $link = $(this).closest('.filter-item').find('> label > a.term-label');

		if ($link.length) {
			window.location.href = $link.attr('href');
		}
	});

	// Keyboard accessibility: Space on a focused link toggles selection.
	$filters.on('keydown', '.filter-item > label > a.term-label', function(e) {
		if (13 === e.keyCode || 32 === e.keyCode) {
			e.preventDefault();
			var $input = $(this).closest('.filter-item').find('> label > input[type="checkbox"]');
			if ($input.length) {
				$input.prop('checked', !$input.prop('checked'));
			}
			window.location.href = $(this).attr('href');
		}
	});
})(jQuery);