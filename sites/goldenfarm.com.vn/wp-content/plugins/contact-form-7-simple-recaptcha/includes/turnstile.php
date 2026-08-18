<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cf7sr_ts_key    = get_option( 'cf7sr_ts_key' );
$cf7sr_ts_secret = get_option( 'cf7sr_ts_secret' );

if ( empty( $cf7sr_ts_key ) || empty( $cf7sr_ts_secret ) || is_admin() ) {
    return;
}

function enqueue_cf7sr_turnstile_script() {
    global $cf7sr_turnstile_on;
    if ( ! $cf7sr_turnstile_on ) {
        return;
    }
    $cf7sr_script_url = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=cf7srLoadTurnstile';
    $cf7sr_ts_key = get_option( 'cf7sr_ts_key' );
    ?>
    <script type="text/javascript">
        var turnstileIds = [];

        var cf7srLoadTurnstile = function() {
            var widgets = document.querySelectorAll('.cf7sr-g-turnstile');
            for (var i = 0; i < widgets.length; ++i) {
                var widget = widgets[i];
                turnstileIds.push(
                    turnstile.render("#" + widget.id, {
                        'sitekey' : <?php echo wp_json_encode( $cf7sr_ts_key ); ?>
                    })
                );
            }
        };

        function cf7srResetTurnstile() {
            for (var i = 0; i < turnstileIds.length; i++) {
                turnstile.reset(turnstileIds[i]);
            }
        }

        document.querySelectorAll('.wpcf7').forEach(function(element) {
            element.addEventListener('wpcf7invalid', cf7srResetTurnstile);
            element.addEventListener('wpcf7mailsent', cf7srResetTurnstile);
            element.addEventListener('invalid.wpcf7', cf7srResetTurnstile);
            element.addEventListener('mailsent.wpcf7', cf7srResetTurnstile);
        });
    </script>
    <script src="<?php echo esc_url( $cf7sr_script_url ); ?>" async defer></script>
    <?php
}
add_action( 'wp_footer', 'enqueue_cf7sr_turnstile_script' );

function cf7sr_turnstile_wpcf7_form_elements( $form ) {
    $form = do_shortcode( $form );
    return $form;
}
add_filter( 'wpcf7_form_elements', 'cf7sr_turnstile_wpcf7_form_elements' );

function cf7sr_turnstile_shortcode( $atts ) {
    global $cf7sr_turnstile_on;
    $cf7sr_turnstile_on = true;
    $cf7sr_ts_key = get_option( 'cf7sr_ts_key' );
    $cf7sr_ts_language = get_option( 'cf7sr_ts_language' );
    if ( empty( $cf7sr_ts_language ) ) {
        $cf7sr_ts_language = 'auto';
    }
    $cf7sr_theme = ! empty( $atts['theme'] ) && in_array($atts['theme'], ['light', 'dark']) ? $atts['theme'] : 'auto';
    $cf7sr_size  = ! empty( $atts['size'] ) && 'compact' == $atts['size'] ? 'compact' : 'normal';

    $cf7sr_id       = 'cf7sr-' . uniqid();
    $cf7sr_redirect = ! empty( $atts['redirect'] ) && filter_var( $atts['redirect'], FILTER_VALIDATE_URL ) ? $atts['redirect'] : '';

    $cf7sr_redirect_html = ! empty( $cf7sr_redirect ) ?
        "<script>
                document.addEventListener( 'wpcf7mailsent', function( event ) {
                    var unitDiv = document.getElementById(event.detail.unitTag);
                    var captchaDiv = document.getElementById('" . $cf7sr_id . "');
                    if (unitDiv.contains(captchaDiv) && captchaDiv.hasAttribute('data-redirect')) {
                        location = captchaDiv.getAttribute('data-redirect');
                    }
                }, false );
            </script>" : '';

    $cf7sr_honeypot = isset( $atts['honeypot'] ) ? '<input type="text" name="cf7sr-g-hp" style="display:none;">' : '';

    return $cf7sr_redirect_html . $cf7sr_honeypot . '<div id="' . $cf7sr_id . '" class="cf7sr-g-turnstile" data-redirect="' . esc_attr( $cf7sr_redirect ) . '" data-theme="' . esc_attr( $cf7sr_theme ) . '" data-language="' . esc_attr( $cf7sr_ts_language ) . '" data-size="' . esc_attr( $cf7sr_size ) . '" 
    data-sitekey="' . esc_attr( $cf7sr_ts_key ) . '"></div><span class="wpcf7-form-control-wrap cf7sr-turnstile" data-name="cf7sr-turnstile"><input type="hidden" name="cf7sr-turnstile" value="" class="wpcf7-form-control"></span>';
}
add_shortcode( 'cf7sr-turnstile', 'cf7sr_turnstile_shortcode' );

function cf7sr_verify_turnstile( $result, $tags ) {
    if ( ! class_exists( 'WPCF7_Submission' ) ) {
        return $result;
    }

    $_wpcf7 = ! empty( $_POST['_wpcf7'] ) ? absint( $_POST['_wpcf7'] ) : 0;
    if ( empty( $_wpcf7 ) ) {
        return $result;
    }

    $submission = WPCF7_Submission::get_instance();
    $data       = $submission->get_posted_data();

    $cf7_text  = do_shortcode( '[contact-form-7 id="' . $_wpcf7 . '"]' );
    $cf7sr_ts_key = get_option( 'cf7sr_ts_key' );
    if ( false === strpos( $cf7_text, $cf7sr_ts_key ) ) {
        return $result;
    }

    $message = get_option( 'cf7sr_message' );
    if ( empty( $message ) ) {
        $message = 'Invalid captcha';
    }

    if (empty( $data['cf-turnstile-response'])) {
        $result->invalidate(
            array(
                'type' => 'captcha',
                'name' => 'cf7sr-turnstile',
            ),
            $message
        );
        return $result;
    }

    $cf7sr_ts_secret = get_option( 'cf7sr_ts_secret' );

    $headers = array(
        'body' => [
            'secret' => $cf7sr_ts_secret,
            'response' => $data['cf-turnstile-response']
        ]
    );
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $request = wp_remote_post( $url, $headers );
    $body = wp_remote_retrieve_body( $request );
    $response = json_decode($body);

    if ( ! ( isset( $response->success ) && 1 == $response->success ) ) {
        $result->invalidate(
            array(
                'type' => 'captcha',
                'name' => 'cf7sr-turnstile',
            ),
            $message
        );
    }

    return $result;
}
add_filter( 'wpcf7_validate', 'cf7sr_verify_turnstile', 30, 2 );
