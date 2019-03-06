<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/6
 * Time: 16:32
 */

namespace app\lib\exception;


class UserClassException extends BaseException
{
    public $code = 404;
    public $msg = '你暂时不是班级学员,请加入学习之后再来';
    public $errorCode = 30000;
}