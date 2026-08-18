<!DOCTYPE html>
<html <?php language_attributes() ?>>

	

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	 <!-- Thẻ canonical -->
    <?php if (is_home() || is_front_page()): ?>
        <link rel="canonical" href="<?php echo esc_url(home_url()); ?>" />
    <?php elseif (is_single() || is_page()): ?>
        <link rel="canonical" href="<?php echo esc_url(get_permalink()); ?>" />
    <?php elseif (is_category() || is_tag() || is_archive()): ?>
        <link rel="canonical" href="<?php $term_link = get_term_link(get_queried_object()); echo esc_url(is_wp_error($term_link) ? home_url() : $term_link); ?>" />
    <?php elseif (is_search()): ?>
        <meta name="robots" content="noindex, nofollow" />
    <?php endif; ?>

    <?php wp_head(); ?>

	<?php if (stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse') === false) : ?>

	<?php endif; ?>
	<!-- Style-->
	<?php wp_head(); ?>
	<!-- Script-->
	<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5SYQLB558V"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-5SYQLB558V');
</script>
</head>

<body <?php body_class(get_field('add_class_body', get_the_ID())) ?>>
	<header>
		<div class="header-wrap container flex items-center justify-between gap-5">
			<div class="header-left">
				<button id="buttonMenu" type="button" data-target="#toggleMenu" aria-controls="toggleMenu"><span class="line"></span><span class="line"></span><span class="line"></span><span id="pulseMe"><span class="bar left"></span><span class="bar top"></span><span class="bar right"></span><span class="bar bottom"></span></span></button>
				<nav class="navbar-nav">
					<?php
					wp_nav_menu(array(
						'theme_location' => 'header-left',
						'container' => false,
						'menu_class' => 'main-menu',
						'menu_id' => 'primary-menu-left',
						'walker' => new CleanMenuWalker()
					));
					?>
				</nav>
			</div>
			<div class="header-center">
				<div class="logo">
					<?php
					$header_logo = get_field('header_logo', 'options');
					if ($header_logo) :
						echo '<a href="' . get_home_url() . '"><img src="' . $header_logo['url'] . '" alt="' . $header_logo['alt'] . '"></a>';
					else :
						$custom_logo_id = get_theme_mod('custom_logo');
						$image = wp_get_attachment_image_src($custom_logo_id, 'full');
						echo '<a href="' . get_home_url() . '"><img src="' . $image[0] . '" alt="Logo"></a>';
					endif;
					?>
				</div>
			</div>
			<div class="header-right">
				<nav class="navbar-nav">
					<?php
					wp_nav_menu(array(
						'theme_location' => 'header-right',
						'container' => false,
						'menu_class' => 'main-menu',
						'menu_id' => 'primary-menu-right',
					));
					?>
				</nav>
				<div class="button-language">
					<div class="toggle-language notranslate">
						<span><?php echo _e('Vi/En', 'canhcamtheme') ?></span>
						<i class="fa-light fa-chevron-down"></i>
					</div>
					<div class="menu-language">
						<?php
						// do_action('wpml_add_language_selector');
						echo do_shortcode('[gtranslate]')
						?>
					</div>
				</div>
				<div class="button-search"><i class="fa-light fa-search"></i></div>
				<div class="button-phone">
					<?php
					$header_phone = get_field('header_phone', 'options');
					?>
					<?php if ($header_phone) : ?>
						<a class="btn-solid" href="tel: <?php echo $header_phone ?>">
							<i class="fa-light fa-phone"></i>
							<span><?php echo $header_phone ?></span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</header>
	<div class="mobile-wrap">
		<div class="navbar-nav-list"></div>
	</div>
	<div class="search-wrap">
		<form class="searchbox" method="GET" role="form" aria-label="<?php echo _e('Tìm kiếm', 'canhcamtheme') ?>" novalidate="novalidate" action="<?php bloginfo('url') ?>/">
			<input class="searchinput" type="text" placeholder="<?php echo _e('Tìm kiếm', 'canhcamtheme') ?>" name="s">
			<button class="searchbutton" type="submit"><i class="fa-light fa-magnifying-glass"></i></button>
		</form>
	</div>
	<main>
