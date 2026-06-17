$(document).ready(function () {
	clickReplyComment();
	var language = {
		en: {
			author: {
				required: 'Please fill your name',
			},
			comment: {
				required: 'Please fill the comment',
			},
			email: {
				required: 'Email is required!',
				email: 'Wrong email format. Ex: yourEmail@gmail.com',
			},
		},
		vn: {
			author: {
				required: 'Bắt buộc nhập họ tên',
			},
			comment: {
				required: 'Bắt buộc nhập bình luận',
			},
			email: {
				required: 'Bắt buộc nhập email',
				email: 'Hãy nhập email đúng định dạng. Ví dụ emailCuaBan@gmail.com',
			},
		},
	};
	const currentLanguageActive =
		$('html').attr('lang') === 'en-US' ? 'en' : 'vn';

	$('#commentform').validate({
		rules: {
			author: {
				required: true,
			},
			comment: {
				required: true,
			},
			email: {
				required: true,
				email: true,
			},
		},
		messages: {
			author: {
				required: language[currentLanguageActive].author.required,
			},
			comment: {
				required: language[currentLanguageActive].comment.required,
			},
			email: {
				required: language[currentLanguageActive].email.required,
				email: language[currentLanguageActive].email.email,
			},
		},
	});
});
function clickReplyComment() {
	$('.comment-reply-link').on('click', function () {
		let idComment = $(this).data('commentid');
		$('.form-submit #comment_parent').val(idComment);
		$('html, body').animate(
			{
				scrollTop: $('#commentform').offset().top,
			},
			'slow'
		);
		$('#comment').focus();
	});
}
