<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/15 23:21
 */
?>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="keywords" content="">
    <meta name="description" content="">
    <title><?php echo __('微信支付',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)?></title>
    <style>
        *{margin:0;padding:0;}
        body{background: #f2f2f4;font-family: "Microsoft Yahei UI","Microsoft Yahei","Helvetica Neue",Helvetica,"Nimbus Sans L",Arial,"Liberation Sans","Hiragino Sans GB","Microsoft YaHei","Wenquanyi Micro Hei","WenQuanYi Zen Hei","ST Heiti",SimHei,"WenQuanYi Zen Hei Sharp",sans-serif;}
        .clearfix:after { content: "."; display: block; height: 0; clear: both; visibility: hidden; }
        .clearfix { display: inline-block; }
        * html .clearfix { height: 1%; }
        .clearfix { display: block; }
        .xctitle{height:66px;line-height:66px;text-align:center;font-size:22px;font-weight:300;border-bottom:2px solid #eee;background: #fff;}
        .qrbox{max-width: 900px;margin: 0 auto;padding:85px 20px 20px 50px;}

        .qrbox .left{width: 95%;
            display: block;
            margin: 15% auto;}
        .qrbox .left .qrcon{
            border-radius: 10px;
            background: #fff;
            overflow: visible;
            text-align: center;
            padding-top:25px;
            color: #555;
            box-shadow: 0 3px 3px 0 rgba(0, 0, 0, .05);
            vertical-align: top;
            -webkit-transition: all .2s linear;
            transition: all .2s linear;
        }
        .qrbox .left .qrcon .logo{width: 100%;}
        .qrbox .left .qrcon .title{font-size: 16px;margin: 10px auto;width: 100%;height:30px;line-height:30px;overflow:hidden;text-overflow :ellipsis }
        .qrbox .left .qrcon .price{font-size: 22px;margin: 0px auto;width: 100%;}
        .qrbox .left .qrcon .bottom{border-radius: 0 0 10px 10px;
            width: 100%;
            background: #32343d;
            color: #f2f2f2;padding:15px 0px;text-align: center;font-size: 14px;}
        .qrbox .sys{width: 60%;float: right;text-align: center;padding-top:20px;font-size: 12px;color: #ccc}
        .qrbox img{max-width: 100%;}
        @media (max-width : 1024px){
            .qrbox{padding:20px;}
            .qrbox .left{width: 95%;float: none;}
            .qrbox .sys{display: none;}
            .xcpay{width:100%;margin-top:30px;margin-bottom:20px;padding:30px 0;display:flex;flex-direction:column;justify-content:center;align-items: center;}
            .xcpay .logo{width:100px;height:100px;border-radius:100px;}
            .xcpay .price{font-size:36px;color:#333;margin-top:15px;}
            .xcpaybt{width:90%;margin:15px auto;}
            .xcbtn {
                border-radius: 2px;
                color: #fff;
                margin: auto;
                cursor: pointer;
                padding: 10px 0px;
                font-size: 16px;
                text-align: center;

                display: block;
                text-decoration: none;
                box-shadow: none!important;
            }
            .xcbtn-green {
                background-color: #5fb878;
                border-color: #5fb878!important;
                box-shadow: none!important;
            }
            .xcbtn-border-green {
                background-color: #fff !important;
                -webkit-transition: background-color .1s ease-in-out;
                transition: background-color .1s ease-in-out;
                border: 1px solid #5fb878;
                color: #5fb878!important;
            }
            .xcfooter{position: absolute;bottom:10px;left:0;text-align:center;width:100%;font-size:12px;}
        }
        @media (max-width : 320px){

        }
        @media ( min-width: 321px) and ( max-width:375px ){

        }
    </style>
</head>

<body >
<div class="xctitle"><img src="<?php echo plugins_url('icon/wechat-s.png',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCE_FILE);?>" alt="<?php echo esc_attr__('微信支付',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)?>" style="vertical-align: middle"> <?php echo __('微信支付',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)?></div>

<div class="xcpay">
    <div class="title"><?php echo $order['title'];?></div>
    <span class="price"><?php echo $order['amount'];?></span>
</div>
<div class="xcpaybt">
    <a href="<?php echo esc_attr($order['pay_url']);?>" class="xcbtn xcbtn-green" ><?php echo __('立即支付',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)?></a>
</div>
<div class="xcpaybt">
    <a href="<?php echo esc_attr(wpyaa_ensure_woocommerce_wc_get_cart_url());?>" class="xcbtn xcbtn-border-green" ><?php echo __('取消支付',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)?></a>
</div>
<div class="xcfooter"><?php echo __('“<a href="https://www.wpyaa.com">板鸭WordPress</a>”提供技术支持',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)?></div>

</body>
</html>

