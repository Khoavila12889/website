<?php
$parent_cat_args = array(
	'hide_empty' => false,
	'parent' => $args['id'],
);
$parent_category = get_terms($args['taxonomy'], $parent_cat_args);
?>
<section class="section-tab-bar">
	<div class="container">
		<ul class="flex overflow-auto">
			<?php if ($parent_category) : ?>
				<?php foreach ($parent_category as $item) : ?>
					<li class="first:ml-auto last:mr-auto <?php echo add_class_active_tab($item->term_id) ?> uppercase">
						<a href="<?php echo get_term_link($item->term_id) ?>" class="flex whitespace-nowrap uppercase font-bold p-4 text-sm">
							<?php echo $item->name ?>
						</a>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
	</div>
</section>