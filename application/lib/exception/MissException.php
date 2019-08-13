<?php

namespace app\lib\exception;

/**
 * 404时抛出此异常
 */
class MissException extends BaseException
{
    //因为404被重定向了，所以用405代表404
    public $code = 405;
    public $msg = 'global:your required resource are not found';
    public $errorCode = 10001;
}