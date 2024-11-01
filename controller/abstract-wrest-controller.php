<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/14 17:49
 */
defined( 'ABSPATH' ) || exit;
abstract class Wpyaa_Alipay_Wechat_For_WooCommerce_Controller extends WP_REST_Controller{
    /**
     * 获取$_SERVER 相关信息
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function server($key,$default = null){
        return isset($_SERVER[$key])?sanitize_text_field($_SERVER[$key]):$default;
    }

    /**
     * 判断是否是移动端
     * @return bool
     */
    public static function isMiniWebClient(){
        if (self::server('HTTP_VIA') && stristr(self::server('HTTP_VIA'), "wap")) {
            return true;
        } elseif (self::server('HTTP_ACCEPT') && strpos(strtoupper(self::server('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        } elseif (self::server('HTTP_X_WAP_PROFILE') || self::server('HTTP_PROFILE')) {
            return true;
        } elseif (self::server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', self::server('HTTP_USER_AGENT'))) {
            return true;
        }

        return false;
    }

    /**
     * 判断是否是IOS
     * @return bool
     */
    public static function isIOSClient(){
        $ua = self::server('HTTP_USER_AGENT') ;
        return $ua&& (strripos($ua,'iphone')!==false||strripos($ua,'ipad')!==false);
    }
    /**
     * @param WC_Order $order
     * @return string
     */
    public static function getOrderTitle($order){
        $title="#{$order->get_id()}|";
        if(!function_exists('mb_strimwidth')){
            return apply_filters('wpyaa_alipay_wechat_for_woocommerce_order_title',$title,$order);
        }

        $order_items = $order->get_items();
        if($order_items){
            $index = 0;
            foreach ($order_items as $item_id =>$item){
                if($index++>0){
                    $title.='，';
                }
                $title.="{$item['name']}";
            }
        }

        return  apply_filters('wpyaa_alipay_wechat_for_woocommerce_order_title',mb_strimwidth($title,0,32,'...','utf-8'),$order);
    }

    public static function getClientIP(){
        $ip = getenv('HTTP_CLIENT_IP');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }

        $ip = getenv('HTTP_X_FORWARDED_FOR');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }

        $ip = getenv('REMOTE_ADDR');
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if ($ip && strcasecmp($ip, 'unknown')) {
            return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : null;
        }

        return null;
    }
}