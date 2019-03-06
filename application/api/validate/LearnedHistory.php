<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 9:42
 */

namespace app\api\validate;


class LearnedHistory extends BaseValidate

{
    // 为防止欺骗重写user_id外键
    // rule中严禁使用user_id
    // 获取post参数时过滤掉user_id
    // 所有数据库和user关联的外键统一使用user_id，而不要使用uid
    protected $rule = [
        'group' => 'require|isNotEmpty',
        'stage' => 'require|isNotEmpty',
        'word_id' => 'require|isNotEmpty',
        'useropt' => 'require|isOptNumber',
    ];
}