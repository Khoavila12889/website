<?php
add_filter('body_class', function ($classes) {
	return array_merge($classes, array('no-banner-page'));
});
?>
<?php $key = isset($_GET['s']) && $_GET['s'] ? $_GET['s'] : ''; ?>

<?php get_header() ?>
<div class="single-frame">
	<section class="search-page section">
		<div class="container">
			<h1 class="site-title text-center mb-5">
				<?php _e('Tìm kiếm', 'canhcamtheme') ?>
			</h1>
			<div class="wrap-form-search mb-2">
				<form class="searchbox flex items-center w-full relative" action="<?php bloginfo('url') ?>/" method="GET" role="form">
					<input class="w-full" name="s" class="form-control" type="text" placeholder="<?php _e('Tìm kiếm', 'canhcamtheme') ?>">
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
					'post_type' => 'product',
					'posts_per_page' => -1,
					's' => $key,
				);
				$the_query_pro = new WP_Query($args_pro);
				$count_pro = $the_query_pro->found_posts;
				if ($the_query_pro->have_posts()) : ?>
					<div class="box-load-more">
						<h2 class="section-header-32 font-bold text-primary-new-1 mt-10">
							<?php _e('Sản phẩm', 'canhcamtheme') ?>
						</h2>
						<div class="product-list mt-5 load-more-list" data-show-more="5">
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
								<a href="javascript:;" class="btn-solid load-more-button" data-load-more="5">
									<span><?php echo _e('Xem thêm sản phẩm', 'canhcamtheme') ?></span>
									<i class="fa-light fa-chevron-down"></i>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php
				// Set up the custom query args
				$args = array(
					's' => $key,
					'post_type' => 'post',
					// Set post type to "post"
					'posts_per_page' => -1,
					'orderby' => 'category', // Order by category
					// Change this value to set the number of posts to display per page
					'paged' => get_query_var('paged') ? get_query_var('paged') : 1 // Get the current page number
				);

				// // Modify the default search query to only search posts
				// add_filter('pre_get_posts', function ($query) use ($args) {
				// 	if ($query->is_search() && !is_admin() && $query->is_main_query()) {
				// 		$query->set('post_type', $args['post_type']);
				// 	}
				// 	return $query;
				// });

				// Run the custom query
				$search_query = new WP_Query($args);
				?>

				<?php
				$parent_categories = array();

				// Get parent categories of each post
				if ($search_query->have_posts()) {
					while ($search_query->have_posts()) {
						$search_query->the_post();
						$categories = get_the_category();
						if ($categories) {
							foreach ($categories as $category) {
								// log_dump($category);
								// if ($category->parent == 0) {
									$parent_categories[$category->term_id] = $category->name;
								// }
							}
						}
					}
					// Reset post data
					wp_reset_postdata();

					// Loop through parent categories and display posts under each category
					foreach ($parent_categories as $parent_id => $parent_name) {
				?>
						<div class="box-load-more">
							<h2 class="section-header-32 font-bold text-primary-new-1 mt-10">
								<?php echo $parent_name; ?>
							</h2>
							<div class="news-list load-more-list grid grid-cols-1 gap-8 mt-5 sm:grid-cols-2 lg:grid-cols-3" data-show-more="3">
								<?php
								// Query posts for the current parent category
								$args = array(
									'post_type' => 'post',
									'posts_per_page' => -1,
									'category__in' => $parent_id,
									's' => $key,
								);
								$parent_query = new WP_Query($args);
								$count_parent = $parent_query->found_posts;
								if ($parent_query->have_posts()) :
									while ($parent_query->have_posts()) :
										$parent_query->the_post();
								?>
										<div class="load-more-item hidden">
											<?php get_template_part('modules/news/news_item', '', array('idPost' => get_the_ID())) ?>
										</div>
								<?php
									endwhile;
									wp_reset_postdata();
								endif;
								?>
							</div>
							<?php if ($count_parent > 3) : ?>
								<div class="button mt-5 justify-center">
									<a href="javascript:;" class="btn-solid load-more-button" data-load-more="3">
										<span><?php echo _e('Xem thêm', 'canhcamtheme') ?></span>
										<i class="fa-light fa-chevron-down"></i>
									</a>
								</div>
							<?php endif; ?>
						</div>
				<?php
					}
				}
				?>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php get_footer() ?>
