		<div class="backdrop"></div>
		<div class="cta-fixed">
			<ul>
				<li class="back-to-top"><span class="cta"><span class="icon"><span class="fa-light fa-arrow-up-to-line"></span></span><span>Top</span></span></li>
				<?php
				$header_phone = get_field('header_phone', 'options');
				if ($header_phone) :
					echo '<li class="phone"><a class="cta" href="tel: ' . $header_phone . '"><span class="icon"><span class="fa-thin fa-phone"></span></span></a></li>';
				endif;
				?>
				<?php
				$cta_list = get_field('cta_list', 'options');
				if ($cta_list) :
					foreach ($cta_list as $cta) :
						echo '<li><a class="cta" href="' . $cta['url'] . '"><span class="icon">' . $cta['icon'] . '</span><span>' . $cta['title'] . '</span></a></li>';
					endforeach;
				endif;
				?>
			</ul>
		</div>
		</main>
		<footer class="border-t-5 border-black bg-neutral-footer pt-10">
			<div class="container">
				<div class="footer-logo">
					<?php
					$footer_logo = get_field('footer_logo', 'options');
					if ($footer_logo) :
						echo '<a href="' . get_home_url() . '"><img src="' . $footer_logo['url'] . '" alt="' . $footer_logo['alt'] . '"></a>';
					endif;
					?>
				</div>
				<div class="footer-mid pt-6 pb-10 xl:pb-16">
					<?php $company_info = get_field('company_info', 'options'); ?>
					<div class="row -mt-8">
						<div class="col w-full mt-8 lg:w-1/3">
							<div class="footer-info">
								<?php if ($company_info) : ?>
									<h4 class="notranslate">
										<?php echo $company_info['company_name'] ?>
									</h4>
									<?php foreach ($company_info['company_location'] as $item) : ?>
										<h5><?php echo $item['title'] ?></h5>
										<?php echo $item['detail'] ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
						<div class="col w-full mt-8 lg:w-1/3">
							<div class="footer-maps space-y-4">
								<?php if ($company_info) : ?>
									<h4>
										<?php echo $company_info['company_maps_title'] ?>
									</h4>
									<div class="iframe-scale">
										<?php echo $company_info['company_maps'] ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="col w-full mt-8 lg:w-1/3">
							<div class="footer-newsletter">
								<?php
								$footer_newsletter = get_field('footer_newsletter', 'options');
								if ($footer_newsletter) :
									echo do_shortcode($footer_newsletter, true);
								endif;
								?>
							</div>
							<div class="social-list mt-8">
								<?php
								$site_social_list = get_field('site_social_list', 'options');
								if ($site_social_list) :
									echo '<ul>';
									foreach ($site_social_list as $item) :
										echo '<li><a href="' . $item['url'] . '" title="' . $item['name'] . '" target="_blank" rel="nofollow">' . $item['icon'] . '</a></li>';
									endforeach;
									echo '</ul>';
								endif;
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="footer-bot py-5 border-t border-white/15">
					<div class="flex flex-wrap gap-3 items-center justify-between">
						<div class="footer-copyright body-14 font-normal text-neutral-200 opacity-60">
							<?php
							$footer_copyright = get_field('footer_copyright', 'options');
							if ($footer_copyright) :
								echo $footer_copyright;
							endif;
							?>
						</div>
						<div class="footer-policy body-14 font-normal text-neutral-200 opacity-60">
							<?php
							$footer_policy = get_field('footer_policy', 'options');
							if ($footer_policy) :
								echo $footer_policy;
							endif;
							?>
						</div>
					</div>
				</div>
			</div>
		</footer>
		<?php wp_footer() ?>
		</body>

		</html>
