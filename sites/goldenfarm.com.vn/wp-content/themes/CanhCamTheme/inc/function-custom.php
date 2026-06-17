<?php
function log_dump($data)
{
	// Use the PHP ob_start function to capture the output of the var_dump function
	ob_start();
	var_dump($data);
	$dump = ob_get_clean();

	// Use the PHP highlight_string function to highlight the syntax
	$highlighted = highlight_string("<?php\n" . $dump . "\n?>", true);

	// Remove the PHP tags and wrap the highlighted code in a <pre> tag
	$formatted = '<pre>' . substr($highlighted, 27, -8) . '</pre>';

	// Add custom CSS styles for the .php and .hlt classes
	$custom_css = 'pre {position: static;
		background: #ffffff80;
		// max-height: 50vh;
		width: 100vw;
	}
	pre::-webkit-scrollbar{
	width: 1rem;}';

	// Wrap the custom CSS in a <style> tag
	$formatted_css = '<style>' . $custom_css . '</style>';
	echo ($formatted_css . $formatted);
}

function empty_content($str)
{
	return trim(str_replace('&nbsp;', '', strip_tags($str, '<img>'))) == '';
}

function custom_get_post_thumbnail($post_id, $size = 'full', $attr = '')
{
	if (is_array($post_id))
		$post_id = $post_id["ID"];
	$post_thumbnail_id = get_post_thumbnail_id($post_id);
	$image_attributes = wp_get_attachment_image_src($post_thumbnail_id, $size);
	$alt_text = get_post_meta($post_thumbnail_id, '_wp_attachment_image_alt', true);

	if ($image_attributes) {
		$html = "<img width='" . $image_attributes[1] . "' height='" . $image_attributes[2] . "' data-src='" . esc_url($image_attributes[0]) . "' class='lozad'";
		if (empty($alt_text)) {
			$html .= 'alt="' . get_wp_title_rss('') . '"';
		} else {
			$html .= ' alt="' . esc_attr($alt_text) . '"';
		}
		$html .= ' />';

		return $html;
	}
}

function custom_lozad_image($attachment_id, $size = 'full', $icon = false, $attr = '')
{
	if (is_array($attachment_id))
		$attachment_id = $attachment_id["ID"];
	$image_attributes = wp_get_attachment_image_src($attachment_id, $size);
	$alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
	if ($image_attributes) {
		$html = "<img width='" . $image_attributes[1] . "' height='" . $image_attributes[2] . "' data-src='" . esc_url($image_attributes[0]) . "' class='lozad'";
		if (empty($alt_text)) {
			$html .= 'alt="' . get_wp_title_rss('') . '"';
		} else {
			$html .= ' alt="' . esc_attr($alt_text) . '"';
		}
		$html .= ' />';
		return $html;
	}
}



// Determine the top-most parent of a term
function get_term_top_most_parent($term, $taxonomy)
{
	// Start from the current term
	$parent = get_term($term, $taxonomy);
	// Climb up the hierarchy until we reach a term with parent = '0'
	while ($parent->parent != '0') {
		$term_id = $parent->parent;
		$parent = get_term($term_id, $taxonomy);
	}
	return $parent;
}
function get_top_parents($taxonomy)
{
	// get terms for current post
	$terms = wp_get_object_terms(get_the_ID(), $taxonomy);
	$top_parent_terms = array();

	foreach ($terms as $term) {
		//get top level parent
		$top_parent = get_term_top_most_parent($term, $taxonomy);
		//check if you have it in your array to only add it once
		if (!in_array($top_parent, $top_parent_terms)) {
			$top_parent_terms[] = $top_parent;
		}
	}

	// build output (the HTML is up to you)
	$output = '<ul>';
	foreach ($top_parent_terms as $term) {
		//Add every term
		$output .= '<li><a href="' . get_term_link($term) . '">' . $term->name . '</a></li>';
	}
	$output .= '</ul>';

	return $output;
}


add_action('init', 'wpse17478_init');
function wpse17478_init()
{
	remove_filter('get_the_excerpt', 'wp_trim_excerpt');
}

// Custom query search
// function customize_search_query($search, $wp_query) {
//     global $wpdb;

//     if (empty($search) || empty($wp_query->query_vars['s'])) {
//         return $search;
//     }

//     if (empty($wp_query->query_vars['post_type']) || (!in_array('product', (array)$wp_query->query_vars['post_type']) && !in_array('product_variation', (array)$wp_query->query_vars['post_type']))) {
//         return $search;
//     }

//     // Filter out unwanted keywords or patterns like weights (e.g., 500g) and unnecessary punctuations
//     $original_query = $wp_query->query_vars['s'];
//     $filtered_query = preg_replace('/\b(\d+g|\d+kg|\d+lb|\d+oz)\b/', '', $original_query); // Remove weight units
//     $filtered_query = str_replace('-', '', $filtered_query); // Remove dashes if used like delimiters

//     $terms = explode(' ', trim($filtered_query));
//     $search = '';

//     foreach ($terms as $term) {
//         $term = esc_sql($wpdb->esc_like(trim($term)));
//         if (empty($term)) {
//             continue;
//         }
//         if (in_array('product', (array)$wp_query->query_vars['post_type'])) {
//             $search .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like($term) . '%');
//         } elseif (in_array('product_variation', (array)$wp_query->query_vars['post_type'])) {
//             $search .= $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)", '%' . $wpdb->esc_like($term) . '%', '%' . $wpdb->esc_like($term) . '%');
//         }
//     }

//     return $search;
// }

// add_filter('posts_search', 'customize_search_query', 10, 2);

// Handle search
function fn_normalize($s)
{ 
	// Replaces all diacritics/accents
	return transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
	//return $s;
}
//
function customize_search_query($search, $wp_query)
{

	global $wpdb;
	if (
		!is_main_query() || !is_search() || !isset($query->query_vars['s']) ||
		!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product'
	) {
		return $search;
	}

	// Modify the search terms to allow broader matches
	$search_terms = explode(' ', trim($wp_query->query_vars['s']));
	// $search = '';


	foreach ($search_terms as $term) {
		$term = esc_sql($wpdb->esc_like(trim($term)));
		$term_clean = fn_normalize($term);
		if (!empty($term)) {
			$search .= $wpdb->prepare(" OR {$wpdb->posts}.post_title LIKE %s", '%' . $term_clean . '%');
		}
	}

    $search = " AND ({$search})";
	return $search;
}

add_filter('posts_search', 'customize_search_query', 10, 2);

function filter_posts_by_relevance($posts, $query)
{
    if (
        !is_search() || empty($query->query_vars['s']) ||
        empty($query->query_vars['post_type']) ||
        (!in_array('product', (array)$query->query_vars['post_type']))
    ) {
        return $posts;
    }

    $original_query = strtolower($query->query_vars['s']);
    $query_words = explode(' ', $original_query);
    $threshold = 10;
    $posts_with_relevance = [];

    // Get products by tag
	$args = array(
		'taxonomy'   => 'product_tag',
		'hide_empty' => false,
		'name__like' => $original_query
	);

	$tags = get_terms($args);
	$arr_tags_name = array_map(function($tag) {
		return $tag->name;
	}, $tags);
    $tag_products = get_posts(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => array(array(
            'taxonomy' => 'product_tag',
            'field' => 'name',
            'terms' => $arr_tags_name,
            'operator' => 'IN',
        )),
    ));

    // // Merge tag products with original posts

    foreach ($posts as $post) {
        $title_words = explode(' ', fn_normalize(strtolower($post->post_title)));
        $matches = 0;

        foreach ($query_words as $qword) {
            if (in_array(fn_normalize($qword), $title_words)) {
                $matches++;
            }
        }

        $percent = ($matches / count($query_words)) * 100;

        if ($percent >= $threshold) {
            $posts_with_relevance[] = ['post' => $post, 'relevance' => $percent];
        }
    }

    usort($posts_with_relevance, function ($a, $b) {
        return $b['relevance'] - $a['relevance'];
    });

    $filtered_posts = array_map(function ($item) {
        return $item['post'];
    }, $posts_with_relevance);

    $all_posts = array_merge($filtered_posts, $tag_products);
    $all_posts = array_unique($all_posts, SORT_REGULAR);

    return $all_posts;
}

add_filter('the_posts', 'filter_posts_by_relevance', 10, 2);



function outputOptions($product_id)
{

	$product = new WC_Product_Variable($product_id);
	$variations = $product->get_available_variations(); // Get all variations
	$variation_attributes = []; // Initialize an array to store variation attributes

	foreach ($variations as $variation) {
		$variation_obj = new WC_Product_Variation($variation['variation_id']);
		$variation_details = [];

		foreach ($variation['attributes'] as $attr_key => $attr_value) {
			$term = get_term_by('slug', $attr_value, str_replace('attribute_', '', $attr_key));
			if ($term) {
				$variation_details[] = $term->name; // Add the attribute term name to the details list
			} else {
				$variation_details[] = $attr_value;
			}
		}

		$variation_attributes[] = implode(' | ', $variation_details);
	}
	echo implode(' | ', $variation_attributes);
}
