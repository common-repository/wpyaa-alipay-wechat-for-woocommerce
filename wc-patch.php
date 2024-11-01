<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/14 17:14
 */
defined( 'ABSPATH' ) || exit;

if(!function_exists('wpyaa_ensure_woocommerce_order_is_paid')){
    /**
     * @param WC_Order $order
     * @return bool
     */
    function wpyaa_ensure_woocommerce_order_is_paid($order){
        if(method_exists($order,'is_paid')){
            return $order->is_paid();
        }

        return apply_filters( 'woocommerce_order_is_paid', $order->has_status(apply_filters( 'woocommerce_order_is_paid_statuses', array( 'processing', 'completed' ) )), $order );
    }
}

/**
 * wc_get_checkout_url
 */
if(!function_exists('wpyaa_ensure_woocommerce_wc_get_checkout_url')){
    function wpyaa_ensure_woocommerce_wc_get_checkout_url(){
        if(function_exists('wc_get_checkout_url')){
            return wc_get_checkout_url();
        }

        $checkout_url = wc_get_page_permalink( 'checkout' );
        if ( $checkout_url ) {
            // Force SSL if needed.
            if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
                $checkout_url = str_replace( 'http:', 'https:', $checkout_url );
            }
        }

        return apply_filters( 'woocommerce_get_checkout_url', $checkout_url );
    }
}

/**
 * wc_get_cart_url
 */
if(!function_exists('wpyaa_ensure_woocommerce_wc_get_cart_url')){
    function wpyaa_ensure_woocommerce_wc_get_cart_url(){
        if(function_exists('wc_get_cart_url')){
            return wc_get_cart_url();
        }

        return apply_filters( 'woocommerce_get_cart_url', wc_get_page_permalink( 'cart' ) );
    }
}