<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/11
 * Time: 15:09
 */

namespace app\api\validate;


class StageID extends BaseValidate
{
    protected $rule = [
        'stage' => 'require|isPositiveInteger',
    ];
}