<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/12
 * Time: 9:06
 */

namespace app\api\validate;


class CollectionSentence extends BaseValidate
{
    protected $rule = [
        'stage' => 'require|isPositiveInteger',
        'group' => 'require|isPositiveInteger',
        'sentence_id' => 'require|isPositiveInteger',
        'is_collection' => 'require|isCollection|isPositiveInteger',
    ];
}