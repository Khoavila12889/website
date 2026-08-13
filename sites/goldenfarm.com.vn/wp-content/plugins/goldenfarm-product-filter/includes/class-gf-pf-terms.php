<?php
/**
 * Term tree + URL building for the GoldenFarm Product Filter.
 *
 * Reads the native `product_cat` taxonomy and the custom brand taxonomy,
 * builds cached hierarchical trees per taxonomy, detects the current selection
 * from the native query vars and builds clean cacheable URLs
 * (?product_cat=<slugs>&product_brand=<slugs>) without the `yith_wcan` param.
 * No query modification is performed: WooCommerce handles the query.
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GF_PF_Terms
 */
class GF_PF_Terms {

	/**
	 * Taxonomy used for filtering (native product category).
	 *
	 * @return string
	 */
	public static function taxonomy() {
		return apply_filters( 'gf_pf_taxonomy', 'product_cat' );
	}

	/**
	 * Custom brand taxonomy slug.
	 *
	 * Theme/site can override via the `gf_pf_brand_taxonomy` filter.
	 *
	 * @return string
	 */
	public static function brand_taxonomy() {
		return apply_filters( 'gf_pf_brand_taxonomy', 'product_brand' );
	}

	/**
	 * Taxonomies rendered as independent filter blocks, in display order.
	 *
	 * Brand block first, then product categories.
	 *
	 * @return string[]
	 */
	public static function filter_taxonomies() {
		return apply_filters( 'gf_pf_filter_taxonomies', array( self::brand_taxonomy(), self::taxonomy() ) );
	}

	/**
	 * Root terms of the tree for a taxonomy (terms with no parent).
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return WP_Term[]
	 */
	public static function get_roots( $taxonomy = '' ) {
		$taxonomy = $taxonomy ? $taxonomy : self::taxonomy();
		$tree     = self::get_tree( $taxonomy );

		if ( isset( $tree['roots'] ) ) {
			return $tree['roots'];
		}

		return array();
	}

	/**
	 * Returns all terms of a taxonomy as a hierarchy.
	 *
	 * Structure: [ 'roots' => WP_Term[], 'children' => term_id => WP_Term[] ].
	 * Cached in the object cache (Redis) with a versioned key, invalidated when
	 * the taxonomy changes. Each taxonomy is cached under its own key
	 * (e.g. gf_pf_tree_product_cat, gf_pf_tree_product_brand).
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array
	 */
	public static function get_tree( $taxonomy = '' ) {
		$taxonomy  = $taxonomy ? $taxonomy : self::taxonomy();
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
	 * Children of a given term within a taxonomy.
	 *
	 * @param int    $parent_id Term id.
	 * @param string $taxonomy  Taxonomy name.
	 * @return WP_Term[]
	 */
	public static function get_children( $parent_id, $taxonomy = '' ) {
		$taxonomy = $taxonomy ? $taxonomy : self::taxonomy();
		$tree     = self::get_tree( $taxonomy );

		if ( isset( $tree['children'][ $parent_id ] ) ) {
			return $tree['children'][ $parent_id ];
		}

		return array();
	}

	/**
	 * Version used to invalidate the term cache when the taxonomy changes.
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
	 * Bumps the cache version so the tree is rebuilt.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	public static function invalidate_cache( $taxonomy = 'product_cat' ) {
		wp_cache_set( 'gf_pf_term_version_' . $taxonomy, time(), 'gf_pf', DAY_IN_SECONDS );
	}

	/**
	 * Slugs currently selected for a taxonomy, from the native query var.
	 *
	 * Includes the queried term when on a matching taxonomy archive page.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string[]
	 */
	public static function get_active_slugs( $taxonomy = '' ) {
		$taxonomy = $taxonomy ? $taxonomy : self::taxonomy();
		$slugs    = array();

		if ( is_tax( $taxonomy ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$slugs[] = $term->slug;
			}
		}

		$query_var = get_query_var( $taxonomy );
		if ( ! empty( $query_var ) ) {
			$slugs = array_merge( $slugs, self::split_slugs( $query_var ) );
		}

		if ( isset( $_GET[ $taxonomy ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$slugs = array_merge( $slugs, self::split_slugs( wp_unslash( $_GET[ $taxonomy ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Whether a term is part of the current selection for its taxonomy.
	 *
	 * @param WP_Term $term     Term to test.
	 * @param string  $taxonomy Taxonomy name.
	 * @return bool
	 */
	public static function is_term_active( $term, $taxonomy = '' ) {
		$taxonomy = $taxonomy ? $taxonomy : self::taxonomy();
		return in_array( $term->slug, self::get_active_slugs( $taxonomy ), true );
	}

	/**
	 * Base URL used to build filter links (current page path, no query string).
	 *
	 * @return string
	 */
	public static function get_base_url() {
		global $wp;

		return apply_filters( 'gf_pf_base_url', home_url( $wp->request ) );
	}

	/**
	 * Builds the filter URL for a term (toggling it in the current selection).
	 *
	 * Preserves active selections from OTHER taxonomies so that combining
	 * filters works (e.g. ?product_brand=golden-farm&product_cat=bo). Toggling
	 * an already-active term removes it while retaining selections from other
	 * taxonomies.
	 *
	 * Single-select default: checking a term replaces the current selection for
	 * THIS taxonomy, unchecking it clears this taxonomy's filter. When $multiple
	 * is true the term is appended to / removed from a comma-separated list
	 * (OR relation).
	 *
	 * @param WP_Term $term     Term to toggle.
	 * @param string  $taxonomy Taxonomy name.
	 * @param bool    $multiple Whether multiple terms can be selected.
	 * @return string
	 */
	public static function get_term_url( $term, $taxonomy = '', $multiple = false ) {
		$taxonomy = $taxonomy ? $taxonomy : self::taxonomy();

		// Single-select: redirect to term archive URL (clean SEO-friendly).
		if ( ! $multiple && ! self::is_term_active( $term, $taxonomy ) ) {
			return get_term_link( $term, $taxonomy );
		}

		$active = self::get_active_slugs( $taxonomy );

		if ( self::is_term_active( $term, $taxonomy ) ) {
			$selected = array_values( array_diff( $active, array( $term->slug ) ) );
		} elseif ( $multiple ) {
			$selected   = $active;
			$selected[] = $term->slug;
		} else {
			$selected = array( $term->slug );
		}

		// Preserve selections from every other filter taxonomy.
		$query_args = array();
		foreach ( self::filter_taxonomies() as $other ) {
			if ( $other === $taxonomy ) {
				continue;
			}
			$other_slugs = self::get_active_slugs( $other );
			if ( ! empty( $other_slugs ) ) {
				$query_args[ $other ] = implode( ',', $other_slugs );
			}
		}

		if ( ! empty( $selected ) ) {
			$query_args[ $taxonomy ] = implode( ',', $selected );
		}

		return add_query_arg( $query_args, self::get_base_url() );
	}

	/**
	 * Splits a raw query var value (array or comma-separated string) into slugs.
	 *
	 * @param string|array $raw Raw value.
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
