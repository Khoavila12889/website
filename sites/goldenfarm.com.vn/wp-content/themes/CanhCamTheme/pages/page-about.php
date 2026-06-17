<?php
/*
Template Name: Page - About
*/
?>

<?php get_header() ?>

<?php
$about_1_title = get_field('about_1_title', get_the_ID());
$about_2_title = get_field('about_2_title', get_the_ID());
$about_3_title = get_field('about_3_title', get_the_ID());
$about_4_title = get_field('about_4_title', get_the_ID());
?>

<section class="about-sticky bg-neutral-900">
	<div class="container">
		<ul id="menu-spy">
			<?php foreach ([$about_1_title, $about_2_title, $about_3_title, $about_4_title] as $key => $value) {
				// Remove accents from the title
				$title_without_accents = remove_accents($value);
				// Print the value with a specific format, for example, lowercase and replacing spaces with hyphens
				$formatted_title = strtolower(str_replace(' ', '-', $title_without_accents));
				echo '<li><a href="#' . $formatted_title . '">' . $value . '</a></li>';
			}
			?>
		</ul>
	</div>
</section>

<?php get_template_part('modules/about/about-1') ?>
<?php get_template_part('modules/about/about-2') ?>
<?php get_template_part('modules/about/about-3') ?>
<?php get_template_part('modules/about/about-4') ?>

<?php get_footer() ?>
