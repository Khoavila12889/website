<?php
/**
 * Term tree + URL building for the GoldenFarm Product Filter.
 *
 * Reads the native `product_cat` taxonomy, builds a cached hierarchical tree,
 * detects the current selection from the native `product_cat` query var and
 * builds clean cacheable URLs (?product_cat=<slugs>) without the `yith_wcan`
 * param. No query modification is performed: WooCommerce handles the query.
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GF_PF_Terms
 */
class GF_PF_Terms {

	/**
	 * Taxonomy used for filtering.
	 *
	 * @return string
	 */
	public static function taxonomy() {
		return apply_filters( 'gf_pf_taxonomy', 'product_cat' );
	}

	/**
	 * Root terms of the tree (terms with no parent).
	 *
	 * @return WP_Term[]
	 */
	public static function get_roots() {
		$tree = self::get_tree();

		if ( isset( $tree['roots'] ) ) {
			return $tree['roots'];
		}

		return array();
	}

	/**
	 * Returns all terms of the taxonomy as a hierarchy.
	 *
	 * Structure: [ 'roots' => WP_Term[], 'children' => term_id => WP_Term[] ].
	 * Cached in the object cache (Redis) with a versioned key, invalidated when
	 * the taxonomy changes.
	 *
	 * @return array
	 */
	public static function get_tree() {
		$taxonomy = self::taxonomy();
		$cache_key = 'gf_pf_tree_' . $taxonomy;
		$version   = self::cache_version( $taxonomy );

		$tree = wp_cache_get( $cache_key, 'gf_pf' );

		if ( false === $tree || empty( $tree['version'] ) || $tree['version'] !== $version ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'orderby'    => apply_filters( 'gf_pf_orderby', 'name' ),
					'order'      => apply_filters( 'gf_pf_order', 'ASC' ),
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
	 * @param int $parent_id Term id.
	 * @return WP_Term[]
	 */
	public static function get_children( $parent_id ) {
		$tree = self::get_tree();

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
	 * Slugs currently selected for the taxonomy, from the native query var.
	 *
	 * Includes the queried term when on a product_cat archive page.
	 *
	 * @return string[]
	 */
	public static function get_active_slugs() {
		$taxonomy = self::taxonomy();
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
	 * Whether a term is part of the current selection.
	 *
	 * @param WP_Term $term Term to test.
	 * @return bool
	 */
	public static function is_term_active( $term ) {
		return in_array( $term->slug, self::get_active_slugs(), true );
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
	 * Single-select default: checking a term replaces the current selection,
	 * unchecking it clears the filter. When $multiple is true the term is
	 * appended to / removed from a comma-separated list (OR relation).
	 *
	 * For single-select: redirects to term archive URL (e.g. /danh-muc-san-pham/socola-set-den)
	 * For multiple-select: appends to / removes from current selection.
	 *
	 * @param WP_Term $term     Term to toggle.
	 * @param bool    $multiple Whether multiple terms can be selected.
	 * @return string
	 */
	public static function get_term_url( $term, $multiple = false ) {
		$taxonomy = self::taxonomy();

		// Single-select: redirect to term archive URL (clean SEO-friendly)
		if ( ! $multiple && ! self::is_term_active( $term ) ) {
			return get_term_link( $term, $taxonomy );
		}

		$active = self::get_active_slugs();

		if ( self::is_term_active( $term ) ) {
			$selected = array_values( array_diff( $active, array( $term->slug ) ) );
		} elseif ( $multiple ) {
			$selected   = $active;
			$selected[] = $term->slug;
		} else {
			$selected = array( $term->slug );
		}

		$query_args = array();
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