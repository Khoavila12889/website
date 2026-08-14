<?php
/**
 * Smart Tree Renderer for Brand -> Product Categories
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

class GF_PF_Renderer {

	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => __( 'THƯƠNG HIỆU', 'goldenfarm-product-filter' ),
			),
			$atts,
			'goldenfarm_product_filter'
		);

		return self::get_filters_html( $atts );
	}

	public static function render( $args = array() ) {
		// phpcs:ignore
		echo self::get_filters_html( $args );
	}

	public static function get_filters_html( $args = array() ) {
		$tree = GF_PF_Terms::get_brand_tree();

		if ( empty( $tree ) ) {
			return '';
		}

		$title         = isset( $args['title'] ) ? $args['title'] : __( 'THƯƠNG HIỆU', 'goldenfarm-product-filter' );
		$brand_taxonomy = GF_PF_Terms::brand_taxonomy();
		$cat_taxonomy   = GF_PF_Terms::taxonomy();

		ob_start();
		?>
		<div class="yith-wcan-filters no-title gf-pf-filters" id="gf-pf-filters">
			<div class="filters-container">
				<form method="get" action="<?php echo esc_url( GF_PF_Terms::get_base_url() ); ?>">
					<div class="yith-wcan-filter filter-tax hierarchical checkbox-design gf-pf-block">
						<h4 class="filter-title">
							<span class="gf-pf-title-text"><?php echo esc_html( $title ); ?></span>
							<a href="<?php echo esc_url( GF_PF_Terms::get_reset_url() ); ?>" class="gf-pf-reset"><?php esc_html_e( 'Xóa bộ lọc', 'goldenfarm-product-filter' ); ?></a>
						</h4>
						<div class="filter-content">
							<ul class="filter-items level-0">
								<?php
								foreach ( $tree as $item ) {
									$brand        = $item['brand'];
									$brand_count  = isset( $item['count'] ) ? (int) $item['count'] : 0;
									$categories   = $item['categories'];
									$brand_active = GF_PF_Terms::is_term_active( $brand, $brand_taxonomy );

									$classes = array( 'filter-item', 'checkbox', 'level-0', 'is-brand-root' );
									if ( $brand_active ) {
										$classes[] = 'active';
									}
									if ( ! empty( $categories ) ) {
										$classes[] = 'has-children opened';
									}

									$brand_item_id = 'gf-pf-brand-' . $brand->term_id;
									?>
									<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
										<label for="<?php echo esc_attr( $brand_item_id ); ?>">
											<input type="checkbox" id="<?php echo esc_attr( $brand_item_id ); ?>" name="product_brand" value="<?php echo esc_attr( $brand->slug ); ?>" <?php checked( $brand_active ); ?>>
											<a href="<?php echo esc_url( GF_PF_Terms::get_term_url( $brand, $brand_taxonomy ) ); ?>" class="term-label"><?php echo esc_html( $brand->name ); ?></a>
											<?php if ( $brand_count > 0 ) : ?>
												<span class="gf-pf-count"><?php echo esc_html( number_format_i18n( $brand_count ) ); ?></span>
											<?php endif; ?>
										</label>
										<?php if ( ! empty( $categories ) ) : ?>
											<span class="toggle-handle"></span>
											<ul class="filter-items level-1">
												<?php echo self::render_cat_level( $categories, 1, $cat_taxonomy ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</ul>
										<?php endif; ?>
									</li>
								<?php } ?>
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
	 * Recursively renders one category level of a brand tree.
	 *
	 * @param array  $nodes    Category nodes (term/count/children).
	 * @param int    $level    Tree depth (1 = group parent, 2+ = product leaf).
	 * @param string $taxonomy Category taxonomy.
	 * @return string
	 */
	protected static function render_cat_level( $nodes, $level, $taxonomy ) {
		$html = '';

		foreach ( $nodes as $node ) {
			$term     = $node['term'];
			$count    = (int) $node['count'];
			$children = $node['children'];
			$active   = GF_PF_Terms::is_term_active( $term, $taxonomy );

			$classes = array(
				'filter-item',
				'checkbox',
				'level-' . $level,
				$level <= 1 ? 'is-group-parent' : 'is-product-leaf',
			);

			if ( $active ) {
				$classes[] = 'active';
			}
			if ( ! empty( $children ) ) {
				$classes[] = 'has-children';

				// Level-1 groups stay expanded; deeper branches open only when
				// active or when they lead to an active category.
				if ( $level === 1 || $active || self::cat_node_has_active( $children, $taxonomy ) ) {
					$classes[] = 'opened';
				}
			}

			$item_id = 'gf-pf-cat-' . $term->term_id;

			$html .= '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
			$html .= '<label for="' . esc_attr( $item_id ) . '">';
			$html .= '<input type="checkbox" id="' . esc_attr( $item_id ) . '" name="' . esc_attr( $taxonomy ) . '" value="' . esc_attr( $term->slug ) . '"' . checked( $active, true, false ) . '>';
			$html .= '<a href="' . esc_url( GF_PF_Terms::get_term_url( $term, $taxonomy ) ) . '" class="term-label">' . esc_html( $term->name ) . '</a>';
			if ( $count > 0 ) {
				$html .= '<span class="gf-pf-count">' . esc_html( number_format_i18n( $count ) ) . '</span>';
			}
			$html .= '</label>';

			if ( ! empty( $children ) ) {
				$html .= '<span class="toggle-handle"></span>';
				$html .= '<ul class="filter-items level-' . ( $level + 1 ) . '">';
				$html .= self::render_cat_level( $children, $level + 1, $taxonomy );
				$html .= '</ul>';
			}

			$html .= '</li>';
		}

		return $html;
	}

	/**
	 * Whether any node in the given subtree is currently active.
	 *
	 * @param array  $nodes    Category nodes.
	 * @param string $taxonomy Category taxonomy.
	 * @return bool
	 */
	protected static function cat_node_has_active( $nodes, $taxonomy ) {
		foreach ( $nodes as $node ) {
			if ( GF_PF_Terms::is_term_active( $node['term'], $taxonomy ) || self::cat_node_has_active( $node['children'], $taxonomy ) ) {
				return true;
			}
		}

		return false;
	}
}
