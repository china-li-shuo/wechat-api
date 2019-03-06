<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 10:35
 */

namespace app\api\controller\v1;

use app\api\service\Token;
use app\api\model\Share as ShareModel;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;

class Share
{
    public function punchCard()
    {
        //从token中获取uid
        //根据uid判断分享时间
        //如果今日已分享过，则打卡天数不加一，否则打卡天数加一
        $uid = Token::getCurrentTokenVar('uid');

        $data = ShareModel::addShare($uid);
        if(!$data){
            throw new MissException([
                'msg' => '打卡时间发生了错误',
                'errorCode' => 50000
            ]);
        }

        throw new SuccessMessage();
    }
}