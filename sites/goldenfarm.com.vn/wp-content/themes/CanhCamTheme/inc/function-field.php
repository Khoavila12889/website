<?php
// Custom field class for page
function add_field_custom_class_body()
{
	acf_add_local_field_group(array(
		'key' => 'class_body',
		'title' => 'Body: Add Class',
		'fields' => array(),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'page',
				),
			),
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'post',
				),
			),
		),
	));
	acf_add_local_field(array(
		'key' => 'add_class_body',
		'label' => 'Add class body',
		'name' => 'Add class body',
		'type' => 'text',
		'parent' => 'class_body',
	));
}
add_action('acf/init', 'add_field_custom_class_body');

//

function add_field_select_banner()
{
	acf_add_local_field_group(array(
		'key' => 'select_banner',
		'title' => 'Banner: Select Page',
		'fields' => array(),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'page',
				),
			),
			array(
				array(
					'param' => 'taxonomy',
					'operator' => '==',
					'value' => 'category',
				),
			),
			array(
				array(
					'param' => 'taxonomy',
					'operator' => '==',
					'value' => 'product_cat',
				),
			),
			// Thêm taxonomy ở dưới
			// array(
			// 	array(
			// 		'param' => 'taxonomy',
			// 		'operator' => '==',
			// 		'value' => 'danh-muc-san-pham'
			// 	)
			// )
		),
	));
	acf_add_local_field(array(
		'key' => 'banner_select_page',
		'label' => 'Chọn banner hiển thị',
		'name' => 'Chọn banner hiển thị',
		'type' => 'post_object',
		'post_type' => 'banner',
		'multiple' => 1,
		'parent' => 'select_banner',
	));
}
add_action('acf/init', 'add_field_select_banner');

// Custom taxonomy shop
function shop_custom_post_type()
{
	/*
	 * Biến $label để chứa các text liên quan đến tên hiển thị của Post Type trong Admin
	 */
	$label = array(
		'name' => __('Cửa hàng', 'canhcamtheme'),
		//Tên post type dạng số nhiều
		'singular_name' => __('Cửa hàng', 'canhcamtheme'),
		//Tên post type dạng số ít
		'view_item' => __('Xem cửa hàng', 'canhcamtheme'),
		'add_new_item' => __('Thêm mới', 'canhcamtheme'),
		'add_new' => __('Thêm mới', 'canhcamtheme'),
		'edit_item' => __('Chỉnh sửa', 'canhcamtheme'),
		'update_item' => __('Cập nhật', 'canhcamtheme'),
	);
	/*
	 * Biến $args là những tham số quan trọng trong Post Type
	 */
	$args = array(
		'labels' => $label,
		//Gọi các label trong biến $label ở trên
		'description' => __('Cửa hàng', 'canhcamtheme'),
		//Mô tả của post type
		'supports' => array(
			'title',
			'editor',
			'thumbnail',
			'excerpt',
			'comments',
		),
		//Các tính năng được hỗ trợ trong post type
		'taxonomies' => array('pages'),
		//Các taxonomy được phép sử dụng để phân loại nội dung
		'hierarchical' => false,
		//Cho phép phân cấp, nếu là false thì post type này giống như Post, true thì giống như Page
		'public' => true,
		//Kích hoạt post type
		'show_ui' => true,
		//Hiển thị khung quản trị như Post/Page
		'show_in_menu' => true,
		//Hiển thị trên Admin Menu (tay trái)
		'show_in_nav_menus' => true,
		//Hiển thị trong Appearance -> Menus
		'show_in_admin_bar' => true,
		//Hiển thị trên thanh Admin bar màu đen.
		'menu_position' => 56,
		//Thứ tự vị trí hiển thị trong menu (tay trái)
		'menu_icon' => 'dashicons-store',
		//Đường dẫn tới icon sẽ hiển thị
		'can_export' => true,
		// Có thể export nội dung bằng Tools -> Export
		'has_archive' => false,
		// Cho phép lưu trữ (month, date, year)
		'exclude_from_search' => false,
		//Loại bỏ khỏi kết quả tìm kiếm
		'rewrite' => array('slug' => 'shop'),
		'publicly_queryable' => true,
		//Hiển thị các tham số trong query, phải đặt true
		'capability_type' => 'post' //
	);
	register_post_type('shop', $args); //Tạo post type với slug tên và các tham số trong biến $args ở trên
}
add_action('init', 'shop_custom_post_type');

function create_taxonomy_category_shops()
{
	$labels = array(
		'name' => _x('Chuyên mục', 'Taxonomy General Name', 'canhcamtheme'),
		'singular_name' => _x('Cửa hàng', 'Taxonomy Singular Name', 'canhcamtheme'),
		'menu_name' => __('Chuyên mục', 'canhcamtheme'),
	);
	$args = array(
		'labels' => $labels,
		'hierarchical' => true,
		'public' => true,
		'rewrite' => array('hierarchical' => true, 'slug' => 'shops'),
		'show_ui' => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => true,
	);
	register_taxonomy('shops', array('shop'), $args);
}
add_action('init', 'create_taxonomy_category_shops');
