<?php
$about_2_title = get_field('about_2_title', get_the_ID());
$about_2_list = get_field('about_2_list', get_the_ID());

// Remove accents from the title
$title_without_accents = remove_accents($about_2_title);
// Print the value with a specific format, for example, lowercase and replacing spaces with hyphens
$formatted_title = strtolower(str_replace(' ', '-', $title_without_accents));
?>

<section class="about-point overflow-hidden bg-neutral-950 section" id="<?php echo $formatted_title ?>">
	<div class="container">
		<h2 class="site-title text-center">
			<?php echo $about_2_title ?>
		</h2>
	</div>
	<div class="swiper-relative is-linear point-slider mt-11 mx-auto pb-15 lg:mt-30 lg:pb-0 equal-height">
		<div class="swiper">
			<div class="swiper-wrapper">
				<?php foreach ($about_2_list as $item) : ?>
					<div class="swiper-slide">
						<a class="point-item" href="<?php echo $item['url'] ?>">
							<div class="image img-cover img-zoom">
								<img class="lozad" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo $item['image']['url'] ?>" loading="lazy" alt="<?php echo $item['title'] ?>">
							</div>
							<div class="caption">
								<div class="year"><?php echo $item['year'] ?></div>
								<p><?php echo $item['title'] ?></p>
							</div>
						</a>
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
</section>
