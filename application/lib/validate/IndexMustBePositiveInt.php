<?php
namespace app\lib\validate;

class IndexMustBePositiveInt extends BaseValidate
{
    protected $rule = [
        'index' => 'require|isPositiveInteger',
    ];
}
