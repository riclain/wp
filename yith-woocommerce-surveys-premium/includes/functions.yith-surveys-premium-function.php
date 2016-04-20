<?php
if( !defined( 'ABSPATH' ) )
exit;


if( ! function_exists( 'yith_wpml_get_translated_id' ) ) {
    /**
     * Get the id of the current translation of the post/custom type
     *
     * @since  2.0.0
     * @author Andrea Frascaspata <andrea.frascaspata@yithemes.com>
     */
    function yith_wpml_get_translated_id( $id, $post_type ) {

        if ( function_exists( 'icl_object_id' ) ) {

            $id = icl_object_id( $id, $post_type, true );

        }

        return $id;
    }
}

if( !function_exists( 'yith_setcookie' ) ) {
    /**
     * Create a cookie.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     * @since 1.0.0
     */
    function yith_setcookie( $name, $value = array(), $time = null ) {
        $time = $time != null ? $time : time() + 60 * 60 * 24 * 30;

        //$value = maybe_serialize( stripslashes_deep( $value ) );
        $value = json_encode( stripslashes_deep( $value ) );
        $expiration = apply_filters( 'yith_wcwl_cookie_expiration_time', $time ); // Default 30 days

        $_COOKIE[ $name ] = $value;
        wc_setcookie( $name, $value, $expiration, false );
    }
}

if( !function_exists( 'yith_getcookie' ) ) {
    /**
     * Retrieve the value of a cookie.
     *
     * @param string $name
     * @return mixed
     * @since 1.0.0
     */
    function yith_getcookie( $name ) {
        if( isset( $_COOKIE[$name] ) ) {
            return json_decode( stripslashes( $_COOKIE[$name] ), true );
        }

        return array();
    }
}


if( !function_exists ( 'yith_destroycookie' ) ) {
    /**
     * Destroy a cookie.
     *
     * @param string $name
     * @return void
     * @since 1.0.0
     */
    function yith_destroycookie( $name ) {
        yith_setcookie( $name, array(), time() - 3600 );
    }
}

if( !function_exists( 'yith_woocommerce_get_orders' ) ){

    function yith_woocommerce_get_orders(){

        global $wpdb;

        $query =    "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id WHERE {$wpdb->posts}.post_type= %s
                     AND {$wpdb->posts}.post_status = %s AND {$wpdb->postmeta}.meta_key = %s AND {$wpdb->postmeta}.meta_value!=''";

        $query = $wpdb->prepare( $query, array( 'shop_order','wc-completed', '_yith_order_survey_voting' ) );

        $result = $wpdb->get_col( $query );

        return $result;
    }
}


if ( ! function_exists( 'yith_download_file' ) ) {

    /**
     * Download a file
     *
     * @param $filepath
     */
    function yith_download_file( $filepath ) {

        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename=' . $filepath );
        header( "Content-Type: text/csv; charset=" . get_option( 'blog_charset' ), true );
        header( 'Expires: 0' );
        header( 'Pragma: public' );

        readfile( $filepath );
        exit;
    }
}

if( !function_exists( 'get_surveys_type' ) ){

    function get_surveys_type(){

        $visible_option = array(
            'checkout' => __( 'WooCommerce Checkout','yith-woocommerce-surveys' ),
            'product' => __( 'WooCommerce Product','yith-woocommerce-surveys' ),
            'other_page' => __( 'Other Pages','yith-woocommerce-surveys' )
        );

        return apply_filters( 'yith_wc_surveys_types', $visible_option );
    }
}