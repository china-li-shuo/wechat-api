<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/8
 * Time: 13:58
 */


namespace app\article\controller\v1;


use app\article\model\NaviTempL;
use app\article\model\User;
use app\lib\exception\MissException;
use app\article\service\Token;

class Nav
{
    /**
     * 首页导航模板信息
     * @param string $token
     * @throws MissException
     */
    public function  getNavTempL($sign = '')
    {
        echo 'hello world';die;
        $nav = NaviTempL::where('status', '=', 1)
            ->find();
        if(empty($nav)){
            throw new MissException([
                'msg'=>'首页导航模板数据为空',
                'errorCode'=>5000
            ]);
        }
        $nav = $nav->hidden(['status','create_time','update_time'])
            ->toArray();
        $nav['quotations'] = json_decode($nav['quotations'], true);
        //进行查询用户的打卡天数
        if ($sign == 'success') {
            $uid = Token::getCurrentUid();
            $user = User::field('punch_days')
                     ->get($uid);
             $nav['punch_days'] = $user->punch_days;
        }
        return json($nav);
    }
}