/**
 * GoldenFarm Product Filter — Mobile Off-Canvas Drawer.
 *
 * Slides the filter into an off-canvas sidebar on small screens, locks the
 * page scroll while open, and closes on backdrop click or the close button.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var filterSidebar = document.getElementById('gf-pf-filters');

		// Nút icon lọc trên Header (nếu có) hoặc nút plugin tự render.
		var mobileToggleBtn = document.querySelector('.gf-pf-mobile-toggle, .filter-toggle-icon');

		if (!filterSidebar) {
			return;
		}

		// Tự động tạo Overlay nếu chưa có trong DOM
		var overlay = document.querySelector('.gf-pf-overlay');
		if (!overlay) {
			overlay = document.createElement('div');
			overlay.className = 'gf-pf-overlay';
			document.body.appendChild(overlay);
		}

		function openSidebar() {
			filterSidebar.classList.add('is-open');
			overlay.classList.add('is-open');
			document.body.classList.add('gf-pf-filter-open'); // Chống cuộn trang web bên dưới

			if (mobileToggleBtn) {
				mobileToggleBtn.setAttribute('aria-expanded', 'true');
			}
		}

		function closeSidebar() {
			filterSidebar.classList.remove('is-open');
			overlay.classList.remove('is-open');
			document.body.classList.remove('gf-pf-filter-open');

			if (mobileToggleBtn) {
				mobileToggleBtn.setAttribute('aria-expanded', 'false');
			}
		}

		// Bắt sự kiện bấm mở Sidebar
		if (mobileToggleBtn) {
			mobileToggleBtn.addEventListener('click', function (e) {
				e.preventDefault();
				openSidebar();
			});
		}

		// Bắt sự kiện bấm vào backdrop hoặc nút X để đóng
		overlay.addEventListener('click', closeSidebar);

		document.addEventListener('click', function (e) {
			if (e.target.closest('.gf-pf-close-btn')) {
				closeSidebar();
			}
		});

		// Đóng bằng phím Escape
		document.addEventListener('keyup', function (e) {
			if (27 === e.keyCode) {
				closeSidebar();
			}
		});
	});
})();