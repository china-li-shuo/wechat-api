<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v5;

use app\api\dao\Post;
use app\api\dao\User;
use app\api\service\Token;
use app\lib\exception\MissException;
use think\Db;

class Personal
{


    /**
     *用户个人信息
     */
    public function getPersonalInfo()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $userInfo = User::field('user_name,nick_name,avatar_url,mobile')->get($uid)->toArray();
        $userInfo['mobile'] = substr_replace($userInfo['mobile'], '****', 3, 4);
        $userInfo['nick_name'] = urlDecodeNickName($userInfo['nick_name']);
        if(empty($userInfo)){
            throw new MissException([
                'msg'=>'个人信息查询失败',
                'errorCode'=>50000
            ]);
        }
        return json($userInfo);
    }

    /**
     * 我的打卡
     */
    public function getMyPunchCard()
    {
        $uid  = Token::getCurrentTokenVar('uid');
        $data = Post::getMyPost($uid);
        if (empty($data)) {
            throw new MissException([
                'msg'       => '你还暂时没有打卡记录，赶快去打卡吧！',
                'errorCode' => 50000
            ]);
        }
        foreach ($data as $key => $val) {
            $data[$key]['content']   = json_decode($val['content']);
            $data[$key]['nick_name'] = urlDecodeNickName($val['nick_name']);
        }
        return json($data);
    }

    /**
     * 我的班级
     */
    public function getMyClass()
    {
        $uid  = Token::getCurrentTokenVar('uid');
        //查看这个用户下加入的所有班级
        $userInfo = Db::table('yx_user_class')
            ->alias('uc')
            ->join('yx_class c','c.id=uc.class_id')
            ->where('uc.user_id',$uid)
            ->where('uc.status',1)
            ->field('uc.class_id,c.class_name')
            ->select();
        //今日打卡条件
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $where[]    = ['create_time', 'between time', [$beginToday, $endToday]];

        //已有多少人打卡
        foreach ($userInfo as $key=>$val){
            $userInfo[$key]['number'] = Db::table('yx_post')
                ->where($where)
                ->where('class_id',$val['class_id'])
                ->group('user_id')
                ->count();
        }

        if(empty($userInfo)){
            throw new MissException([
                'msg'=>'我的班级信息查询失败',
                'errorCode'=>50000
            ]);
        }
        return json($userInfo);
    }
}