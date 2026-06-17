<?php
// Taxonomy get field
$terms = get_queried_object(); // get info taxonomy
$taxonomy = $terms->taxonomy; // get taxonomy name
$taxonomyID = $terms->term_taxonomy_id; // get ID taxonomy name
$taxonomyTitle = $terms->name; // get Title taxonomy name
$depthParentID = get_term_depth($taxonomy, 0); // get ID taxonomy parent (cha)
$depthParentTitle = get_term_top_most_parent($terms, $taxonomy)->name; // get top parent category name
$acf_key = $taxonomy . '_' . $depthParentID;
$acf_key_current_page = $taxonomy . '_' . $taxonomyID;

$all_page_child = array(
	'hide_empty' => false,
	'parent' => $depthParentID,
);

$fullSlugCategory = get_category_link($taxonomyID); // lấy full dường dẫn
$all_page_child = get_terms($taxonomy, $all_page_child);

$count_page_child = count($all_page_child);
$category_parent = get_term($taxonomyID, $taxonomy)->parent;
?>

<section class="news-section section-large">
	<div class="container">
		<h1 class="site-title text-center">
			<?php echo $depthParentTitle ?>
		</h1>
		<nav class="site-nav mt-8">
			<?php if ($all_page_child) : ?>
				<?php foreach ($all_page_child as $key => $item) : ?>
					<?php
					// get terms parent category by item
					$parentID = $item->parent;
					?>
					<?php if ($key == 0) :
						echo '<ul>';
					endif;
					?>
					<li class="<?php echo $item->term_id == $taxonomyID || $item->term_id == $category_parent ? 'current-page' : '' ?>">
						<h2>
							<a href="<?php echo get_category_link($item->term_id) ?>"><?php echo $item->name ?></a>
						</h2>
					</li>
					<?php if ($key + 1 == $count_page_child) :
						echo '</ul>';
					endif;
					?>
				<?php endforeach; ?>
				<?php foreach ($all_page_child as $key => $item) : ?>
					<?php
					if($item->term_id == $taxonomyID || $item->term_id == $category_parent) :
					$child_page = get_terms($taxonomy, array(
						'hide_empty' => false,
						'parent' => $item->term_id,
					));
					if ($child_page) :
						echo '<ul class="nav-list-child mt-8">';
						foreach ($child_page as $key => $child) :
					?>
							<li class="<?php echo $child->term_id == $taxonomyID ? 'current-page' : '' ?>">
								<h3>
									<a href="<?php echo get_category_link($child->term_id) ?>"><?php echo $child->name ?></a>
								</h3>
							</li>
					<?php
						endforeach;
						echo '</ul>';
					endif;
					endif;
					?>
				<?php endforeach; ?>
			<?php endif; ?>
		</nav>
		<?php
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		$args = array(
			'post_type' => 'post',
			'posts_per_page' => 6,
			'order' => 'DESC',
			'orderby' => 'date',
			'post_status' => 'publish',
			'paged' => $paged,
			'cat' => $taxonomyID,
		);
		$the_query = new WP_Query($args);
		if ($the_query->have_posts()) :
		?>
			<div class="news-list grid grid-cols-1 gap-8 mt-10 sm:grid-cols-2 lg:mt-16 lg:grid-cols-3 xl:mt-16">
				<?php
				while ($the_query->have_posts()) : $the_query->the_post();
					get_template_part('modules/news/news_item', '', array('idPost' => get_the_ID()));
				endwhile;
				?>
			</div>
		<?php
		else :
			echo '<div class="news-list mt-10 xl:mt-16">';
			echo '<h2 class="sub-header-24 font-bold text-center">' . _e('Đang cập nhật tin tức mới nhất!', 'canhcamtheme') . '</h2>';
			echo '</div>';
		endif;
		wp_reset_postdata();
		echo wp_bootstrap_pagination(array('custom_query' => $the_query))
		?>
	</div>
</section>
