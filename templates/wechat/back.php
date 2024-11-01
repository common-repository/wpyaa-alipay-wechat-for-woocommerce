<?php
/**
 * Created by 小冉(739521119@qq.com).
 * Date: 2020/9/15 23:28
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="HandheldFriendly" content="true" />
    <meta name="MobileOptimized" content="width" />
    <meta id="viewport" content="width=device-width, user-scalable=yes,initial-scale=1" name="viewport" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta content="black" name="apple-mobile-web-app-status-bar-style" />
    <meta content="telephone=no" name="format-detection" />
    <meta charset="UTF-8">
    <title><?php echo __('微信支付',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)?></title>
    <style type="text/css">
        /*基础样式*/
        html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address, big, cite, code, del, dfn, em, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, b, u, i, center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, embed, figure, figcaption, footer, header, hgroup, menu, nav, output, ruby, section, summary, time, mark, audio, video, input { margin: 0; padding: 0; border: 0; font-size: 100%; font: inherit; vertical-align: baseline; }
        article, aside, details, figcaption, figure, footer, header, hgroup, menu, nav, section, iframe {display: block;}
        ul,li,dl,dt,dd,ol{list-style:none; margin:0;}
        input[type="text"],input[type="search"]{-webkit-appearance:none;-webkit-tap-highlight-color:#fff;outline:0}
        body,button,input,select,textarea{outline-style: none;font:400 14px/1.5  "Microsoft YaHei",hei,Arial,"Lucida Grande",Verdana;}
        img{width:100%; vertical-align:top; display:block;}
        a,a:visited{text-decoration:none;outline:0; color:#1b1b1b}
        h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:500}
        html,body{font-family:"Microsoft YaHei",hei,Arial,"Lucida Grande",Verdana; color:#1b1b1b; background:#fff;}

        /* 操作头部 */
        .header{text-align:center; padding:50px 0;}
        .header img{width:80px; margin:0 auto;}
        .successHeader{background:#83d982; }
        .errorHeader{background:#f26061; }
        .successTitle ,.errorTitle{font-size:26px; text-align:center; margin:40px auto;}
        .successTitle{color:#83d982;}
        .errorTitle{color:#f26061;}
        .textCenter{text-align:center; font-size:14px; color:#333;}

        /* 加载模块 */
        .loading{width: 100%;
            /* height: 100%;background:#222428; */
            display: flex;
            align-items: center;
            justify-content: center; }
        .loading svg {
            width: 240px;
            height: 240px; position:absolute; margin-top:-120px; top:50%;
        }

        .dc-logo {
            position: fixed;
            right: 10px;
            bottom: 10px;
        }

        .dc-logo:hover svg {
            -webkit-transform-origin: 50% 50%;
            transform-origin: 50% 50%;
            -webkit-animation: arrow-spin 2.5s 0s cubic-bezier(0.165, 0.84, 0.44, 1) infinite;
            animation: arrow-spin 2.5s 0s cubic-bezier(0.165, 0.84, 0.44, 1) infinite;
        }
        .dc-logo:hover:hover:before {
            content: '\2764';
            padding: 6px;
            font: 10px/1 Monaco, sans-serif;
            font-size: 10px;
            color: #00fffe;
            text-transform: uppercase;
            position: absolute;
            left: -70px;
            top: -30px;
            white-space: nowrap;
            z-index: 20;
            box-shadow: 0px 0px 4px #222;
            background: rgba(0, 0, 0, 0.4);
        }
        .dc-logo:hover:hover:after {
            content: 'Digital Craft';
            padding: 6px;
            font: 10px/1 Monaco, sans-serif;
            font-size: 10px;
            color: #6E6F71;
            text-transform: uppercase;
            position: absolute;
            right: 0;
            top: -30px;
            white-space: nowrap;
            z-index: 20;
            box-shadow: 0px 0px 4px #222;
            background: rgba(0, 0, 0, 0.4);
            background-image: none;
        }

        @-webkit-keyframes arrow-spin {
            50% {
                -webkit-transform: rotateY(360deg);
                transform: rotateY(360deg);
            }
        }

        @keyframes arrow-spin {
            50% {
                -webkit-transform: rotateY(360deg);
                transform: rotateY(360deg);
            }
        }
    </style>
    <style type="text/css">
        .loading-box{
            margin-top: 100px;
            width: 100%;
            text-align: center;
        }
        .loading-box .loading{
            display: inline-block;
            width: 55px;
            height: 55px;
            border: 5px solid #8a8a8a;
            border-bottom: 5px solid #cccccc;
            border-radius: 50%;
            -webkit-animation:load 1.1s infinite linear;
        }
        @-webkit-keyframes load{
            from{
                transform: rotate(0deg);
            }
            to{
                transform: rotate(360deg);
            }
        }
    </style>
    <?php
    wp_print_scripts('jquery');
    ?>
</head>
<body>

<div class="loading-box">
    <div class="loading"></div>
    <div class="tips" style="margin-top:10px;"><?php echo __('微信支付结果确认中...',WPYAA_ALIPAY_WECHAT_FOR_WOOCOMMERCEE)?></div>
</div>
<script type="text/javascript">
    (function ($) {
        var times = 0;
        function queryOrderStatus() {
            times++;
            if(times>10){
                location.href = '<?php echo esc_attr($order['fail_url'])?>';
                return;
            }
            $.ajax({
                type: 'post',
                url: "<?php echo esc_attr($order['query_url']);?>",
                timeout:6000,
                cache:false,
                dataType:'json',
                async:true,
                success:function(e){
                    if (e.errcode===0) {
                        location.href = e.url;
                        return;
                    }

                    setTimeout(queryOrderStatus, 2500);
                },
                error:function(e){
                    setTimeout(queryOrderStatus, 2500);
                }
            });
        }
        setTimeout(queryOrderStatus, 3000);
    })(jQuery);
</script>
</body>
</html>
