<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/5
 * Time: 18:04
 */


namespace app\article\validate;


class AddRecord extends BaseValidate
{
    protected $rule = [
        'article_id' => 'require|isPositiveInteger',
        'game_time' => 'require|isPositiveInteger'
    ];
}