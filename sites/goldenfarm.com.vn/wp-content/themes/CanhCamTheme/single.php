<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package CanhCam
 */
?>

<?php get_header() ?>

<?php
// Kiểm tra loại bài viết hiện tại
$current_post_type = get_post_type();

if ($current_post_type === 'banner') {
    // 1. XỬ LÝ RIÊNG CHO BANNER: Bỏ qua phần tìm chuyên mục để tránh lỗi
    echo '<div class="container text-center" style="padding: 50px 0;">';
    the_title('<h1>', '</h1>');
    if (has_post_thumbnail()) {
        the_post_thumbnail('full'); // Hiển thị ảnh banner nếu có
    }
    the_content(); // Hiển thị nội dung chi tiết banner nếu có
    echo '</div>';
    
    // Nếu bạn có một file giao diện riêng cho banner, bạn có thể gọi nó ở đây:
    // get_template_part('modules/banner/single');
    
} else {
    // 2. XỬ LÝ CHO BÀI VIẾT / TIN TỨC THÔNG THƯỜNG (Code gốc của bạn đã sửa lỗi)
    $category = get_the_category();
    $template_name = ''; 

    if (!empty($category) && isset($category[0])) {
        $categoryID       = $category[0]->cat_ID;
        $categoryTaxonomy = $category[0]->taxonomy;
        $choose_template  = get_field('choose_template', $categoryTaxonomy . '_' . $categoryID);
        
        if (is_array($choose_template) && isset($choose_template['value'])) {
            $template_name = $choose_template['value'];
        }
    }

    get_template_part('modules/news/single_' . $template_name);
}
?>

<?php get_footer() ?>