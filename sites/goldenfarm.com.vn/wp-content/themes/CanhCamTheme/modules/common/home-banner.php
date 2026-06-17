<?php
$id_category = get_queried_object()->term_id ?? null;
$taxonomy = get_queried_object()->taxonomy ?? null;

if ($id_category) {
    $id = $taxonomy . '_' . $id_category;
} else {
    $id = get_the_ID();
}

// ✅ Lấy danh sách banner từ ACF
$banner = get_field('banner_select_page', $id);
?>

<?php if ($banner) : ?>
    <section class="banner-section banner-slider">
        <div class="swiper">
            <div class="swiper-wrapper">
                <?php foreach ($banner as $item) : ?>
                    <div class="swiper-slide">
                        <?php $link_button = get_field('link_button', $item->ID); ?>
                        <div class="image img-cover">
                            <img src="<?php echo get_the_post_thumbnail_url($item->ID, 'full'); ?>" 
                                 loading="lazy" 
                                 alt="<?php echo esc_attr($item->post_title); ?>">
                        </div>
                        <div class="banner-content container">
                            <div class="box-content">
                                <div class="desc">
                                    <?php echo $item->post_content; ?>
                                </div>
                                <?php if (!empty($link_button['url'])): ?>
                                <div class="button mt-8">
                                    <a class="btn-solid" href="<?php echo esc_url($link_button['url']); ?>">
                                        <span><?php echo esc_html($link_button['title']); ?></span>
                                        <i class="fa-light fa-chevron-right"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
