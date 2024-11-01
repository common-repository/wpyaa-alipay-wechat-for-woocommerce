<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/14 17:02
 */
defined( 'ABSPATH' ) || exit;
class Wpyaa_Alipay_Wechat_For_WooCommerce_View_Response extends WP_REST_Response{
    public function __construct($template = null, $assigns = [], array $headers = array()){
        parent::__construct([
            'template'=>$template,
            'assigns'=>$assigns
        ], 200, $headers);

        $this->header('content-type','text/html');
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
        if($result instanceof Wpyaa_Alipay_Wechat_For_WooCommerce_View_Response){
            $data = $result->get_data();
            $template = ltrim($data['template'],'/');
            $assigns = $data['assigns'];
            extract($assigns,EXTR_OVERWRITE);
            $themeTemplate = get_template_directory().'/'.current((explode('/', plugin_basename(WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCE_FILE)))).'/'.$template;
            if(file_exists($themeTemplate)){
                require $themeTemplate;
            }else{
                require plugin_dir_path(WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCE_FILE).'templates/'.$template;
            }
            return true;
        }

        return $false;
    }
}