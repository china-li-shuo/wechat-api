<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/17
 * Time: 9:42
 */

namespace app\api\validate;


class Settlement extends BaseValidate
{
        protected $rule = [
            'group' => 'require|isPositiveInteger',
            'stage' => 'require|isPositiveInteger',
        ];
}