<?php
defined( 'ABSPATH' ) || exit;
class Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay extends WC_Payment_Gateway {
    /**
	 * 支付说明
     * @var string
     */
    private $instructions;

    /**
	 * 微信支付网关
     * @var Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay
     */
    private static $_instance;

    /**
	 * 返回微信支付网关实例
     * @return Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay
     */
    public static function instance(){
		if(!self::$_instance){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    /**
     * Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay constructor.
     */
	private function __construct() {
		$this->id                 = 'wpyaa_alipay_wechat_for_woocommerce_alipay';
		$this->icon               = plugins_url('icon/alipay.png',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCE_FILE);
		$this->has_fields         = false;

		if($this->get_option ( 'refund','yes' )==='yes'){
            $this->supports         []= 'refunds';
        }

		$this->method_title       =  __('支付宝',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE);
		$this->method_description = __('PC电脑端扫码支付，手机浏览器端自动唤起支付宝APP支付(对微信内置浏览器内支付做了兼容处理),订单退款。',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE);

		$this->title              = $this->get_option ( 'title', __('支付宝',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE));
		$this->description        = $this->get_option ( 'description');
		$this->instructions       = $this->get_option('instructions');

		$this->init_form_fields ();
		$this->init_settings ();

		$this->enabled            = $this->get_option ( 'enabled' );

		add_filter ( 'woocommerce_payment_gateways', array($this,'woocommerce_add_gateway') );
		add_action ( 'woocommerce_update_options_payment_gateways_' .$this->id, array ($this,'process_admin_options') );
		//兼容低版本wordpress
		add_action ( 'woocommerce_update_options_payment_gateways', array ($this,'process_admin_options') );
		add_action ( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action ( 'woocommerce_thankyou_'.$this->id, array( $this, 'thankyou_page' ) );
	}
    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = array (
            'enabled' => array (
                'title'       =>  __('支付宝网关',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'type'        => 'checkbox',
                'label'       =>  __('启用/禁用',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'default'     => 'no'
            ),
            'title' => array (
                'title'       => __('标题',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'type'        => 'text',
                'desc_tip'    => true,
                'default'     =>  __('支付宝',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
				'description'=>__('这控制用户在结帐时看到的标题。',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)
            ),
            'description' => array (
                'title'       => __('描述',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'desc_tip'    => true,
                'type'        => 'textarea',
                'description'=>__('顾客支付的时候会看到关于该支付方式的说明',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)
            ),
            'instructions' => array(
                'title'       =>__('说明',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'type'        => 'textarea',
                'desc_tip'    => true,
                'description' => __('说明将会被显示在订单确认页面和相关邮件中',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)
            ),
            'pid' => array(
                'title'       =>'APP ID',
                'type'        => 'text',
                'description'=>__('您可以在<a href="https://www.wpyaa.com/post/2.html" target="_blank">帮助文档</a>内获悉如何获取配置信息',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)
            ),
            'rsa_private_key' => array(
                'title'       => __('(RSA2)应用私钥',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'type'        => 'textarea'
            ),
            'rsa_public_key' => array(
                'title'       => __('支付宝公钥',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'type'        => 'textarea',
                'description'=>__('注意：请填写“支付宝公钥”,而不是开发者公钥',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)
            ),
            'phone' => array(
                'title'       => __('手机网站支付',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'type'        => 'checkbox',
                'label'       =>__('启用/禁用',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'default'     =>'yes',
                'disabled'    =>true,
                'description' =>__('手机浏览器内，唤起支付宝APP支付，您需要在<a href="https://b.alipay.com/signing/productSetV2.htm" target="_blank">支付宝商家中心/产品中心</a> 下开通“手机网站支付”',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)
            ),
            'pc' => array(
                'title'       => __('电脑网站支付',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'type'        => 'checkbox',
                'label'       =>__('启用/禁用',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'default'     => 'yes',
                'disabled'    => true,
                'description' =>__('PC电脑端，支付宝扫码支付，您需要在<a href="https://b.alipay.com/signing/productSetV2.htm" target="_blank">支付宝商家中心/产品中心</a> 下开通“电脑网站支付”',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)
            ),
            'refund' => array(
                'title'       => __('支付宝退款',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'type'        => 'checkbox',
                'label'       =>__('启用/禁用',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),
                'default'     =>'yes'
            )
        );
    }

    public function process_refund( $order_id, $amount = null, $reason = ''){
        $wc_order = wc_get_order ($order_id );
        if(!$wc_order){
            return new WP_Error( 'invalid_order',__('订单信息异常',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE));
        }

        $total = $wc_order->get_total ();
        if($amount<=0||$amount>$total){
            return new WP_Error( 'invalid_order',__('退款金额超出总金额或为0',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE) );
        }

        $args = array (
            'app_id' =>$this->get_option('pid'),
            'method'=>'alipay.trade.refund',
            'charset'=>'utf-8',
            'sign_type'=>'RSA2',
            'timestamp'=>date_i18n('Y-m-d H:i:s'),
            'version'=>'1.0',
            'biz_content'=>json_encode(array(
               // 'out_trade_no'=>$wc_order->get_id(),
                'trade_no'=>$wc_order->get_transaction_id(),
                'refund_amount'=>round($amount,2),
                'out_request_no'=> date_i18n('Ymdhis').$wc_order->get_id(),
                'refund_reason'=>$reason
            ))
        );


        try {
            $args['sign'] = self::sign($args);
            $response =  wp_remote_post('https://openapi.alipay.com/gateway.do',array(
                'body' => $args
            ));

            if(is_wp_error($response)){
                throw new Exception($response->get_error_message());
            }

            $response = iconv("GB2312","UTF-8", wp_remote_retrieve_body($response));
            $response = json_decode($response,true);
            if(!$response||!is_array($response)){
                $response=array();
            }

            if($response['alipay_trade_refund_response']['code']!=10000){
                throw new Exception("code:{$response['alipay_trade_refund_response']['code']},msg:{$response['alipay_trade_refund_response']['msg']};code:{$response['alipay_trade_refund_response']['sub_code']},msg:{$response['alipay_trade_refund_response']['sub_msg']}");
            }
        }catch(Exception $e){
            wc_get_logger()->error(__('支付宝退款失败：',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE),$e->getMessage());
            return new WP_Error( 'refuse_error', $e->getMessage());
        }

        return true;
    }

    /**
	 * 注册支付网关
     * @param $methods
     * @return array
     */
    public function woocommerce_add_gateway($methods) {
        $methods [] = $this;
        return $methods;
    }

    /**
     * 支付成功邮件：支付说明显示
     *
     * @param WC_Order $order 订单信息
     * @param bool $sent_to_admin 是否发送给管理员
     * @param bool $plain_text 是否是纯文本邮件
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        $method = $order->get_payment_method();
        if ( $this->instructions && ! $sent_to_admin && $this->id === $method) {
            echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
        }
    }

    /**
     * 感谢支付页面：支付说明显示
     */
    public function thankyou_page() {
        if ( $this->instructions ) {
            echo wpautop( wptexturize( $this->instructions ) );
        }
    }

	public function process_payment($order_id) {
        return array(
            'result'  => 'success',
            'redirect'=> add_query_arg([
                'id'=>$order_id
            ],rest_url("/wpyaa/woocommerce/alipay-wechat/v1/alipay/index"))
        );
	}

    /**
     *  计算签名
     * @param array $args
     * @return string
     */
    public static function sign($args){
        $instance = Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay::instance();
        $res=$instance->get_option('rsa_private_key');
        if(strpos($res,'-----BEGIN RSA PRIVATE KEY-----')===false){
            $res = "-----BEGIN RSA PRIVATE KEY-----\n" .wordwrap($res, 64, "\n", true) ."\n-----END RSA PRIVATE KEY-----";
        }

        openssl_sign(self::getSignContent($args), $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
        return  base64_encode($sign);
    }

    private static function getSignContent($args) {
        ksort($args);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($args as $k => $v) {
            if (is_null($v) || $v === "" || is_array($v) || strpos($v, '@') === 0) {
                continue;
            }

            if ($i == 0) {
                $stringToBeSigned .= "$k" . "=" . "$v";
            } else {
                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
            }
            $i++;
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 验证签名
     * @param array $args
     * @return bool
     */
    public static function validateSign(array $args){
        $instance = Wpyaa_Alipay_Wechat_For_WooCommerce_Alipay::instance();
        $publicKey=$instance->get_option('rsa_public_key');

        $sign = isset($args['sign'])?$args['sign']:null;

        unset($args['sign_type']);
        unset($args['sign']);

        if(strpos($publicKey, '-----BEGIN PUBLIC KEY-----')===false){
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }

        return (bool)openssl_verify(self::getSignContent($args), base64_decode($sign), $publicKey, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
    }
}