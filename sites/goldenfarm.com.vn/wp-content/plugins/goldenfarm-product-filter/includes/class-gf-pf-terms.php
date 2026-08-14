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
	 * @param string $taxonomy Taxonomy slug.
	 * @return string[]
	 */
	public static function get_active_slugs( $taxonomy = 'product_cat' ) {
		$slugs = array();

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
	 * @return string
	 */
	public static function get_base_url() {
		global $wp;

		return apply_filters( 'gf_pf_base_url', home_url( $wp->request ) );
	}

	/**
	 * Query args that should survive a filter toggle (sort + search).
	 *
	 * @return array
	 */
	protected static function get_preserved_args() {
		$args = array();

		foreach ( array( 'orderby', 's' ) as $key ) {
			if ( isset( $_GET[ $key ] ) && '' !== (string) $_GET[ $key ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$args[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
			}
		}

		return apply_filters( 'gf_pf_preserve_args', $args );
	}

	/**
	 * Builds toggle URL for a term (preserves sort/search + current selection).
	 *
	 * @param WP_Term $term Term to toggle.
	 * @param string  $taxonomy Target taxonomy.
	 * @param bool    $multiple Whether multiple terms allowed.
	 * @return string
	 */
	public static function get_term_url( $term, $taxonomy = 'product_cat', $multiple = true ) {
		$active_current = self::get_active_slugs( $taxonomy );

		if ( self::is_term_active( $term, $taxonomy ) ) {
			$selected = array_values( array_diff( $active_current, array( $term->slug ) ) );
		} elseif ( $multiple ) {
			$selected   = $active_current;
			$selected[] = $term->slug;
		} else {
			$selected = array( $term->slug );
		}

		$query_args = self::get_preserved_args();

		if ( ! empty( $selected ) ) {
			$query_args[ $taxonomy ] = implode( ',', $selected );
		}

		return add_query_arg( $query_args, self::get_base_url() );
	}

	/**
	 * URL that clears all active filter selections (keeps sort/search).
	 *
	 * @return string
	 */
	public static function get_reset_url() {
		$query_args = self::get_preserved_args();

		return add_query_arg( $query_args, remove_query_arg( self::taxonomy(), self::get_base_url() ) );
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