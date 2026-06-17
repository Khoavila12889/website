<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package CanhCam
 */
?>

<?php get_header() ?>

<?php
$category = get_the_category();
$categoryID = $category[0]->cat_ID;
$categoryTaxonomy = $category[0]->taxonomy;
$choose_template = get_field('choose_template', $categoryTaxonomy . '_' . $categoryID);
get_template_part('modules/news/single_' . $choose_template['value'] . '');
?>

<?php get_footer() ?>
