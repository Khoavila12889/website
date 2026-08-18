<?php
/**
 * Admin settings page for the GoldenFarm Product Filter.
 *
 * Registers a native WordPress Settings API menu so site admins can
 * configure which category slugs are hidden from the filter tree.
 *
 * @package GF_PF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class GF_PF_Admin
 */
class GF_PF_Admin {

	const MENU_SLUG = 'gf-pf-settings';

	const OPTION_EXCLUDED_SLUGS = 'gf_pf_excluded_category_slugs';

	const DEFAULT_EXCLUDED_SLUGS = 'san-pham, chuyen-muc-trang-chu, thuc-pham, nguyen-pha-che-lam-banh';

	/**
	 * Hooks the admin menu + settings registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Bước 1: Tạo Menu trong Dashboard.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Cấu hình Filter Plugin', 'goldenfarm-product-filter' ), // Tiêu đề trang (Page Title).
			__( 'Tùy chọn Filter', 'goldenfarm-product-filter' ),        // Tên hiển thị trên Menu (Menu Title).
			'manage_options',                                            // Quyền truy cập (Capability).
			self::MENU_SLUG,                                             // Slug của menu.
			array( __CLASS__, 'render_page' ),                           // Hàm callback dựng giao diện HTML.
			'dashicons-filter',                                          // Icon menu (dùng Dashicons).
			85                                                           // Vị trí hiển thị trên Admin Bar.
		);
	}

	/**
	 * Bước 2: Đăng ký các Trường Cài đặt (Settings).
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'gf_pf_settings_group',           // Option group (nhóm settings).
			self::OPTION_EXCLUDED_SLUGS,      // Tên Option lưu trong Database.
			array(
				'type'              => 'string',
				'default'           => self::DEFAULT_EXCLUDED_SLUGS,
				'sanitize_callback' => array( __CLASS__, 'sanitize_excluded_slugs' ),
			)
		);
	}

	/**
	 * Sanitizes the comma-separated slug list before saving.
	 *
	 * @param mixed $value Raw input value.
	 * @return string
	 */
	public static function sanitize_excluded_slugs( $value ) {
		$slugs = array();

		foreach ( explode( ',', (string) $value ) as $raw ) {
			$slug = sanitize_title( trim( (string) $raw ) );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}

		return implode( ', ', array_unique( $slugs ) );
	}

	/**
	 * Bước 3: Dựng Giao diện HTML.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php
				// Sinh các token bảo mật và hidden field bắt buộc của WP.
				settings_fields( 'gf_pf_settings_group' );
				?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( self::OPTION_EXCLUDED_SLUGS ); ?>"><?php esc_html_e( 'Slug Danh mục cần ẩn', 'goldenfarm-product-filter' ); ?></label>
							</th>
							<td>
								<input type="text"
									id="<?php echo esc_attr( self::OPTION_EXCLUDED_SLUGS ); ?>"
									name="<?php echo esc_attr( self::OPTION_EXCLUDED_SLUGS ); ?>"
									value="<?php echo esc_attr( get_option( self::OPTION_EXCLUDED_SLUGS, self::DEFAULT_EXCLUDED_SLUGS ) ); ?>"
									class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Nhập slug danh mục muốn ẩn khỏi bộ lọc, cách nhau bằng dấu phẩy (Ví dụ: thuc-pham, san-pham).', 'goldenfarm-product-filter' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Lưu Cấu Hình', 'goldenfarm-product-filter' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Active excluded slugs as a clean array.
	 *
	 * @return string[]
	 */
	public static function get_excluded_slugs() {
		$raw = get_option( self::OPTION_EXCLUDED_SLUGS, self::DEFAULT_EXCLUDED_SLUGS );

		if ( ! is_string( $raw ) ) {
			$raw = self::DEFAULT_EXCLUDED_SLUGS;
		}

		$slugs = array();

		foreach ( explode( ',', $raw ) as $slug ) {
			$slug = trim( (string) $slug );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}

		return $slugs;
	}
}
