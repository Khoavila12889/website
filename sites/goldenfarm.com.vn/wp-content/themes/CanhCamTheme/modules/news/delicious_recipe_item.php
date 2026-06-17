<?php
$idPost = $args['idPost'];
$isShowCategory = isset($args['showCategory']) ? $args['showCategory'] : false;

$title = get_the_title($idPost);
$content = get_post($idPost)->post_content;
?>

<div class="delicious-recipe-item">
	<div class="image img-cover img-zoom">
		<a class="" href="<?= get_the_permalink($idPost) ?>" title="<?php echo $title ?>">
			<img class="lozad" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?= get_the_post_thumbnail_url($idPost) ?>" alt="<?= $title ?>">
		</a>
	</div>
	<div class="caption p-4">
		<?php if($isShowCategory) : ?>
			<p class="category">
				<?php
				$categories = get_the_category($idPost);
				if ($categories) {
					echo $categories[0]->name;
				}
				?>
			</p>
		<?php endif; ?>
		<h3 class="sub-header-24 font-normal text-white text-center">
			<?= edit_link_post($idPost) ?>
			<a href="<?= get_the_permalink($idPost) ?>" title="<?php echo $title ?>">
				<?php echo $title ?>
			</a>
		</h3>
	</div>
</div>
