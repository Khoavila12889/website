<?php
$home_about_title = get_field('home_about_title', get_the_ID());
$home_about_list = get_field('home_about_list', get_the_ID());
if ($home_about_list) :
?>

	<section class="home-about home-factory section-large bg-black">
		<h2 class="site-title text-center">
			<?php echo $home_about_title ?>
		</h2>
		<div class="container">
			<div class="home-about-list grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
				<?php foreach ($home_about_list as $item) : ?>
					<div class="scale-item p-5 text-center h-full flex flex-col lg:py-8">
						<div class="icon">
							<img src="<?php echo $item['icon']['url'] ?>" alt="<?php echo $item['title'] ?>">
						</div>
						<div class="caption mt-5 flex flex-col justify-between flex-1 h-full">
							<div class="top">
								<h3 class="sub-header-24 font-bold text-primary-new-1">
									<?php echo $item['title'] ?>
								</h3>
								<div class="brief body-18 font-normal text-white mt-8">
									<?php echo $item['content'] ?>
								</div>
							</div>
							<?php if ($item['url']) : ?>
								<div class="button mt-6 justify-center">
									<a class="btn-solid" href="<?php echo $item['url']['url'] ?>" target="<?php echo $item['url']['target'] ?>">
										<span><?php echo _e('Xem thêm', 'canhcamtheme') ?></span>
										<i class="fa-light fa-chevron-right"></i>
									</a>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
