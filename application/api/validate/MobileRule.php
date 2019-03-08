<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/8
 * Time: 18:54
 */

namespace app\api\validate;


class MobileRule extends BaseValidate
{
    protected $rule = [
        'mobile' => 'require|isMobile',
    ];
}