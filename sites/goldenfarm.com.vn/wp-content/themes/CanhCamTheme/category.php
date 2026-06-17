<?php
/**
 * The template for displaying category pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package CanhCam
 */
?>

<?php get_header() ?>

<?php get_template_part('modules/common/banner') ?>

<?php
$terms = get_queried_object(); // get info taxonomy
$taxonomy = $terms->taxonomy; // get taxonomy name
$taxonomyID = $terms->term_taxonomy_id; // get ID taxonomy name
$taxonomyTitle = $terms->name; // get Title taxonomy name
$choose_template = get_field('choose_template', $taxonomy . '_' . $taxonomyID);
get_template_part('modules/news/' . $choose_template['value'] . '');
?>

<?php get_footer() ?>
