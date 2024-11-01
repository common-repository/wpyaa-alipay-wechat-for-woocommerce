<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/14 16:21
 */
defined( 'ABSPATH' ) || exit;
class Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay_Controller extends Wpyaa_Alipay_Wechat_For_WooCommerce_Controller
{
    protected $namespace = 'wpyaa/woocommerce/alipay-wechat/v1';
    protected $rest_base = 'alipay';

    public function register_routes(){
        register_rest_route($this->namespace, "/{$this->rest_base}/index", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'index')
            )
        ));

        register_rest_route($this->namespace, "/{$this->rest_base}/back", array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'back')
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
     * 支付宝页面
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

        return new Wpyaa_Alipay_Wechat_For_WooCommerce_View_Response('alipay/index.php',[
            'order'=>[
                'id'=>$order->get_id(),
                'args'=>self::createOrder($order),
                'title'=>self::getOrderTitle($order),
                'query_url'=> add_query_arg(['id'=>$orderId ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/alipay/query")),
                'amount'=>get_woocommerce_currency_symbol(). round($order->get_total(),2)
            ]
        ]);
    }

    /**
     * 支付宝页面
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
     * 支付宝页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function back($request){
        //we must be get all query params ,then generate the sign(validate the all data is safe)
        // @see 114 line
        $requestData = $request->get_query_params() ;
        foreach ($requestData as $k=>$v){
            $requestData[$k] = sanitize_text_field($v);
        }

        //validate all query params is valid by rsa2
        if(!Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay::validateSign($requestData)){
            wc_get_logger()->error(__('支付宝回调：签名验证失败',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).print_r($requestData,true));
            wp_die(__("支付宝回调：签名信息异常！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        $out_trade_no = isset($requestData['out_trade_no'])?$requestData['out_trade_no']:null;
        $transaction_id = isset($requestData['trade_no'])?$requestData['trade_no']:null;
        $trade_status = isset($requestData['trade_status'])?$requestData['trade_status']:null;

        $wc_order_id = substr($out_trade_no,14);
        $order = wc_get_order($wc_order_id);
        if(!$order){
            wc_get_logger()->error(__('支付宝回调：订单信息异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).print_r($requestData,true));
            wp_die(__("支付宝回调：订单信息异常！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        try {
            if (in_array($trade_status,['TRADE_FINISHED','TRADE_SUCCESS'])) {
                $order->payment_complete($transaction_id);
            }
        } catch (Exception $e) {
            wc_get_logger()->error(__('支付宝回调：系统异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage().print_r($requestData,true));
            wp_die(__("支付宝回调：系统异常！",WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),'error');
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response(wpyaa_ensure_woocommerce_wc_get_cart_url());
        }

        return new Wpyaa_Alipay_Wechat_For_WooCommerce_Redirect_Response($order->get_checkout_order_received_url());
    }

    /**
     * 支付宝页面
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function notify($request){
        //we must be get all query params ,then generate the sign(validate the all data is safe)
        //@see 159 line
        $requestData = $request->get_body_params() ;
        foreach ($requestData as $k=>$v){
            $requestData[$k] = sanitize_text_field($v);
        }

        //validate all query params is valid by rsa2
        if(!Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay::validateSign($requestData)){
            wc_get_logger()->error(__('支付宝回调：签名验证失败',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).print_r($requestData,true));
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('failed');
        }

        $out_trade_no = isset($requestData['out_trade_no'])?$requestData['out_trade_no']:null;
        $transaction_id = isset($requestData['trade_no'])?$requestData['trade_no']:null;
        $trade_status = isset($requestData['trade_status'])?$requestData['trade_status']:null;

        $wc_order_id = substr($out_trade_no,14);
        $order = wc_get_order($wc_order_id);
        if(!$order){
            wc_get_logger()->error(__('支付宝回调：订单信息异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).print_r($requestData,true));
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('failed');
        }

        try {
            if (in_array($trade_status,['TRADE_FINISHED','TRADE_SUCCESS'])) {
                $order->payment_complete($transaction_id);
            }
        } catch (Exception $e) {
            wc_get_logger()->error(__('支付宝回调：系统异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE).$e->getMessage().print_r($requestData,true));
            return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('failed');
        }

        return new Wpyaa_Alipay_Wechat_For_WooCommerce_Content_Response('success');
    }

    /**
     * 创建支付宝订单
     * @param WC_Order $order
     * @return array
     */
    public static function createOrder($order){
        $gateway = Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay::instance();

        $args = array (
            'app_id' =>$gateway->get_option('pid'),
            'method'=>self::isMiniWebClient()?'alipay.trade.wap.pay':'alipay.trade.page.pay',
            'charset'=>'utf-8',
            'format' => 'JSON',
            'sign_type'=>'RSA2',
            'timestamp'=>date_i18n('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => rest_url('/wpyaa/woocommerce/alipay-wechat/v1/alipay/notify'),
            'return_url' => rest_url('/wpyaa/woocommerce/alipay-wechat/v1/alipay/back'),
            'biz_content'=>json_encode(array(
                'product_code'=>self::isMiniWebClient()?'QUICK_WAP_WAY':'FAST_INSTANT_TRADE_PAY',
                'out_trade_no'=>date_i18n('YmdHis').$order->get_id(),
                'total_amount'=>round($order->get_total(),2),
                'subject'=> self::getOrderTitle($order)
            ),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
        );

        $args['sign'] = Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay::sign($args);
        return $args;
    }
}