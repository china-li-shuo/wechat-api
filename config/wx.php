<?php

return [
    //  +---------------------------------
    //  微信相关配置
    //  +---------------------------------

    // 单词打卡小程序app_id
    'app_id' => 'wx5a976cab60edb159',
    // 单词打卡小程序app_secret
    'app_secret' => '71b541e0f4dff18440a679383ffbd469',

    // 微信使用code换取用户openid及session_key的url地址
    'login_url' => "https://api.weixin.qq.com/sns/jscode2session?" .
        "appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    // 微信获取access_token的url地址
    'access_token_url' => "https://api.weixin.qq.com/cgi-bin/token?" .
        "grant_type=client_credential&appid=%s&secret=%s",
    // 英语阅读小程序app_id
    'read_app_id' => 'wx97b569f51f214d52',
    // 英语阅读小程序app_secret
    'read_app_secret' => '499834472e52c1a8d3bcde649be43193',

];
