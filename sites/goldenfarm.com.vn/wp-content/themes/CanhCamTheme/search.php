<?php
add_filter('body_class', function ($classes) {
	return array_merge($classes, array('no-banner-page'));
});
?>
<?php
$key = isset($_GET['s']) && $_GET['s'] ? $_GET['s'] : '';
$choose_categories_product_search = get_field('choose_categories_product_search', 'options');
$choose_categories_post_search = get_field('choose_categories_post_search', 'options');
?>

<?php get_header() ?>
<div class="single-frame">
	<section class="search-page section">
		<div class="container">
			<h1 class="site-title text-center mb-5">
				<?php _e('Tìm kiếm', 'canhcamtheme') ?>
			</h1>
			<div class="wrap-form-search mb-2">
				<form class="searchbox flex items-center w-full relative" action="<?php bloginfo('url') ?>/" method="GET" role="form">
					<input class="w-full" name="s" class="form-control" type="text" placeholder="<?php _e('Tìm kiếm', 'canhcamtheme') ?>" value="<?php echo $key ?>">
					<button type="submit" class="flex items-center justify-center">
						<em class="fa-regular fa-magnifying-glass"></em>
					</button>
				</form>
			</div>
			<?php if (!($key == '')) : ?>
				<div class="search-query">
					<?php _e('Kết quả tìm kiếm từ khóa', 'canhcamtheme') ?>: " <span>
						<?php echo get_search_query() ?>
					</span> "
				</div>
				<?php
				$args_pro = array(
					'post_type' => array('product'),
					'posts_per_page' => 20,
					's' => $key,
					// 'sentence' => true,
				);
				$the_query_pro = new WP_Query($args_pro);
				$count_pro = $the_query_pro->post_count;
				if ($the_query_pro->have_posts()) : ?>
					<div class="box-load-more">
						<h2 class="section-header-32 font-bold text-primary-new-1 mt-10">
							<?php _e('Sản phẩm', 'canhcamtheme') ?>
						</h2>
						<div class="product-list mt-5 load-more-list" data-show-more="4">
							<?php if ($the_query_pro->have_posts()) :
								while ($the_query_pro->have_posts()) :
									$the_query_pro->the_post(); ?>
									<div class="load-more-item hidden">
										<?php wc_get_template_part('content', 'product'); ?>
									</div>
							<?php endwhile;
							endif;
							wp_reset_postdata(); ?>
						</div>
						<?php if ($count_pro > 5) : ?>
							<div class="button mt-5 justify-center">
								<a href="javascript:;" class="btn-solid load-more-button" data-load-more="4">
									<span><?php echo _e('Xem thêm sản phẩm', 'canhcamtheme') ?></span>
									<i class="fa-light fa-chevron-down"></i>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php
				// Loop through parent categories and display posts under each category
				foreach ($choose_categories_post_search as $category_key => $category) {
				?>
					<?php
					// Query posts for the current parent category
					$args = array(
						'post_type' => 'post',
						'posts_per_page' => -1,
						'category__in' => $category->term_id,
						's' => $key,
					);
					$parent_query = new WP_Query($args);
					$count_parent = $parent_query->found_posts;
					if ($parent_query->have_posts()) :
					?>
					<div class="box-load-more">
						<h2 class="section-header-32 font-bold text-primary-new-1 mt-10">
							<?php echo $category->name; ?>
						</h2>
						<div class="news-list load-more-list grid grid-cols-1 gap-8 mt-5 sm:grid-cols-2 md:grid-cols-4" data-show-more="4">
							<?php
								while ($parent_query->have_posts()) :
									$parent_query->the_post();
							?>
									<div class="load-more-item hidden">
										<?php get_template_part('modules/news/news_item', '', array('idPost' => get_the_ID())) ?>
									</div>
							<?php
								endwhile;
								wp_reset_postdata();
							?>
						</div>
						<?php if ($count_parent > 3) : ?>
							<div class="button mt-5 justify-center">
								<a href="javascript:;" class="btn-solid load-more-button" data-load-more="4">
									<span><?php echo _e('Xem thêm', 'canhcamtheme') ?></span>
									<i class="fa-light fa-chevron-down"></i>
								</a>
							</div>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				<?php
				}
				?>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php get_footer() ?>
