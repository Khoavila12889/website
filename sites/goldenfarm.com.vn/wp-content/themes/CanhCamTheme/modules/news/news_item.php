<?php
$idPost = $args['idPost'];
$class = isset($args['class']) ? $args['class'] : '';

$title = get_the_title($idPost);
$brief = get_post($idPost)->post_excerpt;
?>

<div class="news-item <?php echo $class ?> h-full">
	<div class="image img-cover img-zoom">
		<a class="" href="<?= get_the_permalink($idPost) ?>" title="<?php echo $title ?>">
			<img class="lozad" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?= get_the_post_thumbnail_url($idPost) ?>" alt="<?= $title ?>">
		</a>
	</div>
	<div class="caption">
		<p class="news-date">
			<?php echo get_the_date('d.m.Y', $idPost) ?>
		</p>
		<h3 class="news-title">
			<?= edit_link_post($idPost) ?>
			<a href="<?= get_the_permalink($idPost) ?>" title="<?php echo $title ?>">
				<?php echo $title ?>
			</a>
		</h3>
		<div class="brief">
			<?php echo $brief ?>
		</div>
	</div>
</div>
