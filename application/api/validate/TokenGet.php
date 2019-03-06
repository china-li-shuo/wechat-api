<?php

namespace app\api\validate;

class TokenGet extends BaseValidate
{
    protected $rule = [
        'code' => 'require|isNotEmpty',
        'nick_name' => 'require|isNotEmpty',
        'avatar_url' => 'require|isNotEmpty',
    ];
    
    protected $message=[
        'code' => '没有code还想拿token？做梦哦'
    ];
}
