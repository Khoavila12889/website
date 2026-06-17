<?php
$home_dealer_title = get_field('home_dealer_title', get_the_ID());
$home_choose_dealer = get_field('home_choose_dealer', get_the_ID());
if ($home_choose_dealer) :
?>

	<section class="dealer-section section-large">
		<div class="container">
			<h2 class="site-title text-center">
				<?php echo $home_dealer_title ?>
			</h2>
			<?php
			get_template_part('modules/dealer/dealer-filter', '', array('home_choose_dealer' => $home_choose_dealer));
			?>
		</div>
	</section>
<?php endif; ?>
