<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/14 17:02
 */
defined( 'ABSPATH' ) || exit;
class Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response extends WP_REST_Response{
    public function __construct($url = null,$status = 302){
        parent::__construct($url, $status, []);

        add_filter( 'rest_pre_serve_request',array($this,'rest_pre_serve_request'),10,4);
    }

    /**
     * @param boolean $false
     * @param WP_REST_Response $result
     * @param WP_REST_Request $request
     * @param WP_REST_Server $server
     * @return bool
     */
    public function rest_pre_serve_request($false, $result, $request, $server){
        if($result instanceof Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response){
            wp_redirect($result->get_data(),$result->get_status());
            return true;
        }

        return $false;
    }
}