<?php
/*
Template Name: Page - Dealer
*/
?>

<?php get_header() ?>

<section class="dealer-section section">
	<div class="container">
		<h1 class="site-title text-center">
			<?php the_title() ?>
		</h1>

		<?php get_template_part('modules/dealer/dealer-filter') ?>
		<?php get_template_part('modules/dealer/dealer-result') ?>

	</div>
</section>

<?php get_footer() ?>
