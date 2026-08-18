<section class="delicious-recipe-section section-large">
	<div class="container">
		<div class="row -mt-8">
			<div class="col w-full mt-8 lg:w-2/3 xl:w-3/4">
				<div class="box-delicious-recipe-detail">
					<h1 class="section-header-32 font-bold text-primary-new-1">
						<?php echo edit_link_post(get_the_ID()) ?>
						<?php the_title() ?>
					</h1>
					<div class="brief mt-8 body-18 font-normal text-white">
						<?php the_excerpt() ?>
					</div>
					<?php
					$time_list = get_field('time_list', get_the_ID());
					$is_level_of_difficult = get_field('is_level_of_difficult', get_the_ID());
					$level_of_difficult = get_field('level_of_difficult', get_the_ID());
					$implementation_guide = get_field('implementation_guide', get_the_ID());
					$prepared_ingredients = get_field('prepared_ingredients', get_the_ID());
					$iframe_video = get_field('iframe_video', get_the_ID());
					if ($time_list || $level_of_difficult) :
					?>
						<div class="step-list mt-8 space-y-4">
							<?php if (is_array($time_list)) : ?>
								<?php foreach ($time_list as $item) : ?>
									<div class="step-item">
										<div class="step-title">
											<?php echo isset($item['title']) ? $item['title'] : '' ?>
										</div>
										<div class="step-content">
											<?php echo isset($item['time']) ? $item['time'] : '' ?>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
							<?php if ($is_level_of_difficult && isset($level_of_difficult['label'])) : ?>
								<div class="step-item">
									<div class="step-title">
										<?php echo _e('Độ khó', 'canhcamtheme') ?>
									</div>
									<div class="step-content">
										<?php echo $level_of_difficult['label'] ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<hr class="my-8 border-t border-neutral-600">
					<div class="full-content">

						<?php the_content() ?>

						<?php if ($implementation_guide) : ?>

						<?php endif; ?>

						<?php if ($prepared_ingredients) : ?>

						<?php endif; ?>

						<?php if ($iframe_video) : ?>
							<h3><?php echo _e('Video hướng dẫn', 'canhcamtheme') ?></h3>
							<div class="iframe-video">
								<?php echo $iframe_video ?>
							</div>
						<?php endif; ?>

					</div>

					<?php
					$choose_product_by_delicious_recipe = get_field('choose_product_by_delicious_recipe', get_the_ID());
					if ($choose_product_by_delicious_recipe) :
					?>
						<div class="home-products section-t-large">
							<h2 class="site-title">
								<?php echo _e('Sản phẩm theo công thức', 'canhcamtheme') ?>
							</h2>
							<div class="swiper-relative pro-auto-16-slider pb-15 mt-10 lg:pb-0 equal-height">
								<div class="swiper">
									<div class="swiper-wrapper">
										<?php foreach ($choose_product_by_delicious_recipe as $product_item) : ?>
											<?php // if ($product_item->ID == $product_id) continue; ?>
											<div class="swiper-slide">
												<?php $post_object = get_post($product_item->ID); ?>
												<?php
												if ($post_object) {
													setup_postdata($GLOBALS['post'] = &$post_object);
													wc_get_template_part('content-product', '', $product_item->ID);
													wp_reset_postdata();
												}
												?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
								<div class="mobile-only">
									<div class="swiper-pagination"></div>
								</div>
								<div class="desktop-only">
									<div class="swiper-button is-abs">
										<div class="button-prev"><i class="fa-thin fa-chevron-left"></i></div>
										<div class="button-next"><i class="fa-thin fa-chevron-right"></i></div>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="col w-full mt-8 lg:w-1/3 xl:w-1/4">
				<?php
				$category = get_the_category();
				
				// Kiểm tra nếu bài viết thực sự có chuyên mục thì mới truy vấn bài viết liên quan
				if (!empty($category) && isset($category[0])) {
					$categoryParent = get_category($category[0]->category_parent);
					$categoryID = ($categoryParent && !is_wp_error($categoryParent)) ? $categoryParent->cat_ID : 0;
					
					$args = array(
						'post_type' => 'post',
						'posts_per_page' => 4,
						'order' => 'DESC',
						'orderby' => 'date',
						'post_status' => 'publish',
						'cat' => $category[0]->term_id,
						'post__not_in' => array(get_the_ID())
					);
					$the_query_other = new WP_Query($args);
					if ($the_query_other->have_posts()) :
					?>
						<div class="box-delicious-recipe-other">
							<h3 class="section-header-32 font-bold text-primary-new-1">
								<?php echo _e('Công thức khác', 'canhcamtheme') ?>
							</h3>
							<div class="delicious-recipe-other-list space-y-8 mt-8">
								<?php
								while ($the_query_other->have_posts()) : $the_query_other->the_post();
									get_template_part('modules/news/delicious_recipe_item', '', array('idPost' => get_the_ID()));
								endwhile;
								?>
							</div>
						</div>
					<?php
					endif;
					wp_reset_postdata();
				}
				?>
			</div>
		</div>
	</div>
</section>