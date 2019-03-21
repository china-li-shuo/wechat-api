<?php

namespace app\lib\exception;


class SmsException extends BaseException
{
    public $code = 400;
    public $msg = '你输入的短信验证码不正确';
    public $errorCode = 60000;
}