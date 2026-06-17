<!-- Include file comments by comments_template() -->
<div id="comments" class="comments-area">
	<?php if (have_comments()) : ?>
		<h2 class="comments-title">
			Bình luận bài viết
			(<?php echo get_comments_number(get_the_ID()) ?>)
		</h2>
		<ul class="comment-list comments">
			<?php
			wp_list_comments(array(
				'style'      => 'ul',
				'max_depth'         => '2',
				'short_ping' => true,
				'callback' => 'better_commets'
			));
			?>
		</ul><!-- .comment-list -->
		<?php
		// Are there comments to navigate through?
		?>
		<?php if (!comments_open() && get_comments_number()) : ?>
			<p class="no-comments"><?php echo 'Không có bình luận' ?></p>
		<?php endif; ?>
	<?php endif; // have_comments()
	?>
	<?php $comments_args = array(
		'fields' => array(
			'author' => '<div class="form-group"><input required="required" id="author" name="author" aria-required="true" placeholder="Nhập họ tên của bạn"></input></div>',
			'email' => '<div class="form-group"><input required="required" id="email" name="email" placeholder="Nhập email của bạn"></input></div>',
			'url' => '',
			'cookies' => '',
		),
		'comment_field' => '<div class="form-group"><textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required" placeholder="Nhập bình luận của bạn tại đây"></textarea></div>',
		'label_submit' => __('Gửi bình luận'),
		'title_reply' => __('Bình luận tại đây'),
		'title_reply_to' => 'Bình luận',
		'comment_notes_after' => '',
		'comment_notes_before' => '',
		'cancel_reply_link' => 'Hủy bình luận',
	); ?>
	<?php comment_form($comments_args); ?>
</div><!-- #comments -->


<?php
add_action('wp_footer', 'commentScriptValidate');

function commentScriptValidate()
{
    /**
     * Styles
     */
    wp_enqueue_script('validate-js-cdn', 'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js', '', '', true);
    wp_enqueue_script('comment-script-validate', THEME_URI . '/scripts/comment.js', '', '', true);
}; ?>