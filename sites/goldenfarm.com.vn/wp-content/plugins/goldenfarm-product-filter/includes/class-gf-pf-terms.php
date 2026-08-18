<?php
/**
 * Term tree + URL building for the GoldenFarm Product Filter.
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GF_PF_Terms
 */
class GF_PF_Terms {

	/**
	 * Primary taxonomy used for categories.
	 *
	 * @return string
	 */
	public static function taxonomy() {
		return apply_filters( 'gf_pf_taxonomy', 'product_cat' );
	}

	/**
	 * Taxonomy used for brands.
	 *
	 * @return string
	 */
	public static function brand_taxonomy() {
		return apply_filters( 'gf_pf_brand_taxonomy', 'product_brand' );
	}

	/**
	 * Slugs of all brand terms.
	 *
	 * Used to prune category terms that duplicate a brand (e.g. a
	 * product_cat named "Golden Farm").
	 *
	 * @return string[]
	 */
	public static function get_brand_slugs() {
		$brands = get_terms(
			array(
				'taxonomy'   => self::brand_taxonomy(),
				'hide_empty' => false,
				'fields'     => 'slugs',
			)
		);

		if ( is_wp_error( $brands ) || empty( $brands ) ) {
			return array();
		}

		return array_values( array_filter( (array) $brands ) );
	}

	/**
	 * Root terms of a given taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return WP_Term[]
	 */
	public static function get_roots( $taxonomy = 'product_cat' ) {
		$tree = self::get_tree( $taxonomy );

		if ( isset( $tree['roots'] ) ) {
			return $tree['roots'];
		}

		return array();
	}

	/**
	 * Returns all terms of a taxonomy as a cached hierarchy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array
	 */
	public static function get_tree( $taxonomy = 'product_cat' ) {
		$cache_key = 'gf_pf_tree_' . $taxonomy;
		$version   = self::cache_version( $taxonomy );

		$tree = wp_cache_get( $cache_key, 'gf_pf' );

		if ( false === $tree || empty( $tree['version'] ) || $tree['version'] !== $version ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'orderby'    => apply_filters( 'gf_pf_orderby', 'term_order', $taxonomy ),
					'order'      => apply_filters( 'gf_pf_order', 'ASC', $taxonomy ),
				)
			);

			$roots    = array();
			$children = array();

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( empty( $term->parent ) ) {
						$roots[] = $term;
					} else {
						$children[ $term->parent ][] = $term;
					}
				}
			}

			$tree = array(
				'version'  => $version,
				'roots'    => $roots,
				'children' => $children,
			);

			wp_cache_set( $cache_key, $tree, 'gf_pf', DAY_IN_SECONDS );
		}

		return $tree;
	}

	/**
	 * Children of a given term.
	 *
	 * @param int    $parent_id Term id.
	 * @param string $taxonomy Taxonomy slug.
	 * @return WP_Term[]
	 */
	public static function get_children( $parent_id, $taxonomy = 'product_cat' ) {
		$tree = self::get_tree( $taxonomy );

		if ( isset( $tree['children'][ $parent_id ] ) ) {
			return $tree['children'][ $parent_id ];
		}

		return array();
	}

	/**
	 * Brand -> categories tree.
	 *
	 * The tree is derived from the real product -> brand / product -> category
	 * relationships in the database (same logic as filter.py), not from a
	 * slug-matching heuristic:
	 *
	 * - A category is kept for a brand only when it contains published
	 *   products of that brand, or when it is an ancestor of such a category
	 *   (empty branches are pruned).
	 * - Each node carries a live 'count' = distinct published products of the
	 *   brand directly assigned to that category.
	 *
	 * @return array[] Array of items: array( 'brand' => WP_Term, 'count' => int, 'categories' => array ).
	 */
	public static function get_brand_tree() {
		$cache_key = 'gf_pf_brand_tree_v2';
		$version   = self::cache_version( self::brand_taxonomy() ) . '_' . self::cache_version( self::taxonomy() );

		$tree = wp_cache_get( $cache_key, 'gf_pf' );

		if ( false === $tree || empty( $tree['version'] ) || $tree['version'] !== $version ) {
			$tree = array(
				'version' => $version,
				'items'   => self::build_brand_tree(),
			);

			wp_cache_set( $cache_key, $tree, 'gf_pf', DAY_IN_SECONDS );
		}

		return $tree['items'];
	}

	/**
	 * Builds the brand -> categories tree straight from the database.
	 *
	 * @return array
	 */
	private static function build_brand_tree() {
		$brands = get_terms(
			array(
				'taxonomy'   => self::brand_taxonomy(),
				'hide_empty' => false,
				'orderby'    => apply_filters( 'gf_pf_orderby', 'term_order', self::brand_taxonomy() ),
				'order'      => apply_filters( 'gf_pf_order', 'ASC', self::brand_taxonomy() ),
			)
		);

		if ( is_wp_error( $brands ) || empty( $brands ) ) {
			return array();
		}

		$cats = get_terms(
			array(
				'taxonomy'   => self::taxonomy(),
				'hide_empty' => false,
				'orderby'    => apply_filters( 'gf_pf_orderby', 'term_order', self::taxonomy() ),
				'order'      => apply_filters( 'gf_pf_order', 'ASC', self::taxonomy() ),
			)
		);

		$cat_by_id    = array();
		$children_map = array();
		if ( ! is_wp_error( $cats ) ) {
			foreach ( $cats as $cat ) {
				$cat_by_id[ $cat->term_id ] = $cat;
				$children_map[ (int) $cat->parent ][ $cat->term_id ] = $cat->term_id;
			}
		}

		$counts       = self::query_brand_category_counts();
		$brand_counts = self::query_brand_counts();

		$items = array();
		foreach ( $brands as $brand ) {
			$active = isset( $counts[ $brand->term_id ] ) ? $counts[ $brand->term_id ] : array();

			$items[] = array(
				'brand'      => $brand,
				'count'      => isset( $brand_counts[ $brand->term_id ] ) ? $brand_counts[ $brand->term_id ] : 0,
				'categories' => self::build_cat_nodes( 0, $active, $cat_by_id, $children_map ),
			);
		}

		return $items;
	}

	/**
	 * Published product count per brand.
	 *
	 * @return array brand_id => int
	 */
	private static function query_brand_counts() {
		global $wpdb;

		$sql = "SELECT bt.term_id AS brand_id, COUNT(DISTINCT p.ID) AS cnt
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS br ON br.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} AS bt ON bt.term_taxonomy_id = br.term_taxonomy_id AND bt.taxonomy = %s
				WHERE p.post_type = 'product' AND p.post_status = 'publish'
				GROUP BY bt.term_id";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, self::brand_taxonomy() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ (int) $row->brand_id ] = (int) $row->cnt;
		}

		return $counts;
	}

	/**
	 * brand_id => array( cat_id => distinct published product count ).
	 *
	 * Single aggregate query over posts + term relationships so the whole
	 * brand/category grid is fetched in one round trip (no N+1 queries).
	 *
	 * @return array
	 */
	private static function query_brand_category_counts() {
		global $wpdb;

		$sql = "SELECT bt.term_id AS brand_id, ct.term_id AS cat_id, COUNT(DISTINCT p.ID) AS cnt
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS br ON br.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} AS bt ON bt.term_taxonomy_id = br.term_taxonomy_id AND bt.taxonomy = %s
				INNER JOIN {$wpdb->term_relationships} AS cr ON cr.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} AS ct ON ct.term_taxonomy_id = cr.term_taxonomy_id AND ct.taxonomy = %s
				WHERE p.post_type = 'product' AND p.post_status = 'publish'
				GROUP BY bt.term_id, ct.term_id";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, self::brand_taxonomy(), self::taxonomy() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ (int) $row->brand_id ][ (int) $row->cat_id ] = (int) $row->cnt;
		}

		return $counts;
	}

	/**
	 * Recursively prunes and builds one brand's category tree.
	 *
	 * A node is kept when it has products for the brand or when any descendant
	 * has products (ancestors on the path to a real category are preserved).
	 *
	 * NEW: Excludes unwanted wrapper categories (san-pham, chuyen-muc-trang-chu)
	 * and flattens the hierarchy by bypassing excluded parents and promoting
	 * their valid children directly to the parent level.
	 *
	 * @param int   $parent_id    Parent term id.
	 * @param array $active       Active category counts (cat_id => count).
	 * @param array $cat_by_id    term_id => WP_Term.
	 * @param array $children_map parent_id => term_id[].
	 * @return array
	 */
	private static function build_cat_nodes( $parent_id, $active, $cat_by_id, $children_map ) {
		$nodes = array();

		if ( empty( $children_map[ $parent_id ] ) ) {
			return $nodes;
		}

		// List of slugs to exclude from the tree structure (configurable in
		// WP Admin > Tùy chọn Filter, default set by GF_PF_Admin).
		$excluded_slugs = apply_filters(
			'gf_pf_excluded_category_slugs',
			GF_PF_Admin::get_excluded_slugs()
		);

		// Also hide category terms whose slug equals a brand slug (e.g. a
		// product_cat named "Golden Farm"), so the brand is not shown twice.
		// Computed once per request, even inside the recursion.
		static $brand_slugs = null;

		if ( null === $brand_slugs ) {
			$brand_slugs = self::get_brand_slugs();
		}

		$excluded_slugs = array_merge( $excluded_slugs, $brand_slugs );

		foreach ( $children_map[ $parent_id ] as $child_id ) {
			$term = $cat_by_id[ $child_id ];

			// Check if this category should be excluded (wrapper categories).
			$is_excluded = in_array( $term->slug, $excluded_slugs, true );

			// Recursively get children for this node.
			$child_nodes = self::build_cat_nodes( $child_id, $active, $cat_by_id, $children_map );

			// If the current category is excluded, flatten by promoting its children.
			if ( $is_excluded ) {
				// Don't add the excluded node itself, but merge its children into current level.
				$nodes = array_merge( $nodes, $child_nodes );
				continue;
			}

			// Remove nodes with 0 count and no valid descendants.
			if ( ! isset( $active[ $child_id ] ) && empty( $child_nodes ) ) {
				continue;
			}

			// Only include nodes that have a non-zero count OR have valid children.
			$count = isset( $active[ $child_id ] ) ? $active[ $child_id ] : 0;

			// Skip nodes with 0 count that only have children with 0 count.
			if ( $count === 0 && ! empty( $child_nodes ) ) {
				// Check if any child has a non-zero count recursively.
				if ( ! self::has_valid_descendants( $child_nodes ) ) {
					continue;
				}
			}

			$nodes[] = array(
				'term'     => $term,
				'count'    => $count,
				'children' => $child_nodes,
			);
		}

		return $nodes;
	}

	/**
	 * Check if a node tree has any valid descendants with count > 0.
	 *
	 * @param array $nodes Array of category nodes.
	 * @return bool
	 */
	private static function has_valid_descendants( $nodes ) {
		foreach ( $nodes as $node ) {
			if ( $node['count'] > 0 ) {
				return true;
			}
			if ( ! empty( $node['children'] ) && self::has_valid_descendants( $node['children'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Version used to invalidate term cache.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	protected static function cache_version( $taxonomy ) {
		$key   = 'gf_pf_term_version_' . $taxonomy;
		$value = wp_cache_get( $key, 'gf_pf' );

		if ( false === $value ) {
			$value = time();
			wp_cache_set( $key, $value, 'gf_pf', DAY_IN_SECONDS );
		}

		return (string) $value;
	}

	/**
	 * Bumps the cache version.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	public static function invalidate_cache( $taxonomy = 'product_cat' ) {
		wp_cache_set( 'gf_pf_term_version_' . $taxonomy, time(), 'gf_pf', DAY_IN_SECONDS );
	}

	/**
	 * Active slugs for a specific taxonomy from query vars.
	 *
	 * FIXED: Only reads from query parameters (?product_cat=slug), not from
	 * the current taxonomy archive page context. This prevents conflicts when
	 * users are on a category archive but want to filter by a different taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return string[]
	 */
	public static function get_active_slugs( $taxonomy = 'product_cat' ) {
		static $cache = array();

		if ( isset( $cache[ $taxonomy ] ) ) {
			return $cache[ $taxonomy ];
		}

		$slugs = array();

		// Read from query var (e.g., ?product_cat=slug1,slug2).
		$query_var = get_query_var( $taxonomy );
		if ( ! empty( $query_var ) ) {
			$slugs = array_merge( $slugs, self::split_slugs( $query_var ) );
		}

		// Read from $_GET as fallback (e.g., when query_var isn't set).
		if ( isset( $_GET[ $taxonomy ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$slugs = array_merge( $slugs, self::split_slugs( wp_unslash( $_GET[ $taxonomy ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		// Remove duplicates and reindex.
		$slugs = array_values( array_unique( $slugs ) );

		$cache[ $taxonomy ] = $slugs;

		return $slugs;
	}

	/**
	 * Whether a term is active.
	 *
	 * @param WP_Term $term Term.
	 * @param string  $taxonomy Taxonomy.
	 * @return bool
	 */
	public static function is_term_active( $term, $taxonomy = 'product_cat' ) {
		return in_array( $term->slug, self::get_active_slugs( $taxonomy ), true );
	}

	/**
	 * Base URL for filter links.
	 *
	 * FIXED: Always returns the WooCommerce shop page URL instead of the
	 * current page URL. This prevents conflicts when filtering from a
	 * category archive page (e.g., /mama-rosa/) to a different brand filter.
	 *
	 * @return string
	 */
	public static function get_base_url() {
		static $base_url = null;

		if ( null !== $base_url ) {
			return $base_url;
		}

		// Always use the shop page as the base for filter links.
		$shop_url = function_exists( 'wc_get_page_permalink' )
			? wc_get_page_permalink( 'shop' )
			: home_url( '/shop/' );

		$base_url = apply_filters( 'gf_pf_base_url', $shop_url );

		return $base_url;
	}

	/**
	 * Query args that should survive a filter toggle (sort + search).
	 *
	 * @return array
	 */
	protected static function get_preserved_args() {
		static $args = null;

		if ( null !== $args ) {
			return $args;
		}

		$args = array();

		foreach ( array( 'orderby', 's' ) as $key ) {
			if ( isset( $_GET[ $key ] ) && '' !== (string) $_GET[ $key ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$args[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
			}
		}

		$args = apply_filters( 'gf_pf_preserve_args', $args );

		return $args;
	}

	/**
	 * Precomputed URL-building context, computed once per request.
	 *
	 * Avoids re-parsing $_GET and re-resolving the shop URL for every term
	 * while rendering the filter tree (no N+1 work in the render loop).
	 *
	 * @return array{base_url:string,preserved:array,active:array,reset_url:string}
	 */
	public static function get_url_context() {
		static $context = null;

		if ( null !== $context ) {
			return $context;
		}

		$context = array(
			'base_url'  => self::get_base_url(),
			'preserved' => self::get_preserved_args(),
			'active'    => array(
				self::brand_taxonomy() => self::get_active_slugs( self::brand_taxonomy() ),
				self::taxonomy()       => self::get_active_slugs( self::taxonomy() ),
			),
			'reset_url' => self::get_reset_url(),
		);

		return $context;
	}

	/**
	 * Builds toggle URL for a term (preserves sort/search + current selection).
	 *
	 * FIXED: Uses shop page as base URL and builds clean query parameters.
	 * - Brand filters: [shop]/?product_brand=[slug]
	 * - Category filters: [shop]/?product_cat=[slug]&product_brand=[brand_slug]
	 *
	 * @param WP_Term $term Term to toggle.
	 * @param string  $taxonomy Target taxonomy.
	 * @param bool    $multiple Whether multiple terms allowed.
	 * @param array   $context  Optional precomputed URL context (see get_url_context()).
	 * @return string
	 */
	public static function get_term_url( $term, $taxonomy = 'product_cat', $multiple = true, $context = null ) {
		if ( null === $context ) {
			$context = self::get_url_context();
		}

		$active_current = isset( $context['active'][ $taxonomy ] )
			? $context['active'][ $taxonomy ]
			: self::get_active_slugs( $taxonomy );

		// Determine the selected slugs for this taxonomy after the toggle.
		if ( in_array( $term->slug, $active_current, true ) ) {
			// Term is active, so clicking it should remove it.
			$selected = array_values( array_diff( $active_current, array( $term->slug ) ) );
		} elseif ( $multiple ) {
			// Multiple selection allowed, add to existing selection.
			$selected   = $active_current;
			$selected[] = $term->slug;
		} else {
			// Single selection mode, replace with this term only.
			$selected = array( $term->slug );
		}

		// Start with preserved query args (orderby, search, etc.).
		$query_args = $context['preserved'];

		// Add the current taxonomy's selected slugs.
		if ( ! empty( $selected ) ) {
			$query_args[ $taxonomy ] = implode( ',', $selected );
		}

		// For category filters, preserve the active brand filter.
		// For brand filters, preserve the active category filter.
		$other_taxonomy = ( $taxonomy === self::brand_taxonomy() ) ? self::taxonomy() : self::brand_taxonomy();
		$other_active   = isset( $context['active'][ $other_taxonomy ] )
			? $context['active'][ $other_taxonomy ]
			: self::get_active_slugs( $other_taxonomy );

		if ( ! empty( $other_active ) ) {
			$query_args[ $other_taxonomy ] = implode( ',', $other_active );
		}

		// Build clean URL: [shop_url]/?taxonomy=slug1,slug2&other_taxonomy=slug
		return add_query_arg( $query_args, $context['base_url'] );
	}

	/**
	 * URL that clears all active filter selections (keeps sort/search).
	 *
	 * FIXED: Returns clean shop page URL with only preserved args (orderby, search).
	 * Removes all taxonomy query parameters (product_cat, product_brand).
	 *
	 * @return string
	 */
	public static function get_reset_url() {
		// Start with base shop URL (no query params).
		$base_url = self::get_base_url();

		// Get preserved args (orderby, search) but NOT taxonomy filters.
		$query_args = self::get_preserved_args();

		// Build URL with only preserved args, if any.
		if ( ! empty( $query_args ) ) {
			return add_query_arg( $query_args, $base_url );
		}

		// Return clean shop URL if no preserved args.
		return $base_url;
	}

	/**
	 * Whether any descendant of a term is currently active.
	 *
	 * @param WP_Term $term Term.
	 * @param string  $taxonomy Taxonomy.
	 * @return bool
	 */
	public static function has_active_descendant( $term, $taxonomy = 'product_cat' ) {
		$children = self::get_children( $term->term_id, $taxonomy );

		foreach ( $children as $child ) {
			if ( self::is_term_active( $child, $taxonomy ) || self::has_active_descendant( $child, $taxonomy ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Published product counts per term, constrained by the current active
	 * selection of the filter taxonomy. Cached in object cache.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return array term_id => int
	 */
	public static function get_term_counts( $taxonomy = 'product_cat' ) {
		$active = self::get_active_slugs( $taxonomy );

		$cache_key = 'gf_pf_counts_' . $taxonomy . '_' . md5( (string) wp_json_encode( $active ) );
		$counts    = wp_cache_get( $cache_key, 'gf_pf' );

		if ( is_array( $counts ) ) {
			return $counts;
		}

		global $wpdb;

		$where = array(
			"tt.taxonomy = %s",
			"p.post_type = 'product'",
			"p.post_status = 'publish'",
		);
		$args  = array( $taxonomy );

		if ( ! empty( $active ) ) {
			$ids = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'slug'       => $active,
					'fields'     => 'ids',
					'hide_empty' => false,
				)
			);

			if ( ! is_wp_error( $ids ) && ! empty( $ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$where[]      = "tr.object_id IN ( SELECT tr2.object_id FROM {$wpdb->term_relationships} AS tr2 INNER JOIN {$wpdb->term_taxonomy} AS tt2 ON tt2.term_taxonomy_id = tr2.term_taxonomy_id WHERE tt2.taxonomy = %s AND tt2.term_taxonomy_id IN ( {$placeholders} ) )";
				$args[]       = $taxonomy;
				$args         = array_merge( $args, $ids );
			}
		}

		$sql = "SELECT tt.term_id, COUNT(DISTINCT tr.object_id) AS cnt
				FROM {$wpdb->term_relationships} AS tr
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
				WHERE " . implode( ' AND ', $where ) . '
				GROUP BY tt.term_id';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );

		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ (int) $row->term_id ] = (int) $row->cnt;
		}

		wp_cache_set( $cache_key, $counts, 'gf_pf', HOUR_IN_SECONDS );

		return $counts;
	}

	/**
	 * Splits raw query string.
	 *
	 * @param string|array $raw Raw input.
	 * @return string[]
	 */
	protected static function split_slugs( $raw ) {
		$parts = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		$slugs = array();

		foreach ( $parts as $slug ) {
			$slug = sanitize_title( trim( (string) $slug ) );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}

		return $slugs;
	}
}