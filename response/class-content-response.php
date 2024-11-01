<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/14 17:02
 */
defined( 'ABSPATH' ) || exit;
class Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response extends WP_REST_Response{
    public function __construct($content, array $headers = array()){
        parent::__construct($content, 200, $headers);

        $this->header('content-type','text/plain');
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
        if($result instanceof Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response){
            $data = $result->get_data();
            echo $data;
            return true;
        }

        return $false;
    }
}