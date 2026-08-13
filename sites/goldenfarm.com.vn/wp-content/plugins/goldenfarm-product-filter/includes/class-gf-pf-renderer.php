<?php
/**
 * Renders the GoldenFarm product filter tree.
 *
 * Produces YITH-compatible markup (.yith-wcan-filters, .filter-items,
 * .filter-item checkbox level-N, .toggle-handle, .active, .opened) so the
 * theme overrides in CanhCamTheme/styles/main.min.css keep working unchanged.
 * Navigation is plain anchor links to ?product_cat=<slugs>&product_brand=<slugs>
 * (no JS required).
 *
 * Two independent filter blocks are rendered inside the main container:
 *   1. "THƯƠNG HIỆU"  — iterates the custom brand taxonomy terms.
 *   2. "SẢN PHẨM"     — iterates the native product_cat taxonomy terms.
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
				'title'    => __( 'Sản phẩm', 'goldenfarm-product-filter' ),
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
	 * Builds the full filter container HTML with two independent blocks.
	 *
	 * @param array $args Render args.
	 * @return string
	 */
	public static function get_filters_html( $args = array() ) {
		$multiple = 'yes' === ( isset( $args['multiple'] ) ? $args['multiple'] : 'no' );
		$multiple = apply_filters( 'gf_pf_multiple', $multiple );

		$brand_title = isset( $args['brand_title'] ) ? $args['brand_title'] : __( 'Thương hiệu', 'goldenfarm-product-filter' );
		$cat_title   = isset( $args['title'] ) ? $args['title'] : __( 'Sản phẩm', 'goldenfarm-product-filter' );

		$brand_taxonomy = GF_PF_Terms::brand_taxonomy();
		$cat_taxonomy   = GF_PF_Terms::taxonomy();

		$brand_roots = GF_PF_Terms::get_roots( $brand_taxonomy );
		$cat_roots   = GF_PF_Terms::get_roots( $cat_taxonomy );

		// Nothing to render at all.
		if ( empty( $brand_roots ) && empty( $cat_roots ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="yith-wcan-filters no-title" id="gf-pf-filters" data-preset-id="gf-pf" data-target="">
			<div class="filters-container">
				<form method="get" action="<?php echo esc_url( GF_PF_Terms::get_base_url() ); ?>">
					<?php
					// Block 1: Brands.
					if ( ! empty( $brand_roots ) ) {
						echo self::render_block( $brand_taxonomy, $brand_roots, $brand_title, $multiple, 0 );
					}

					// Block 2: Product categories.
					if ( ! empty( $cat_roots ) ) {
						echo self::render_block( $cat_taxonomy, $cat_roots, $cat_title, $multiple, 1 );
					}
					?>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders a single filter block (one taxonomy).
	 *
	 * @param string   $taxonomy Taxonomy name.
	 * @param WP_Term[] $roots    Root terms.
	 * @param string   $title    Block title.
	 * @param bool     $multiple Whether multiple selection is allowed.
	 * @param int      $index    Block index (for unique IDs).
	 * @return string
	 */
	protected static function render_block( $taxonomy, $roots, $title, $multiple, $index ) {
		ob_start();
		?>
		<div class="yith-wcan-filter filter-tax hierarchical checkbox-design" id="filter_gf_pf_<?php echo (int) $index; ?>" data-filter-type="tax" data-filter-id="<?php echo (int) $index; ?>" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" data-multiple="<?php echo $multiple ? 'yes' : 'no'; ?>" data-relation="or">
			<?php if ( $title ) : ?>
				<h4 class="filter-title"><?php echo esc_html( $title ); ?></h4>
			<?php endif; ?>

			<div class="filter-content">
				<ul class="filter-items level-0">
					<?php
					foreach ( $roots as $term ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo self::render_term( $term, 0, $multiple, $taxonomy );
					}
					?>
				</ul>
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
	 * @param string  $taxonomy Taxonomy name.
	 * @return string
	 */
	protected static function render_term( $term, $level, $multiple, $taxonomy ) {
		$children     = GF_PF_Terms::get_children( $term->term_id, $taxonomy );
		$has_children = ! empty( $children );
		$active       = GF_PF_Terms::is_term_active( $term, $taxonomy );
		$item_id      = 'gf-pf-' . $taxonomy . '-' . $term->term_id;
		$classes      = array( 'filter-item', 'checkbox', 'level-' . $level );

		if ( 0 === $level ) {
			$classes[] = 'is-root';
		}

		if ( $active ) {
			$classes[] = 'active';
		}

		// Expanded by default (matches YITH "hierarchical: expanded"); collapse is
		// a pure UI toggle handled by gf-pf.js via the .toggle-handle element.
		if ( $has_children ) {
			$classes[] = 'has-children';
			$classes[] = 'opened';
		}

		$html  = '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$html .= '<label for="' . esc_attr( $item_id ) . '">';
		$html .= '<input type="checkbox" id="' . esc_attr( $item_id ) . '" name="' . esc_attr( $taxonomy ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( $active, true, false ) . '>';
		$html .= '<a href="' . esc_url( GF_PF_Terms::get_term_url( $term, $taxonomy, $multiple ) ) . '" role="button" class="term-label" data-term-slug="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a>';
		$html .= '</label>';

		if ( $has_children ) {
			$html .= '<span class="toggle-handle" aria-hidden="true"></span>';
			$html .= '<ul>';
			foreach ( $children as $child ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$html .= self::render_term( $child, $level + 1, $multiple, $taxonomy );
			}
			$html .= '</ul>';
		}

		$html .= '</li>';

		return $html;
	}
}
