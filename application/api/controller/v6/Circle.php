<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v6;

use app\api\model\ClassPermission;
use app\api\model\Cls;
use app\api\model\Post;
use app\api\model\User;
use app\api\model\UserClass;
use app\api\service\Circle as CircleService;
use app\api\service\Token;
use app\api\validate\ClassID;
use app\api\model\Stage;
use app\api\validate\PagingParameter;
use app\lib\exception\MissException;

class Circle
{

    /**
     * 切换模块信息
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function getModule()
    {
        (new ClassID())->goCheck();
        $class_id = input('post.class_id/d');
        $permit = ClassPermission::getPermitStage($class_id);
        if($permit->isEmpty()){
            throw new MissException([
                'msg'=>'此班级没有分配任何模块权限',
                'errorCode'=>50000
            ]);
        }
        $permit = $permit->hidden(['id','class_id','groups'])
            ->toArray();
        $arr = array_column($permit,'stage');
        $stage = Stage::where('id','in',$arr)
            ->field('parent_id')
            ->group('parent_id')
            ->select();
        if($stage->isEmpty()){
            throw new MissException([
                'msg'=>'此班级没有分配任何模块权限',
                'errorCode'=>50000
            ]);
        }
        $stage = $stage->toArray();
        $ids = array_column($stage,'parent_id');
        $data = Stage::where('id','in',$ids)
            ->field('id,stage_name')
            ->select();
        return json($data);
    }

    /**
     * 班级首页信息
     * @return \think\response\Json
     * @throws \app\lib\exception\ParameterException
     */
    public function getCircleInfo()
    {
        (new ClassID())->goCheck();
        $class_id = input('post.class_id/d');
        $uid = Token::getCurrentUid();
        //获取用户基本信息
        $user = User::getByUid($uid);
        //班级基本信息
        $class = Cls::getByID($class_id);
        $circle = new CircleService();
        $data = $circle->info($user, $class);
        return json($data);
    }

    /**
     * 我的班级内今日打卡
     * @param int $page   页码
     * @param int $size   每页展示条数
     * @return \think\response\Json
     * @throws \app\lib\exception\ParameterException
     */
    public function getPunchCardToday($page = 1, $size = 20)
    {
        (new PagingParameter())->goCheck();
        (new ClassID())->goCheck();
        $class_id = input('post.class_id/d');
        $pagingPosts = Post::getSummaryByClass($class_id, $page, $size);
        if ($pagingPosts->isEmpty())
        {
            return json([
                'current_page' => $pagingPosts->currentPage(),
                'data' => []
            ]);
        }
        $post = $pagingPosts->toArray();
        foreach ($post['data'] as &$val){
            $val['nick_name'] = urlDecodeNickName($val['nick_name']);
            $val['content'] = json_decode($val['content']);
        }
        return json([
            'current_page' => $pagingPosts->currentPage(),
            'data' => $post['data']
        ]);
    }

    /**
     * 班级内的今日榜单
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function getRankingList()
    {
        (new ClassID())->goCheck();
        $class_id = input('post.class_id/d');
        $uid      = Token::getCurrentUid();
        $is_today = empty(input('post.is_today/d')) ? 1 : input('post.is_today/d');
        $userClass = UserClass::getUserClassByUCid($uid, $class_id);
        //判断用户是否是此班级成员
        if (!$userClass) {
           throw new MissException([
               'msg'=>'你暂时不是班级成员，请申请加入！',
               'errorCode'=>50000
           ]);
        }
        $circle = new CircleService();
        //获取班级的排行榜信息
        $rankList = $circle->getClassRanKing($class_id, $is_today);
        if(empty($rankList)){
            throw new MissException([
                'msg'       => '暂时没有人进行学习，快来抢沙发呀！',
                'errorCode' => 50000
            ]);
        }
        return json($rankList);
    }
}