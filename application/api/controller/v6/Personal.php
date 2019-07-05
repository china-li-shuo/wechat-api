<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v6;

use app\api\model\Post;
use app\api\model\User;
use app\api\model\UserClass;
use app\api\service\Token;
use app\api\validate\PagingParameter;
use app\lib\exception\MissException;
use app\api\service\Comment as CommentService;

class Personal
{

    /**
     * 用户个人信息
     * @return \think\response\Json
     * @throws MissException
     */
    public function getPersonalInfo()
    {
        $uid = Token::getCurrentUid();
        $user = User::field('user_name,nick_name,avatar_url,mobile')->get($uid);
        $user->mobile = substr_replace($user->mobile, '****', 3, 4);
        $user->nick_name = urlDecodeNickName($user->nick_name);
        if(!$user){
            throw new MissException([
                'msg'=>'个人信息查询失败',
                'errorCode'=>50000
            ]);
        }
        return json($user);
    }

    /**
     * 我的打卡
     * @param int $page
     * @param int $size
     * @return \think\response\Json
     * @throws MissException
     */
    public function getMyPunchCard($page = 1, $size = 20)
    {
        $uid  = Token::getCurrentUid();
        (new PagingParameter())->goCheck();
        //查询今日发帖信息
        $pagingPosts = Post::getSummaryByUser($uid, $page, $size);
        if ($pagingPosts->isEmpty())
        {
            return json([
                'current_page' => $pagingPosts->currentPage(),
                'data' => []
            ]);
        }

        $data = $pagingPosts->hidden(['user_id', 'class_id'])
            ->toArray();
        //进行查找帖子的评论和回复功能
        $comment = new CommentService();
        $data = $comment->getCommentInfo($data['data'],$uid);

        return json([
            'current_page' => $pagingPosts->currentPage(),
            'data' => $data
        ]);
    }

    /**
     * 我的班级
     * @return \think\response\Json
     * @throws MissException
     */
    public function getMyClass()
    {
        $uid  = Token::getCurrentUid();
        //查看这个用户下加入的所有班级
        $userClass = UserClass::getUserClass($uid);
        $userInfo = $userClass->hidden(['id', 'user_id', 'status'])
            ->toArray();
        //已有多少人打卡
        foreach ($userInfo as $key=>&$val){
            $val['number'] = Post::where('class_id','=',$val['class_id'])
                ->where(whereTime())
                ->group('user_id')
                ->count();
        }
        if(!$userInfo){
            throw new MissException([
                'msg'=>'我的班级信息查询失败',
                'errorCode'=>50000
            ]);
        }
        return json($userInfo);
    }

}