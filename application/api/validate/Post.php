<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/12
 * Time: 16:27
 */

namespace app\api\validate;


class Post extends BaseValidate
{
    protected $rule = [
        'class_id' => 'require|isPositiveInteger',
        'content' => 'require|isNotEmpty',
        'stage' => 'require|isPositiveInteger',
        'group' => 'require|isPositiveInteger',
    ];
}