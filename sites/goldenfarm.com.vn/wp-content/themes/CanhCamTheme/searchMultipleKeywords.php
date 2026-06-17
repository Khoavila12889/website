<?php
function remove_vietnamese_accents($str) {
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
    $str = preg_replace("/(đ)/", 'd', $str);
    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
    $str = preg_replace("/(Đ)/", 'D', $str);
    return $str;
}

$paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
$search_query = get_search_query();
$keywords = explode(' ', $search_query);
$keywords = array_filter(array_map('trim', $keywords));
$post_types = array('post', 'shareholder', 'product');

global $wpdb;

$search_conditions = array();
foreach ($keywords as $keyword) {
    $keyword_lower = mb_strtolower($keyword, 'UTF-8');
    $keyword_upper = mb_strtoupper($keyword, 'UTF-8');
    $keyword_no_accents_lower = strtolower(remove_vietnamese_accents($keyword));
    $keyword_no_accents_upper = strtoupper(remove_vietnamese_accents($keyword));
    
    $like_keyword_lower = '%' . $wpdb->esc_like($keyword_lower) . '%';
    $like_keyword_upper = '%' . $wpdb->esc_like($keyword_upper) . '%';
    $like_keyword_no_accents_lower = '%' . $wpdb->esc_like($keyword_no_accents_lower) . '%';
    $like_keyword_no_accents_upper = '%' . $wpdb->esc_like($keyword_no_accents_upper) . '%';

    $search_conditions[] = $wpdb->prepare(
        "(
            p.post_title LIKE %s OR 
            p.post_title LIKE %s OR 
            p.post_content LIKE %s OR 
            p.post_content LIKE %s OR 
            p.post_excerpt LIKE %s OR 
            p.post_excerpt LIKE %s OR 
            remove_vietnamese_accents(p.post_title) LIKE %s OR 
            remove_vietnamese_accents(p.post_title) LIKE %s OR 
            remove_vietnamese_accents(p.post_content) LIKE %s OR 
            remove_vietnamese_accents(p.post_content) LIKE %s OR 
            remove_vietnamese_accents(p.post_excerpt) LIKE %s OR 
            remove_vietnamese_accents(p.post_excerpt) LIKE %s OR
            EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm 
                WHERE pm.post_id = p.ID AND 
                (
                    pm.meta_value LIKE %s OR
                    pm.meta_value LIKE %s OR
                    remove_vietnamese_accents(pm.meta_value) LIKE %s OR
                    remove_vietnamese_accents(pm.meta_value) LIKE %s
                )
            )
        )",
        $like_keyword_lower, $like_keyword_upper,
        $like_keyword_lower, $like_keyword_upper,
        $like_keyword_lower, $like_keyword_upper,
        $like_keyword_no_accents_lower, $like_keyword_no_accents_upper,
        $like_keyword_no_accents_lower, $like_keyword_no_accents_upper,
        $like_keyword_no_accents_lower, $like_keyword_no_accents_upper,
        $like_keyword_lower, $like_keyword_upper,
        $like_keyword_no_accents_lower, $like_keyword_no_accents_upper
    );
}

$query = "
    SELECT DISTINCT p.ID 
    FROM {$wpdb->posts} p
    WHERE p.post_status = 'publish'
    AND p.post_type IN ('" . implode("','", $post_types) . "')
    AND (" . implode(' AND ', $search_conditions) . ")
";

// Debug: In ra câu truy vấn SQL
echo "<pre style='display:none;'>";
print_r($query);
echo "</pre>";

$results = $wpdb->get_results($query);

if ($results) {
    $post_ids = wp_list_pluck($results, 'ID');
    $args = array(
        'post_type' => $post_types,
        'post__in' => $post_ids,
        'posts_per_page' => 8,
        'paged' => $paged,
        'orderby' => 'post__in',
    );
    $the_query = new WP_Query($args);
} else {
    $the_query = new WP_Query(array('post__in' => array(0)));
}

// Hiển thị kết quả
if ($the_query->have_posts()) {
    while ($the_query->have_posts()) {
        $the_query->the_post();
        the_title();
        // Thêm các thông tin khác bạn muốn hiển thị
    }
    echo paginate_links(array(
        'total' => $the_query->max_num_pages,
        'current' => $paged,
    ));
} else {
    echo 'Không tìm thấy kết quả.';
}

wp_reset_postdata();
?>