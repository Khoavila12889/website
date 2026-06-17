<?php

/**
 * WordPress Bootstrap Pagination
 *
 * <?php echo wp_bootstrap_pagination(array('custom_query' => $the_query)) ?>
 *
 * Thêm tham số sau vào WP_Query
 * $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
 * 'paged' => $paged
 */
function wp_bootstrap_pagination($args = array())
{

	$defaults = array(
		'range' => 4,
		'custom_query' => FALSE,
		'previous_string' => '<i class="fa-light fa-chevron-left"></i>',
		'next_string' => '<i class="fa-light fa-chevron-right"></i>',
		'before_output' => '<div class="post-nav"><ul class="pager pagination">',
		'after_output' => '</ul></div>'
	);

	$args = wp_parse_args(
		$args,
		apply_filters('wp_bootstrap_pagination_defaults', $defaults)
	);

	$args['range'] = (int) $args['range'] - 1;
	if (!$args['custom_query'])
		$args['custom_query'] = @$GLOBALS['wp_query'];
	$count = (int) $args['custom_query']->max_num_pages;
	$page = intval(get_query_var('paged'));
	$ceil = ceil($args['range'] / 2);

	if ($count <= 1) return FALSE;
	if (!$page) $page = 1;
	if ($count > $args['range']) {
		if ($page <= $args['range']) {
			$min = 1;
			$max = $args['range'] + 1;
		} elseif ($page >= ($count - $ceil)) {
			$min = $count - $args['range'];
			$max = $count;
		} elseif ($page >= $args['range'] && $page < ($count - $ceil)) {
			$min = $page - $ceil;
			$max = $page + $ceil;
		}
	} else {
		$min = 1;
		$max = $count;
	}
	$echo = '';
	$previous = intval($page) - 1;
	$previous = esc_attr(get_pagenum_link($previous));
	$firstpage = esc_attr(get_pagenum_link(1));
	if ($firstpage && (1 != $page)) $echo .= '<li class="previous"><a href="' . $firstpage . '"><i class="fa-light fa-chevrons-left"></i></a></li>';
	if ($previous && (1 != $page)) $echo .= '<li><a href="' . $previous . '" title="' . __('Trước', 'text-domain') . '">' . $args['previous_string'] . '</a></li>';
	if (!empty($min) && !empty($max)) {
		for ($i = $min; $i <= $max; $i++) {
			if ($page == $i) {
				$echo .= '<li class="active"><span class="active">' . str_pad((int)$i, 2, '0', STR_PAD_LEFT) . '</span></li>';
			} else {
				$echo .= sprintf('<li><a href="%s">%002d</a></li>', esc_attr(get_pagenum_link($i)), $i);
			}
		}
	}
	$next = intval($page) + 1;
	$next = esc_attr(get_pagenum_link($next));
	if ($next && ($count != $page)) $echo .= '<li><a href="' . $next . '" title="' . __('Kế tiếp', 'text-domain') . '">' . $args['next_string'] . '</a></li>';
	$lastpage = esc_attr(get_pagenum_link($count));
	if ($lastpage) {
		$echo .= '<li class="next"><a href="' . $lastpage . '"><i class="fa-light fa-chevrons-right"></i></a></li>';
	}
	if (isset($echo)) echo $args['before_output'] . $echo . $args['after_output'];
}

if (!function_exists('glw_custom_pagination')) {
	function glw_custom_pagination(WP_Query $wp_query = null, $echo = true)
	{
		if ($wp_query === null) {
			global $wp_query;
		}
		$pages = paginate_links(
			array(
				'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
				'format' => '?paged=%#%',
				'current' => max(1, get_query_var('paged')),
				'total' => $wp_query->max_num_pages,
				'type' => 'array',
				'show_all' => false,
				'end_size' => 2,
				'mid_size' => 1,
				'prev_next' => false,
				'prev_text' => '<i class="fal fa-chevron-left"></i>',
				'next_text' => '<i class="fal fa-chevron-right"></i>',
				'add_args' => false,
				'add_fragment' => ''
			)
		);
		if (is_array($pages)) {
			$pagination = '<div class="pager"><ul class="pagination">';
			foreach ($pages as $page) {
				$pagination .= '<li' . (strpos($page, 'current') !== false ? ' class="active" ' : '') . '>';

				// Find the page number from the link
				preg_match("/\b\d+\b/", strip_tags($page), $matches);
				$page_number = isset($matches[0]) ? $matches[0] : 1;

				// Adding leading zero to the page number if it is less than 10
				$page_number_with_zero = str_pad($page_number, 1, STR_PAD_LEFT);

				if (strpos($page, 'current') !== false) {
					$pagination .= '<a>' . $page_number_with_zero . '</a>';
				} else {
					// Replace the page number with the padded one
					$page = preg_replace("/\b\d+\b/", $page_number_with_zero, $page);
					$pagination .= str_replace('class="page-numbers"', '', $page);
				}

				$pagination .= '</li>';
			}
			$pagination .= '</ul></div>';
			if ($echo === true) {
				echo $pagination;
			} else {
				return $pagination;
			}
		}
		return null;
	}
}
