/**
 * GoldenFarm Product Filter — frontend behaviour.
 *
 * Default interaction is plain full-page navigation via the term anchor links
 * (?product_cat=<slugs>), so filtering works with JS
 * disabled. This script only (a) animates the hierarchical branch
 * expand/collapse via .toggle-handle and (b) turns a checkbox toggle into
 * navigation to the term URL.
 */
(function($) {
	'use strict';

	var $filters = $('#gf-pf-filters');

	if (!$filters.length) {
		return;
	}

	var DURATION = 220;

	// Expand / collapse a branch without navigating.
	$filters.on('click', '.filter-item.level-0 > .toggle-handle', function(e) {
		e.preventDefault();
		e.stopPropagation();

		var $item = $(this).closest('.filter-item');
		var $branch = $item.children('ul.filter-items').first();

		if (!$branch.length) {
			return;
		}

		if ($item.hasClass('opened')) {
			$branch.slideUp(DURATION, function() {
				$item.removeClass('opened');
			});
		} else {
			$item.addClass('opened');
			$branch.slideDown(DURATION);
		}
	});

	// Toggling a checkbox navigates to the term URL (full page load so the
	// LiteSpeed page cache can serve the filtered result).
	$filters.on('change', '.filter-item input[type="checkbox"]', function(e) {
		var $link = $(this).closest('.filter-item').find('> label > a.term-label');

		if ($link.length) {
			window.location.href = $link.attr('href');
		}
	});

	// Keyboard accessibility: Space/Enter on a focused term-link toggles selection.
	$filters.on('keydown', '.filter-item > label > a.term-label', function(e) {
		if (13 === e.keyCode || 32 === e.keyCode) {
			e.preventDefault();
			window.location.href = $(this).attr('href');
		}
	});
})(jQuery);