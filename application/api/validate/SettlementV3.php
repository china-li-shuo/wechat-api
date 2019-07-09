<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/17
 * Time: 9:42
 */

namespace app\api\validate;


class SettlementV3 extends BaseValidate
{
        protected $rule = [
            'class_id' => 'require|isPositiveInteger',
            'group' => 'require|isPositiveInteger',
            'stage' => 'require|isPositiveInteger',
        ];
}