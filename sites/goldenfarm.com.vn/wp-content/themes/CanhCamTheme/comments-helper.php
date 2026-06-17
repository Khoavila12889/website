<?php
if (!function_exists('better_commets')) :
	function better_commets($comment, $args, $depth)
	{
?>
		<li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
			<div class="comment-item">
				<div class="img-thumbnail d-none d-sm-block">
					<?php echo get_avatar($comment, $size = '80', $default = ''); ?>
				</div>
				<div class="comment-block">
					<div class="comment-arrow"></div>
					<?php if ($comment->comment_approved == '0') : ?>
						<em>
							Bình luận của bạn đang chờ duyệt
						</em>
						<br />
					<?php endif; ?>
					<span class="comment-by">
						<strong><?php echo get_comment_author() ?></strong>
						<span class="comment-reply">
							<a href="#"><?php comment_reply_link(array_merge($args, array('reply_text' => ('Trả lời'), 'depth' => $depth, 'max_depth' => $args['max_depth']))) ?></a>
						</span>
					</span>
					<p> <?php comment_text() ?></p>
					<span class="date float-right">
						<?php echo get_comment_date('d\/m\/Y') ?></span>
				</div>
			</div>

	<?php
	}
endif;
