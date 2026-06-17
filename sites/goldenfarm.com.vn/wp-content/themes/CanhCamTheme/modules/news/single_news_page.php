<section class="news-section section-large">
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
					<hr class="my-8 border-t border-neutral-600">
					<div class="full-content">
						<?php the_content() ?>
					</div>
				</div>
			</div>
			<div class="col w-full mt-8 lg:w-1/3 xl:w-1/4">
				<?php
				$category = get_the_category();
				// var_dump($category);
				$categoryParent = get_category($category[0]->category_parent);
				$categoryID = $categoryParent->cat_ID;
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
							<?php echo _e('Tin tức khác', 'canhcamtheme') ?>
						</h3>
						<div class="news-detail-other-list space-y-8 mt-8">
							<?php
							while ($the_query_other->have_posts()) : $the_query_other->the_post();
								get_template_part('modules/news/news_item', '', array('idPost' => get_the_ID(), 'class' => 'news-other-item'));
							endwhile;
							?>
						</div>
					</div>
				<?php
				endif;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</div>
</section>
