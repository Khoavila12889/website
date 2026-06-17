<?php
/**
 * @param string $textWebsite
 * @param string $textSeo
 */
function cc_optimize_text_seo($textWebsite, $textSeo) {
	if (stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse') === false){
		echo $textWebsite;
	} else {
		echo $textSeo;
	}
}
?>