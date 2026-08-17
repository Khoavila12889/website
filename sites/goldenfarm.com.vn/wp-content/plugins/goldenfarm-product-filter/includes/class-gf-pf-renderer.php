<?php
/**
 * Recursive Brand ➔ Category Tree Renderer
 *
 * Renders the brand (product_brand) → product category (product_cat) hierarchy
 * as a native, SEO-friendly checkbox filter tree, matching STRUCTURE.md.
 * Levels are sorted A-Z (Vietnamese-aware).
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

class GF_PF_Renderer {

	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'    => __( 'THƯƠNG HIỆU', 'goldenfarm-product-filter' ),
				'multiple' => 'yes',
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

		// Sắp xếp các thương hiệu (Cấp 0) theo thứ tự A-Z
		self::sort_brand_items( $tree );

		$title         = isset( $args['title'] ) ? $args['title'] : __( 'THƯƠNG HIỆU', 'goldenfarm-product-filter' );
		$multiple      = 'no' !== ( isset( $args['multiple'] ) ? $args['multiple'] : 'yes' );
		$brand_taxonomy = GF_PF_Terms::brand_taxonomy();

		ob_start();
		?>
		<div class="gf-pf-wrap">
			<button type="button" class="gf-pf-mobile-toggle" aria-label="<?php esc_attr_e( 'Mở bộ lọc', 'goldenfarm-product-filter' ); ?>" aria-expanded="false">
				<i class="gf-pf-search-icon" aria-hidden="true"></i>
				<span><?php esc_html_e( 'Bộ lọc', 'goldenfarm-product-filter' ); ?></span>
			</button>
			<div class="yith-wcan-filters no-title gf-pf-filters" id="gf-pf-filters">
				<div class="gf-pf-mobile-header">
					<h3><?php esc_html_e( 'Bộ lọc', 'goldenfarm-product-filter' ); ?></h3>
					<button type="button" class="gf-pf-close-btn" aria-label="<?php esc_attr_e( 'Đóng bộ lọc', 'goldenfarm-product-filter' ); ?>">&times;</button>
				</div>
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
										// phpcs:ignore
										echo self::render_brand( $item, $multiple, $brand_taxonomy );
									}
									?>
								</ul>
							</div>
						</div>
					</form>
				</div>
				<div class="gf-pf-mobile-footer">
					<a href="<?php echo esc_url( GF_PF_Terms::get_reset_url() ); ?>" class="btn-reset"><?php esc_html_e( 'Xóa bộ lọc', 'goldenfarm-product-filter' ); ?></a>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render một thương hiệu (Cấp 0) cùng cây danh mục con.
	 *
	 * @param array  $item          Brand item: array( 'brand', 'count', 'categories' ).
	 * @param bool   $multiple      Cho phép chọn nhiều.
	 * @param string $brand_taxonomy Taxonomy thương hiệu.
	 * @return string
	 */
	protected static function render_brand( $item, $multiple, $brand_taxonomy ) {
		$brand       = $item['brand'];
		$brand_count = isset( $item['count'] ) ? (int) $item['count'] : 0;
		$categories  = isset( $item['categories'] ) ? $item['categories'] : array();
		$active      = GF_PF_Terms::is_term_active( $brand, $brand_taxonomy );

		// Sắp xếp danh mục con theo A-Z
		self::sort_cat_nodes( $categories );

		$item_id    = 'gf-pf-brand-' . $brand->term_id;
		$input_name = $multiple ? 'product_brand[]' : 'product_brand';

		$classes = array( 'filter-item', 'checkbox', 'level-0', 'is-brand-root', 'term-slug-' . $brand->slug );
		if ( $active ) {
			$classes[] = 'active';
		}
		if ( ! empty( $categories ) ) {
			$classes[] = 'has-children opened';
		}

		$html  = '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$html .= '<label for="' . esc_attr( $item_id ) . '">';
		$html .= '<input type="checkbox" id="' . esc_attr( $item_id ) . '" name="' . esc_attr( $input_name ) . '" value="' . esc_attr( $brand->slug ) . '" ' . checked( $active, true, false ) . '>';
		$html .= '<a href="' . esc_url( GF_PF_Terms::get_term_url( $brand, $brand_taxonomy, $multiple ) ) . '" class="term-label">' . esc_html( $brand->name ) . '</a>';
		if ( $brand_count > 0 ) {
			$html .= '<span class="gf-pf-count">' . esc_html( number_format_i18n( $brand_count ) ) . '</span>';
		}
		$html .= '</label>';

		if ( ! empty( $categories ) ) {
			$html .= '<span class="toggle-handle"></span>';
			$html .= '<ul class="filter-items level-1">';
			foreach ( $categories as $node ) {
				$html .= self::render_cat_node( $node, 1, $multiple );
			}
			$html .= '</ul>';
		}

		$html .= '</li>';

		return $html;
	}

	/**
	 * Render đệ quy một node danh mục (Cấp 1 trở lên).
	 *
	 * @param array $node     Node: array( 'term', 'count', 'children' ).
	 * @param int   $level    Độ sâu cây (1 = nhóm, 2+ = sản phẩm con).
	 * @param bool  $multiple Cho phép chọn nhiều.
	 * @return string
	 */
	protected static function render_cat_node( $node, $level, $multiple ) {
		$term     = $node['term'];
		$count    = isset( $node['count'] ) ? (int) $node['count'] : 0;
		$children = isset( $node['children'] ) ? $node['children'] : array();
		$active   = GF_PF_Terms::is_term_active( $term );

		// Sắp xếp danh mục con theo A-Z
		self::sort_cat_nodes( $children );

		$item_id    = 'gf-pf-cat-' . $term->term_id;
		$input_name = $multiple ? 'product_cat[]' : 'product_cat';

		$classes = array( 'filter-item', 'checkbox', 'level-' . $level );
		if ( 1 === $level ) {
			$classes[] = 'is-group-parent';
		} else {
			$classes[] = 'is-product-leaf';
		}
		if ( $active ) {
			$classes[] = 'active';
		}
		if ( ! empty( $children ) ) {
			$classes[] = 'has-children opened';
		}

		$html  = '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$html .= '<label for="' . esc_attr( $item_id ) . '">';
		$html .= '<input type="checkbox" id="' . esc_attr( $item_id ) . '" name="' . esc_attr( $input_name ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( $active, true, false ) . '>';
		$html .= '<a href="' . esc_url( GF_PF_Terms::get_term_url( $term, GF_PF_Terms::taxonomy(), $multiple ) ) . '" class="term-label">' . esc_html( $term->name ) . '</a>';
		if ( $count > 0 ) {
			$html .= '<span class="gf-pf-count">' . esc_html( number_format_i18n( $count ) ) . '</span>';
		}
		$html .= '</label>';

		if ( ! empty( $children ) ) {
			$html .= '<span class="toggle-handle"></span>';
			$html .= '<ul class="filter-items level-' . ( $level + 1 ) . '">';
			foreach ( $children as $child_node ) {
				$html .= self::render_cat_node( $child_node, $level + 1, $multiple );
			}
			$html .= '</ul>';
		}

		$html .= '</li>';

		return $html;
	}

	/**
	 * Sắp xếp danh sách brand items (cấp 0) theo A-Z tên thương hiệu.
	 *
	 * @param array $items Mảng item: array( 'brand' => WP_Term, ... ).
	 */
	protected static function sort_brand_items( &$items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return;
		}

		usort( $items, function ( $a, $b ) {
			return self::compare_names( $a['brand']->name, $b['brand']->name );
		} );
	}

	/**
	 * Sắp xếp mảng node danh mục theo A-Z tên term.
	 *
	 * @param array $nodes Mảng node: array( 'term' => WP_Term, ... ).
	 */
	protected static function sort_cat_nodes( &$nodes ) {
		if ( empty( $nodes ) || ! is_array( $nodes ) ) {
			return;
		}

		usort( $nodes, function ( $a, $b ) {
			return self::compare_names( $a['term']->name, $b['term']->name );
		} );
	}

	/**
	 * So sánh tên tiếng Việt (A-Z) có hỗ trợ dấu.
	 *
	 * @param string $a Tên thứ nhất.
	 * @param string $b Tên thứ hai.
	 * @return int
	 */
	protected static function compare_names( $a, $b ) {
		if ( class_exists( 'Collator' ) ) {
			try {
				$collator = new Collator( 'vi_VN' );
				if ( $collator ) {
					$cmp = $collator->compare( $a, $b );
					if ( false !== $cmp ) {
						return $cmp;
					}
				}
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Fall back to strnatcasecmp below.
			}
		}
		return strnatcasecmp( $a, $b );
	}
}