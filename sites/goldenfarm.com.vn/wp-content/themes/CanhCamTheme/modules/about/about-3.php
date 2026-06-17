<?php
$about_3_title = get_field('about_3_title', get_the_ID());
$about_3_list = get_field('about_3_list', get_the_ID());

// Remove accents from the title
$title_without_accents = remove_accents($about_3_title);
// Print the value with a specific format, for example, lowercase and replacing spaces with hyphens
$formatted_title = strtolower(str_replace(' ', '-', $title_without_accents));
?>

<section class="about-scale section" id="<?php echo $formatted_title ?>">
	<div class="container">
		<h2 class="site-title text-center">
			<?php echo $about_3_title ?>
		</h2>
		<div class="scale-list">
			<div class="row -mt-8">
				<?php foreach ($about_3_list as $item) : ?>
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
