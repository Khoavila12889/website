<?php
defined( 'ABSPATH' ) || exit;
$html = do_shortcode( '[goldenfarm_product_filter]' );
echo 'LEN:' . strlen( $html ) . "\n";
echo $html;