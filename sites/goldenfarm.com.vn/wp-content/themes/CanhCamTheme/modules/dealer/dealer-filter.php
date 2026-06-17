<?php
$home_choose_dealer = isset($args['home_choose_dealer']) ? $args['home_choose_dealer'] : NULL;
$GetDealerSearch = isset($_GET['DealerSearch']) ? $_GET['DealerSearch'] : NULL;

$terms = get_queried_object(); // get info taxonomy
$dealerID = ''; // get ID page
if ($home_choose_dealer) {
	$dealerID = $home_choose_dealer->ID;
} else {
	$dealerID = get_the_ID();
}

// Get info page by ID
$full_url = get_permalink($dealerID); // get full url
$image_url = get_the_post_thumbnail_url($dealerID, 'full'); // get image url

// ACF
$gallery_list = get_field('gallery_list', $dealerID);
$dealer_suggest = get_field('dealer_suggest', $dealerID);

?>

<?php if (!$GetDealerSearch) : ?>
	<div class="box-dealer lozad-bg mt-8" data-background-image="<?php echo $image_url ?>">
		<?php if ($gallery_list) : ?>
			<div class="swiper-relative dealer-slider pb-15 lg:pb-0">
				<div class="swiper">
					<div class="swiper-wrapper">
						<?php foreach ($gallery_list as $item) : ?>
							<div class="swiper-slide">
								<div class="image img-contain">
									<img class="lozad" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo $item['url'] ?>" alt="">
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="mobile-only">
					<div class="swiper-pagination"></div>
				</div>
				<div class="desktop-only">
					<div class="swiper-button is-abs">
						<div class="button-prev"><i class="fa-light fa-chevron-left"></i></div>
						<div class="button-next"><i class="fa-light fa-chevron-right"></i></div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php
		// Shops - Custom Post Type
		$shop_args = array(
			'post_type' => 'shop',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'title',
			'order' => 'ASC',
		);
		$shop_query = new WP_Query($shop_args);

		if ($shop_query->have_posts()) :
		?>
			<div class="dealer-search">
				<form action="<?php echo $full_url ?>">
					<div class="input-group">
						<select name="DealerSearch">
							<option value="">
								<?php echo _e('Tìm nhà phân phối ở gần bạn?', 'canhcamtheme') ?>
							</option>
							<?php while ($shop_query->have_posts()) : $shop_query->the_post();
							?>
								<option value="<?php echo get_the_ID() ?>" <?php echo $GetDealerSearch == get_the_ID() ? 'selected' : '' ?>>
									<?php the_title() ?>
								</option>
							<?php endwhile; ?>
						</select>
						<div class="button-group">
							<button class="btn-solid btn-submit" type="submit">
								<i class="fa-light fa-search"></i>
								<span><?php echo _e('Tìm cửa hàng', 'canhcamtheme') ?></span>
							</button>
						</div>
					</div>
				</form>
			</div>
		<?php endif;
		wp_reset_postdata(); ?>

		<?php if ($dealer_suggest) : ?>
			<div class="dealer-suggest-result">
				<?php if ($home_choose_dealer) : ?>
					<?php foreach ($dealer_suggest as $key => $item) : if ($key < 5) : ?>
							<a class="btn-lined" href="<?php echo $full_url . '?DealerSearch=' . $item->ID ?>">
								<?php echo $item->post_title ?>
							</a>
					<?php endif;
					endforeach; ?>
					<a class="btn-lined" href="<?php echo get_the_permalink($home_choose_dealer->ID) ?>">
						<?php echo _e('Xem thêm...', 'canhcamtheme') ?>
					</a>
				<?php else : ?>
					<?php foreach ($dealer_suggest as $item) : ?>
						<a class="btn-lined" href="<?php echo $full_url . '?DealerSearch=' . $item->ID ?>">
							<?php echo $item->post_title ?>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
