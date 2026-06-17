<?php get_header() ?>

<?php if (is_cart() || is_checkout()) : ?>
	<div class="cart-section section">
		<div class="container">
			<?php the_content() ?>
		</div>
	</div>
<?php else : ?>
	<div class="container section">
		<div class="full-content">
			<?php the_content(); ?>
		</div>
	</div>
<?php endif; ?>

<?php get_footer() ?>
