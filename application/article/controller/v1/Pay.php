<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/9
 * Time: 15:33
 */


namespace app\article\controller\v1;


use app\article\service\Pay as PayService;
use app\article\service\WxNotify;
use app\article\validate\IDMustBePositiveInt;


class Pay
{
    /**
     * 获取预订单
     * @param string $id
     * @throws \think\Exception
     */
    public function getPreOrder($id='')
    {
        (new IDMustBePositiveInt()) -> goCheck();
        $pay= new PayService($id);
        return $pay->pay();
    }

    public function redirectNotify()
    {
        $notify = new WxNotify();
        $notify->handle();
    }

    public function notifyConcurrency()
    {
        $notify = new WxNotify();
        $notify->handle();
    }

    public function receiveNotify()
    {
        $notify = new WxNotify();
        $notify->handle();
    }
}