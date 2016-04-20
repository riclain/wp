<?php
if( !defined( 'ABSPATH' ) )
exit;

if( !function_exists( 'ywcsur_is_premium_active') ) {
    /**
     * check if premium version is active
     * @author YIThemes
     * @since 1.0.0
     * @return bool
     */
    function ywcsur_is_premium_active(){

        return defined( 'YITH_WC_SURVEYS_PREMIUM' ) && YITH_WC_SURVEYS_PREMIUM;
    }
}

