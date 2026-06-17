<?php
$id_category = get_queried_object()->term_id;
$taxonomy = get_queried_object()->taxonomy;
if ($id_category) {
	$id = $taxonomy . '_' . $id_category;
} else {
	$id = get_the_ID();
}
$banner = get_field('banner_select_page', $id);
?>

<?php if ($banner) : ?>
	<section class="banner-child banner-slider">
		<div class="swiper">
			<div class="swiper-wrapper">
				<?php foreach ($banner as $item) : ?>
					<div class="swiper-slide">
						<div class="image img-cover">
							<img class="" src="<?php echo get_the_post_thumbnail_url($item->ID) ?>" loading="lazy" alt="<?php echo $item->post_title; ?>">
							<div class="caption">
								<?php echo $item->post_content; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
