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

    /**
     * 获取用户最近的未读的消息
     * @return \think\response\Json
     * @throws MissException
     */
    public function getUnreadMessage()
    {
        $uid  = Token::getCurrentUid();
        set_time_limit(0);//无限请求超时时间
        $requestNumber = input('post.request_number/d');
        $unreadMessage = cache($uid . 'message');
        //如果是2 直接断开服务器连接状态
        if($requestNumber == 2){
            throw new MissException([
                'code'=>201,
                'msg'=>'这是退出请求信息',
                'errorCode'=>50000
            ]);
        }
        //如果是用户首次进入小程序请求接口，并且有上次未读信息，直接返回未读消息
        if($requestNumber == 1 && $unreadMessage){
            return json($unreadMessage);
        }
        //如果有未读信息，进行把未读信息的条数存储到变量
        if($unreadMessage){
            $number = count($unreadMessage);
        }else{
            $number = 0;
        }
        $i = 1;
        while(true){
            $unreadMessage = cache($uid . 'message');
            if($unreadMessage){
                $count = count($unreadMessage);
                //如果首次没有信息，知道获取最新的信息就会返回
                //数据发生改变 将数据响应客户端
                if($number != $count){
                    return json($unreadMessage);
                }
            }
            //否则 后台进行阻塞进行，过1秒继续请求，根据服务器性能来
            sleep(1);
            $i++;
            if($i > 59){
               throw new MissException([
                   'msg'=>'还没有最新未读信息',
                   'errorCode'=>50000
               ]);
            }
        }
    }

    /**
     * 进行读取我的消息有关的帖子最新动态
     * @return \think\response\Json
     * @throws MissException
     */
    public function readMyMessage()
    {
        $uid  = Token::getCurrentUid();
        $postIDS = input('post.post_ids');
        if(empty($postIDS)){
            throw new MissException([
                'msg'=>'post_ids参数不允许为空',
                'errorCode'=>50000
            ]);
        }
        $posts = Post::getDesignatedPosts($postIDS);
        if ($posts->isEmpty())
        {
            return json(['data' => []]);
        }
        //进行查找帖子的评论和回复功能
        $comment = new CommentService();
        $data = $comment->getCommentInfo($posts->toArray(),$uid);
        cache($uid.'message',null);
        return json([
            'data' => $data
        ]);
    }
}