<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/27
 * Time: 16:00
 */


namespace app\api\validate;


class Comment extends BaseValidate
{
    protected $rule = [
        'post_id' => 'require|isPositiveInteger',
        'content' => 'isNotEmpty|length:1,50',
    ];
}