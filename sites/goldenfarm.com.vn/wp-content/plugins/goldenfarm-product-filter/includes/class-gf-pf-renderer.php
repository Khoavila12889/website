<?php
/**
 * Renders the GoldenFarm product filter tree.
 *
 * Produces YITH-compatible markup (.yith-wcan-filters, .filter-items,
 * .filter-item checkbox level-N, .toggle-handle, .active, .opened) so the
 * theme overrides in CanhCamTheme/styles/main.min.css keep working unchanged.
 * Navigation is plain anchor links to ?product_cat=<slugs> (no JS required).
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GF_PF_Renderer
 */
class GF_PF_Renderer {

	/**
	 * Shortcode renderer.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'    => __( 'Thương hiệu', 'goldenfarm-product-filter' ),
				'multiple' => 'no',
			),
			$atts,
			'goldenfarm_product_filter'
		);

		return self::get_filters_html( $atts );
	}

	/**
	 * Echoes the filter markup.
	 *
	 * @param array $args Optional render args (title, multiple).
	 * @return void
	 */
	public static function render( $args = array() ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_filters_html( $args );
	}

	/**
	 * Builds the full filter container HTML.
	 *
	 * @param array $args Render args.
	 * @return string
	 */
	public static function get_filters_html( $args = array() ) {
		$roots = GF_PF_Terms::get_roots();

		if ( empty( $roots ) ) {
			return '';
		}

		$title    = isset( $args['title'] ) ? $args['title'] : __( 'Thương hiệu', 'goldenfarm-product-filter' );
		$multiple = 'yes' === ( isset( $args['multiple'] ) ? $args['multiple'] : 'no' );
		$multiple = apply_filters( 'gf_pf_multiple', $multiple );

		ob_start();
		?>
		<div class="yith-wcan-filters no-title" id="gf-pf-filters" data-preset-id="gf-pf" data-target="">
			<div class="filters-container">
				<form method="get" action="<?php echo esc_url( GF_PF_Terms::get_base_url() ); ?>">
					<div class="yith-wcan-filter filter-tax hierarchical checkbox-design" id="filter_gf_pf_0" data-filter-type="tax" data-filter-id="0" data-taxonomy="<?php echo esc_attr( GF_PF_Terms::taxonomy() ); ?>" data-multiple="<?php echo $multiple ? 'yes' : 'no'; ?>" data-relation="or">
						<?php if ( $title ) : ?>
							<h4 class="filter-title"><?php echo esc_html( $title ); ?></h4>
						<?php endif; ?>

						<div class="filter-content">
							<ul class="filter-items level-0">
								<?php
								foreach ( $roots as $term ) {
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo self::render_term( $term, 0, $multiple );
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
	 * Renders a single term (and, recursively, its children).
	 *
	 * @param WP_Term $term     Term to render.
	 * @param int     $level    Nesting level (0 = root).
	 * @param bool    $multiple Whether multiple selection is allowed.
	 * @return string
	 */
	protected static function render_term( $term, $level, $multiple ) {
		$children   = GF_PF_Terms::get_children( $term->term_id );
		$has_children = ! empty( $children );
		$active     = GF_PF_Terms::is_term_active( $term );
		$item_id    = 'gf-pf-' . $term->term_id;
		$classes    = array( 'filter-item', 'checkbox', 'level-' . $level );

		if ( $active ) {
			$classes[] = 'active';
		}

		// Expanded by default (matches YITH "hierarchical: expanded"); collapse is
		// a pure UI toggle handled by gf-pf.js via the .toggle-handle element.
		if ( $has_children ) {
			$classes[] = 'opened';
		}

		$html  = '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$html .= '<label for="' . esc_attr( $item_id ) . '">';
		$html .= '<input type="checkbox" id="' . esc_attr( $item_id ) . '" name="' . esc_attr( GF_PF_Terms::taxonomy() ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( $active, true, false ) . '>';
		$html .= '<a href="' . esc_url( GF_PF_Terms::get_term_url( $term, $multiple ) ) . '" role="button" class="term-label" data-term-slug="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a>';
		$html .= '</label>';

		if ( $has_children ) {
			$html .= '<span class="toggle-handle" aria-hidden="true"></span>';
			$html .= '<ul>';
			foreach ( $children as $child ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$html .= self::render_term( $child, $level + 1, $multiple );
			}
			$html .= '</ul>';
		}

		$html .= '</li>';

		return $html;
	}
}