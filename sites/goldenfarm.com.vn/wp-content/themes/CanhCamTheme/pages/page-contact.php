<?php
/*
Template Name: Page - Contact
*/
?>

<?php get_header() ?>

<section class="contact-section section">
	<div class="container">
		<h1 class="site-title text-center">
			<?php the_title() ?>
		</h1>
		<div class="social-list mt-5">
			<?php
			$site_social_list = get_field('site_social_list', 'options');
			if ($site_social_list) :
				echo '<ul class="justify-center">';
				foreach ($site_social_list as $item) :
					echo '<li><a href="' . $item['url'] . '" title="' . $item['name'] . '" target="_blank" rel="nofollow">' . $item['icon'] . '</a></li>';
				endforeach;
				echo '</ul>';
			endif;
			?>
		</div>
		<div class="box-contact-wrap mt-10">
			<?php $company_info = get_field('company_info', 'options'); ?>
			<div class="row -mt-8">
				<div class="col w-full mt-8 lg:w-1/2">
					<div class="contact-info">
						<?php the_content() ?>
					</div>
				</div>
				<div class="col w-full mt-8 lg:w-1/2">
					<div class="contact-maps">
						<div class="iframe-scale">
							<?php echo $company_info['company_maps'] ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<?php get_footer() ?>
