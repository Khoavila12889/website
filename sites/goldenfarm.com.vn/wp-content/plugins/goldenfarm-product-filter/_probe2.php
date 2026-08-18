<?php
defined( 'ABSPATH' ) || exit;
$cat = get_term_by( 'slug', 'golden-farm', 'product_cat' );
var_dump( $cat ? $cat->term_id : null );
$children = GF_PF_Terms::get_children( $cat ? $cat->term_id : 0, 'product_cat' );
echo 'CHILDREN:' . count( $children ) . "\n";
foreach ( $children as $c ) { echo $c->slug . "\n"; }
$tree = GF_PF_Terms::get_brand_tree();
echo 'BRANDS:' . count( $tree ) . "\n";
foreach ( $tree as $item ) { echo $item['brand']->slug . ' => ' . count( $item['categories'] ) . "\n"; }