<?php
$home_event_list = get_field('home_event_list', get_the_ID());
if($home_event_list) :
?>
<section class="home-events bg-gradient-7 py-2">
	<div class="container">
		<div class="swiper-relative one-slider">
			<div class="swiper">
				<div class="swiper-wrapper">
					<?php foreach ($home_event_list as $item) : ?>
						<div class="swiper-slide"><a class="home-events-item" href="<?php echo $item['url'] ?>"><strong><?php echo _e('Sự kiện') ?>: </strong> <?php echo $item['title'] ?></a></div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>
