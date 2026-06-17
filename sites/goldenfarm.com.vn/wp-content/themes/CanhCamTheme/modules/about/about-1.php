<?php
$about_1_title = get_field('about_1_title', get_the_ID());
$about_1_description = get_field('about_1_description', get_the_ID());
$about_1_list = get_field('about_1_list', get_the_ID());
$about_1_background = get_field('about_1_background', get_the_ID());
$about_1_repeater = get_field('about_1_repeater', get_the_ID());

// Remove accents from the title
$title_without_accents = remove_accents($about_1_title);
// Print the value with a specific format, for example, lowercase and replacing spaces with hyphens
$formatted_title = strtolower(str_replace(' ', '-', $title_without_accents));
?>

<section class="about-history section bg-primary-bg-history lozad-bg" id="<?php echo $formatted_title ?>" data-background-image="<?php echo $about_1_background['url'] ?>">
	<div class="container">
		<h1 class="site-title text-center">
			<?php echo $about_1_title ?>
		</h1>
		<div class="site-desc text-primary-dark-green-2 text-center mx-auto mt-5">
			<?php echo $about_1_description ?>
		</div>
		<div class="swiper-relative history-slider pb-15 mt-11 lg:pb-0">
			<div class="swiper">
				<div class="swiper-wrapper">
					<?php foreach ($about_1_list as $item) : ?>
						<div class="swiper-slide">
							<div class="history-item">
								<div class="dot"></div>
								<div class="caption">
									<div class="year"><?php echo $item['year'] ?></div>
									<p><?php echo $item['title'] ?><br><strong><?php echo $item['sub_title'] ?></strong></p>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="mobile-only">
				<div class="swiper-pagination"></div>
			</div>
			<div class="desktop-only">
				<div class="swiper-button is-small is-abs">
					<div class="button-prev"><i class="fa-light fa-chevron-left"></i></div>
					<div class="button-next"><i class="fa-light fa-chevron-right"></i></div>
				</div>
			</div>
		</div>
	</div>
</section>

<?php if($about_1_repeater) : ?>
<section class="about-scale section bg-black">
	<div class="container">
		<div class="scale-list">
			<div class="row -mt-8">
				<?php foreach ($about_1_repeater as $item) : ?>
					<div class="col w-full mt-8 md:w-1/2 lg:w-1/3">
						<div class="scale-item p-5 text-center lg:py-8">
							<div class="icon">
								<img class="lozad" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo $item['image']['url'] ?>" loading="lazy" alt="<?php echo $item['title'] ?>">
							</div>
							<div class="caption mt-5">
								<h3 class="section-header-32 font-bold text-primary-new-1">
									<?php echo $item['title'] ?>
								</h3>
								<div class="brief body-18 font-normal text-white mt-8">
									<?php echo $item['content'] ?>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>
