<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/21
 * Time: 15:10
 */
namespace app\api\controller;


use app\api\service\Token;
use think\Controller;

class BaseController extends Controller
{
    /**
     * 互联网用户专有权限
     */
    protected function checkExclusiveScope()
    {
        Token::needExclusiveScope();
    }

    /**
     * 学员以上的权限
     */
    protected function checkPrimaryScope()
    {
        Token::needPrimaryScope();
    }

    /**
     * 只有老师才能访问的权限
     */
    protected function checkSuperScope()
    {
        Token::needSuperScope();
    }

    /**
     * 所有人都拥有的权限
     * @throws \app\lib\exception\ForbiddenException
     * @throws \app\lib\exception\TokenException
     */
    protected function checkAllPeopleScope()
    {
        Token::allPeopleScope();
    }

}