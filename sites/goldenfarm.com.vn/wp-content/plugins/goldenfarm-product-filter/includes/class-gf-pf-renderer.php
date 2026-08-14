<?php
/**
 * Renders a single hierarchical product_cat filter block (3 levels: Brand root -> Group -> Products) with YITH markup compatibility.
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GF_PF_Renderer
 */
class GF_PF_Renderer {

	/**
	 * Shortcode callback.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'       => '',
				'multiple'    => 'yes',
				'show_counts' => '',
			),
			$atts,
			'goldenfarm_product_filter'
		);

		return self::get_filters_html( $atts );
	}

	/**
	 * Echoes filter markup.
	 *
	 * @param array $args Options.
	 * @return void
	 */
	public static function render( $args = array() ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_filters_html( $args );
	}

	/**
	 * Builds wrapper containing ONE filter block (THƯƠNG HIỆU).
	 *
	 * @param array $args Render options.
	 * @return string
	 */
	public static function get_filters_html( $args = array() ) {
		$cat_tax = GF_PF_Terms::taxonomy();

		$cat_roots = GF_PF_Terms::get_roots( $cat_tax );

		if ( empty( $cat_roots ) ) {
			return '';
		}

		$multiple = 'no' !== ( isset( $args['multiple'] ) ? $args['multiple'] : 'yes' );
		$multiple = apply_filters( 'gf_pf_multiple', $multiple );

		$show_counts = '' !== ( isset( $args['show_counts'] ) ? $args['show_counts'] : '' )
			? 'no' !== $args['show_counts']
			: apply_filters( 'gf_pf_show_counts', true );

		$title = ! empty( $args['title'] )
			? $args['title']
			: __( 'Thương hiệu', 'goldenfarm-product-filter' );

		$cat_active = GF_PF_Terms::get_active_slugs( $cat_tax );
		$has_active = ! empty( $cat_active );

		$cat_counts = $show_counts ? GF_PF_Terms::get_term_counts( $cat_tax ) : array();

		ob_start();
		?>
		<div class="yith-wcan-filters no-title gf-pf-filters" id="gf-pf-filters">
			<div class="filters-container">

				<?php if ( $has_active ) : ?>
					<div class="gf-pf-toolbar">
						<span class="gf-pf-toolbar-status">
							<i class="fa-light fa-filter" aria-hidden="true"></i>
							<?php echo esc_html__( 'Bộ lọc đang áp dụng', 'goldenfarm-product-filter' ); ?>
						</span>
						<a class="gf-pf-reset" href="<?php echo esc_url( GF_PF_Terms::get_reset_url() ); ?>" role="button">
							<i class="fa-light fa-rotate-left" aria-hidden="true"></i>
							<?php echo esc_html__( 'Xóa bộ lọc', 'goldenfarm-product-filter' ); ?>
						</a>
					</div>
				<?php endif; ?>

				<form method="get" action="<?php echo esc_url( GF_PF_Terms::get_base_url() ); ?>">

					<?php
					// THƯƠNG HIỆU (PRODUCT_CAT 3-LEVEL TREE)
					?>
					<div class="yith-wcan-filter filter-tax hierarchical checkbox-design gf-pf-block" id="filter_gf_pf_cat" data-taxonomy="<?php echo esc_attr( $cat_tax ); ?>">
						<h4 class="filter-title">
							<span class="gf-pf-title-icon" aria-hidden="true"><i class="fa-light fa-tags"></i></span>
							<span class="gf-pf-title-text"><?php echo esc_html( $title ); ?></span>
						</h4>
						<div class="filter-content">
							<ul class="filter-items level-0">
								<?php
								foreach ( $cat_roots as $term ) {
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo self::render_term_recursive( $term, 0, $multiple, $cat_tax, $cat_counts, $show_counts );
								}
								?>
							</ul>
						</div>
					</div>

				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Recursively renders a term and its children.
	 *
	 * @param WP_Term $term Term object.
	 * @param int     $level Nesting level.
	 * @param bool    $multiple Multiple selection toggle.
	 * @param string  $taxonomy Taxonomy slug.
	 * @param array   $counts term_id => product count.
	 * @param bool    $show_counts Whether to render count badges.
	 * @return string
	 */
	protected static function render_term_recursive( $term, $level, $multiple, $taxonomy, $counts = array(), $show_counts = true ) {
		$children     = GF_PF_Terms::get_children( $term->term_id, $taxonomy );
		$has_children = ! empty( $children );
		$active       = GF_PF_Terms::is_term_active( $term, $taxonomy );
		$item_id      = 'gf-pf-' . $taxonomy . '-' . $term->term_id;
		$classes      = array( 'filter-item', 'checkbox', 'level-' . $level );

		if ( $active ) {
			$classes[] = 'active';
		}

		if ( 0 === $level ) {
			$classes[] = 'is-root';
			$classes[] = 'is-brand-root';
		} elseif ( 1 === $level ) {
			$classes[] = 'is-group-parent';
		} else {
			$classes[] = 'is-product-leaf';
		}

		if ( $has_children ) {
			$classes[] = 'has-children';
		}

		if ( $has_children && ( $active || GF_PF_Terms::has_active_descendant( $term, $taxonomy ) ) ) {
			$classes[] = 'opened';
		}

		$count_html = '';
		if ( $show_counts && isset( $counts[ $term->term_id ] ) && $counts[ $term->term_id ] > 0 ) {
			$count_html = '<span class="gf-pf-count">' . esc_html( number_format_i18n( $counts[ $term->term_id ] ) ) . '</span>';
		}

		$html  = '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$html .= '<label for="' . esc_attr( $item_id ) . '">';
		$html .= '<input type="checkbox" id="' . esc_attr( $item_id ) . '" name="' . esc_attr( $taxonomy ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( $active, true, false ) . '>';
		$html .= '<a href="' . esc_url( GF_PF_Terms::get_term_url( $term, $taxonomy, $multiple ) ) . '" role="button" class="term-label" data-term-slug="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a>';
		$html .= $count_html;
		$html .= '</label>';

		if ( $has_children ) {
			$html .= '<span class="toggle-handle" aria-hidden="true"></span>';
			$html .= '<ul class="filter-items level-' . ( $level + 1 ) . '">';
			foreach ( $children as $child ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$html .= self::render_term_recursive( $child, $level + 1, $multiple, $taxonomy, $counts, $show_counts );
			}
			$html .= '</ul>';
		}

		$html .= '</li>';

		return $html;
	}
}