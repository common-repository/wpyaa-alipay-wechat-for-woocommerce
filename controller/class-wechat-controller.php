<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/14 16:21
 */
defined( 'ABSPATH' ) || exit;
class Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat_Controller extends Wpyaa_Alipay_Wechat_For_WooCommerce_Controller
{
    protected $namespace = 'wpyaa/woocommerce/alipay-wechat/v1';
    protected $rest_base = 'wechat';

    public function register_routes(){
        register_rest_route($this->namespace, "/{$this->rest_base}/index", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'index')
            )
        ));

        register_rest_route($this->namespace, "/{$this->rest_base}/qrcode", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'qrcode')
            )
        ));

        register_rest_route($this->namespace, "/{$this->rest_base}/jsapi", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'jsapi')
            )
        ));

        register_rest_route($this->namespace, "/{$this->rest_base}/h5", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'h5')
            )
        ));


        register_rest_route($this->namespace, "/{$this->rest_base}/back", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'back')
            )
        ));

        register_rest_route($this->namespace, "/{$this->rest_base}/fail", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'fail')
            )
        ));

        register_rest_route($this->namespace, "/{$this->rest_base}/notify", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'notify')
            )
        ));

        register_rest_route($this->namespace, "/{$this->rest_base}/query", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'query')
            )
        ));
    }

    /**
     * 微信支付页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function index($request){
        $orderId = sanitize_text_field($request->get_param('id'));
        $order = wc_get_order($orderId);
        if(!$order){
            wp_die(__("订单信息错误！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        if(wpyaa_ensure_woocommerce_order_is_paid($order)){
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response($order->get_checkout_order_received_url());
        }

        if(!$order->needs_payment()){
            wp_die(__("订单金额或其他原因，无法进行支付操作！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        return new Wpyaa_Alipay_Wechat_For_WooCommerce_View_Response('wechat/index.php',[
            'order'=>[
                'id'=>$order->get_id(),
                'qrcode_url'=> add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/qrcode")),
                'jsapi_url'=> add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/jsapi")),
                'h5_url'=> add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/h5"))
            ]
        ]);
    }

    /**
     * 微信支付页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function qrcode($request){
        $orderId = sanitize_text_field($request->get_param('id'));
        $order = wc_get_order($orderId);
        if(!$order){
            wp_die(__("订单信息错误！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        if(wpyaa_ensure_woocommerce_order_is_paid($order)){
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response($order->get_checkout_order_received_url());
        }

        if(!$order->needs_payment()){
            wp_die(__("订单金额或其他原因，无法进行支付操作！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        $instance = Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::instance();

        $startTime = date_i18n('YmdHis' );
        $expiredTime = date('YmdHis',current_time( 'timestamp', false )+60*60);
        $args = array(
            'body'=>self::getOrderTitle($order),
            'total_fee'=>round($order->get_total()*100,0),
            'out_trade_no'=>date_i18n('YmdHis').$order->get_id(),
            'time_start'=>$startTime,
            'time_expire'=>$expiredTime,
            'notify_url'=>rest_url('/wpyaa/woocommerce/alipay-wechat/v1/wechat/notify'),
            'trade_type'=> 'NATIVE',
            'appid'=>$instance->get_option('app_id'),
            'mch_id'=>$instance->get_option('mch_id'),
            'spbill_create_ip'=>self::getClientIP(),
            'nonce_str'=>str_shuffle(time()),
            'product_id'=>$order->get_id()
        );
        $args['sign'] =Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::sign($args);
        try{
            $response =  wp_remote_post('https://api.mch.weixin.qq.com/pay/unifiedorder',array(
                'body' =>  Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::arrayToXml($args)
            ));

            if(is_wp_error($response)){
                throw new Exception($response->get_error_message());
            }

            $response = Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::xmlToArray(wp_remote_retrieve_body($response));
            if($response['return_code']!=='SUCCESS'){
                throw new Exception("code:{$response['return_code']},msg:{$response['return_msg']}");
            }
            if($response['result_code']!=='SUCCESS'){
                throw new Exception("code:{$response['err_code']},msg:{$response['err_code_des']}");
            }
        }catch (Exception $e){
            wc_get_logger()->error(__('微信支付创建订单失败：',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage());
            wp_die(__('微信支付创建订单失败：',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage(),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        return new Wpyaa_Alipay_Wechat_For_WooCommerce_View_Response('wechat/qrcode.php',[
            'order'=>[
                'id'=>$order->get_id(),
                'pay_url'=>$response["code_url"],
                'query_url'=>  add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/query")),
                'title'=>self::getOrderTitle($order),
                'amount'=>get_woocommerce_currency_symbol(). round($order->get_total(),2)
            ]
        ]);
    }

    /**
     * 微信支付页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function jsapi($request){
        $orderId = sanitize_text_field($request->get_param('id'));
        $order = wc_get_order($orderId);
        if(!$order){
            wp_die(__("订单信息错误！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        if(wpyaa_ensure_woocommerce_order_is_paid($order)){
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response($order->get_checkout_order_received_url());
        }

        if(!$order->needs_payment()){
            wp_die(__("订单金额或其他原因，无法进行支付操作！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        try{
            $openid = self::getOpenid($request);
            if($openid instanceof WP_REST_Response){
                return $openid;
            }

            $instance = Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::instance();
            $startTime = date_i18n('YmdHis' );
            $expiredTime = date('YmdHis',current_time( 'timestamp', false )+60*60);
            $args = array(
                'body'=>self::getOrderTitle($order),
                'total_fee'=>round($order->get_total()*100,0),
                'out_trade_no'=>date_i18n('YmdHis').$order->get_id(),
                'time_start'=>$startTime,
                'time_expire'=>$expiredTime,
                'notify_url'=>rest_url('/wpyaa/woocommerce/alipay-wechat/v1/wechat/notify'),
                'trade_type'=> 'JSAPI',
                'appid'=>$instance->get_option('app_id'),
                'mch_id'=>$instance->get_option('mch_id'),
                'spbill_create_ip'=>self::getClientIP(),
                'nonce_str'=>str_shuffle(time()),
                'openid'=>$openid,
            );
            $args['sign'] =Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::sign($args);
            $response =  wp_remote_post('https://api.mch.weixin.qq.com/pay/unifiedorder',array(
                'body' =>  Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::arrayToXml($args)
            ));

            if(is_wp_error($response)){
                throw new Exception($response->get_error_message());
            }

            $response = Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::xmlToArray(wp_remote_retrieve_body($response));
            if($response['return_code']!=='SUCCESS'){
                throw new Exception("code:{$response['return_code']},msg:{$response['return_msg']}");
            }
            if($response['result_code']!=='SUCCESS'){
                throw new Exception("code:{$response['err_code']},msg:{$response['err_code_des']}");
            }

            $timeStamp = time();
            $args = array(
                'appId'=>$instance->get_option('app_id'),
                'timeStamp'=>"{$timeStamp}",
                'nonceStr'=>str_shuffle(time()),
                'package'=>"prepay_id={$response['prepay_id']}",
                'signType'=>'MD5'
            );

            ksort($args);
            reset($args);
            $buffer ="";
            foreach ($args as $key=>$val){
                if($buffer){$buffer.="&"; }
                $buffer.="{$key}={$val}";
            }

            $args['paySign']=strtoupper(md5($buffer . "&key=".$instance->get_option('mch_key')));

            return new Wpyaa_Alipay_Wechat_For_WooCommerce_View_Response('wechat/jsapi.php',[
                'order'=>[
                    'id'=>$order->get_id(),
                    'pay_url'=>json_encode($args),
                    'query_url'=>  add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/query")),
                    'success_url'=>$order->get_checkout_order_received_url(),
                    'title'=>self::getOrderTitle($order),
                    'amount'=>get_woocommerce_currency_symbol(). round($order->get_total(),2)
                ]
            ]);
        }catch (Exception $e){
            wc_get_logger()->error(__('微信支付创建订单失败：',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage());
            wp_die(__('微信支付创建订单失败：',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage(),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }


    }

    /**
     * 微信支付页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function h5($request){
        $orderId = sanitize_text_field($request->get_param('id'));
        $order = wc_get_order($orderId);
        if(!$order){
            wp_die(__("订单信息错误！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        if(wpyaa_ensure_woocommerce_order_is_paid($order)){
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response($order->get_checkout_order_received_url());
        }

        if(!$order->needs_payment()){
            wp_die(__("订单金额或其他原因，无法进行支付操作！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        $instance = Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::instance();
        if($instance->get_option('h5')!=='yes'){
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/jsapi")));
        }

        $startTime = date_i18n('YmdHis' );
        $expiredTime = date('YmdHis',current_time( 'timestamp', false )+60*60);
        $args = array(
            'body'=>self::getOrderTitle($order),
            'total_fee'=>round($order->get_total()*100,0),
            'out_trade_no'=>date_i18n('YmdHis').$order->get_id(),
            'time_start'=>$startTime,
            'time_expire'=>$expiredTime,
            'notify_url'=>rest_url('/wpyaa/woocommerce/alipay-wechat/v1/wechat/notify'),
            'trade_type'=> 'MWEB',
            'appid'=>$instance->get_option('app_id'),
            'mch_id'=>$instance->get_option('mch_id'),
            'spbill_create_ip'=>self::getClientIP(),
            'nonce_str'=>str_shuffle(time()),
            'product_id'=>$order->get_id(),
            'scene_info'=>'{"h5_info": {"type":"Wap","wap_url":"'.home_url().'" ,"wap_name": "'.get_bloginfo('name').'"}}'
        );
        $args['sign'] =Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::sign($args);
        try{
            $response =  wp_remote_post('https://api.mch.weixin.qq.com/pay/unifiedorder',array(
                'body' =>  Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::arrayToXml($args)
            ));

            if(is_wp_error($response)){
                throw new Exception($response->get_error_message());
            }

            $response = Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::xmlToArray(wp_remote_retrieve_body($response));
            if($response['return_code']!=='SUCCESS'){
                throw new Exception("code:{$response['return_code']},msg:{$response['return_msg']}");
            }
            if($response['result_code']!=='SUCCESS'){
                throw new Exception("code:{$response['err_code']},msg:{$response['err_code_des']}");
            }

            return new Wpyaa_Alipay_Wechat_For_WooCommerce_View_Response('wechat/h5.php',[
                'order'=>[
                    'id'=>$order->get_id(),
                    'pay_url'=>$response["mweb_url"].'&redirect_url='.urlencode(add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/back"))),
                    'query_url'=>  add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/query")),
                    'title'=>self::getOrderTitle($order),
                    'amount'=>get_woocommerce_currency_symbol(). round($order->get_total(),2)
                ]
            ]);
        }catch (Exception $e){
            wc_get_logger()->error(__('微信支付创建订单失败：',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage());
            wp_die(__('微信支付创建订单失败：',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage(),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }
    }

    /**
     * 微信支付页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function query($request){
        $orderId = sanitize_text_field($request->get_param('id'));
        $order = wc_get_order($orderId);
        if(!$order){
            return new WP_REST_Response([
                'errcode'=>404
            ]);
        }

        if(wpyaa_ensure_woocommerce_order_is_paid($order)){
            return new WP_REST_Response([
                'errcode'=>0,
                'url'=>$order->get_checkout_order_received_url()
            ]);
        }

        return new WP_REST_Response([
            'errcode'=>404
        ]);
    }

    /**
     * 微信支付页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function back($request){
        $orderId = sanitize_text_field($request->get_param('id'));
        $order = wc_get_order($orderId);
        if(!$order){
            wp_die(__("订单信息错误！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        if(wpyaa_ensure_woocommerce_order_is_paid($order)){
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response($order->get_checkout_order_received_url());
        }

        return new Wpyaa_Alipay_Wechat_For_WooCommerce_View_Response('wechat/back.php',[
            'order'=>[
                'id'=>$order->get_id(),
                'query_url'=>  add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/query")),
                'fail_url'=>  add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/wechat/fail")),
                'title'=>self::getOrderTitle($order),
                'amount'=>get_woocommerce_currency_symbol(). round($order->get_total(),2)
            ]
        ]);
    }

    /**
     * 微信支付页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function fail($request){
        $orderId = sanitize_text_field($request->get_param('id'));
        $order = wc_get_order($orderId);
        if(!$order){
            wp_die(__("订单信息错误！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        if(wpyaa_ensure_woocommerce_order_is_paid($order)){
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response($order->get_checkout_order_received_url());
        }

        wp_die(__("订单支付确认失败，如有疑问，请咨询网站管理员！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
        return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
    }

    /**
     * 微信支付页面
     * @return WP_REST_Response
     */
    public function notify(){
        //get the data (xml),
        // validate all params at line:465
        $xml =isset($GLOBALS['HTTP_RAW_POST_DATA'])?$GLOBALS['HTTP_RAW_POST_DATA']:'';
        if(empty($xml)){
            $xml = file_get_contents("php://input");
        }

        if(empty($xml)){
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('<xml>
                          <return_code><![CDATA[FAIL]]></return_code>
                          <return_msg><![CDATA[FAIL]]></return_msg>
                        </xml>');
        }

        $request = Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::xmlToArray($xml) ;
        foreach ($request as $k=>$v){
            //keep the request data is safe
            $request[$k] = sanitize_text_field($v);
        }

        $sign =isset($request['sign'])?$request['sign']:null;
        if($sign!==Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::sign($request)){
            wc_get_logger()->error(__('微信支付回调：签名验证失败',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).print_r($request,true));
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('<xml>
                          <return_code><![CDATA[FAIL]]></return_code>
                          <return_msg><![CDATA[INVALID SIGN]]></return_msg>
                        </xml>');
        }

        $out_trade_no = isset($request['out_trade_no'])?$request['out_trade_no']:null;
        $transaction_id = isset($request['transaction_id'])?$request['transaction_id']:null;

        $wc_order_id = substr($out_trade_no,14);
        $order = wc_get_order($wc_order_id);
        if(!$order){
            wc_get_logger()->error(__('微信支付回调：订单信息异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).print_r($request,true));
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('<xml>
                          <return_code><![CDATA[FAIL]]></return_code>
                          <return_msg><![CDATA[INVALID DATA]]></return_msg>
                        </xml>');
        }

        try {
            if($request['return_code']!=='SUCCESS'){
                wc_get_logger()->error(__('微信支付回调：订单信息异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).print_r($request,true));
                return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('<xml>
                          <return_code><![CDATA[SUCCESS]]></return_code>
                          <return_msg><![CDATA[OK]]></return_msg>
                        </xml>');
            }

            //此字段标识支付成功
            if($request['result_code']!=='SUCCESS'){
                wc_get_logger()->error(__('微信支付回调：订单信息异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).print_r($request,true));
                return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('<xml>
                          <return_code><![CDATA[SUCCESS]]></return_code>
                          <return_msg><![CDATA[OK]]></return_msg>
                        </xml>');
            }

            //默认表示微信支付成功
            $order->payment_complete($transaction_id);
        } catch (Exception $e) {
            wc_get_logger()->error(__('微信支付回调：系统异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage().print_r($request,true));
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('<xml>
                          <return_code><![CDATA[FAIL]]></return_code>
                          <return_msg><![CDATA['.$e->getMessage().']]></return_msg>
                        </xml>');
        }

        return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('<xml>
                          <return_code><![CDATA[SUCCESS]]></return_code>
                          <return_msg><![CDATA[OK]]></return_msg>
                        </xml>');
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @throws Exception
     */
    public static function getOpenid($request){
        $instance = Wpyaa_Alipay_Wechat_For_WooCommerce_Wechat::instance();
        $code = $request->get_param('code');
        if (!$code){
            $args = array();
            $args["appid"] = $instance->get_option('app_id');
            $protocol = (! empty ( self::server('HTTPS') ) && self::server('HTTPS') !== 'off' || self::server('SERVER_PORT') == 443) ? "https://" : "http://";
            $args["redirect_uri"] = $protocol.self::server('HTTP_HOST').self::server('REQUEST_URI');
            $args["response_type"] = "code";
            $args["scope"] = "snsapi_base";
            $args["state"] = str_shuffle(time());
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response("https://open.weixin.qq.com/connect/oauth2/authorize?".http_build_query($args)."#wechat_redirect");
        }

        $args = array();
        $args["appid"] = $instance->get_option('app_id');
        $args["secret"] = $instance->get_option('app_secret');
        $args["code"] = $code;
        $args["grant_type"] = "authorization_code";

        $response =  wp_remote_get("https://api.weixin.qq.com/sns/oauth2/access_token?".http_build_query($args));
        if(is_wp_error($response)){
            throw new Exception($response->get_error_message());
        }

        $response = json_decode(wp_remote_retrieve_body($response),true);
        if(isset($response['errcode'])&&$response['errcode']!=0){
            throw new Exception("errcode:{$response['errcode']},errmsg:{$response['errmsg']}");
        }

        return $response['openid'];
    }
}