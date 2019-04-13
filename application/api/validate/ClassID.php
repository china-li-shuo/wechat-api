<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/13
 * Time: 10:59
 */

namespace app\api\validate;


class ClassID extends BaseValidate
{
    protected $rule = [
        'class_id' => 'require|isPositiveInteger',
    ];
}