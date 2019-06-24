<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/13
 * Time: 10:59
 */

namespace app\api\validate;


class ModuleCode extends BaseValidate
{
    protected $rule = [
        'module_code' => 'require|isPositiveInteger',
    ];
}