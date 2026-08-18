<?php

/**
 * The template for displaying product content within loops
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

global $product;

// 1. Lấy danh mục sản phẩm (dùng để lấy ACF brand color)
$category = get_the_terms($product->get_ID(), 'product_cat');
if (!$category) {
    $parent_id = wp_get_post_parent_id($product->get_ID());
    $category = get_the_terms($parent_id, 'product_cat');
}

$choose_brand_color = '';
if ($category) {
    $current_category   = $category[0];
    $choose_brand_color = get_field('choose_brand_color', $current_category);
}

// 2. FIX: Lấy LOGO THƯƠNG HIỆU (thay vì lấy ảnh Danh mục)
$brand_logo_url = '';
$brand_name     = '';

// Lấy taxonomy thương hiệu (Mặc định: product_brand)
$brand_taxonomy = apply_filters('gf_pf_brand_taxonomy', 'product_brand');
$brand_terms    = get_the_terms($product->get_ID(), $brand_taxonomy);

if (!empty($brand_terms) && !is_wp_error($brand_terms)) {
    $current_brand = array_shift($brand_terms);
    $brand_name    = $current_brand->name;
    
    // Tìm ID ảnh logo của Brand (Thử thumbnail_id của WC hoặc pwb_brand_image/yith)
    $brand_thumb_id = get_term_meta($current_brand->term_id, 'thumbnail_id', true);
    if (!$brand_thumb_id) {
        $brand_thumb_id = get_term_meta($current_brand->term_id, 'pwb_brand_image', true);
    }

    if ($brand_thumb_id) {
        $brand_logo_url = wp_get_attachment_image_url($brand_thumb_id, 'thumbnail');
    }
}
?>

<div <?php wc_product_class('product-item is-primary-' . $choose_brand_color, $product); ?>>
    <div class="brand">
        <?php if ($brand_logo_url) : ?>
            <img src="<?php echo esc_url($brand_logo_url); ?>" alt="<?php echo esc_attr($brand_name); ?>">
        <?php endif; ?>
    </div>

    <div class="image img-contain">
        <a href="<?php the_permalink(); ?>">
            <?php
            $image_id  = get_post_thumbnail_id();
            $image_url = wp_get_attachment_image_src($image_id, 'full');

            if ($image_url && is_array($image_url) && !empty($image_url[0])) {
                $src = esc_url($image_url[0]);
                $alt = esc_attr(get_the_title());
            } else {
                $src = wc_placeholder_img_src('woocommerce_thumbnail');
                $alt = esc_attr(get_the_title()) . ' - ' . __('No image', 'woocommerce');
            }
            ?>
            <img src="<?php echo $src; ?>" alt="<?php echo $alt; ?>">
        </a>
    </div>

    <div class="caption">
        <h3 class="product-title">
            <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
            </a>
            <div class="product-information">
                <?php outputOptions(get_the_ID()); ?>
            </div>
        </h3>

        <div class="price">
            <?php
            $product_id = $product->get_id();
            $product    = wc_get_product($product_id);

            if ($product && $product->is_type('variable')) {
                $default_attributes = $product->get_default_attributes();
                $variation_id       = find_matching_product_variation_id($product, $default_attributes);

                if ($variation_id) {
                    $variation       = wc_get_product($variation_id);
                    $variation_price = $variation->get_price();
                    echo wc_price($variation_price);
                } else {
                    foreach ($product->get_children() as $child_id) {
                        $variation = wc_get_product($child_id);
                        if ($variation && $variation->is_type('variation')) {
                            $variation_price = $variation->get_price();
                            echo wc_price($variation_price);
                            break;
                        }
                    }
                }
            } else {
                echo $product->get_price_html();
            }
            ?>
        </div>
    </div>
</div>