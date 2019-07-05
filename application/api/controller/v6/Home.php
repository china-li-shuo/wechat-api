<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v6;


use app\api\model\Comment;
use app\api\model\User;
use app\api\model\Cls;
use app\api\model\Post;
use app\api\model\Unit;
use app\api\model\UserClass;
use app\api\model\Zan;
use app\api\service\Token;
use app\api\validate\IDMustBePositiveInt;
use app\api\validate\PagingParameter;
use app\lib\enum\ZanStatusEnum;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\TokenException;
use app\api\validate\Comment as CommentValidate;
use app\api\service\Comment as CommentService;

class Home
{
    /**
     * 获取所有分校的信息
     * @throws MissException 错误异常
     */
    public function getBranchSchool()
    {
        $uid = Token::getCurrentUid();
        $userInfo = User::field('mobile_bind')->get($uid);
        //查询分校的信息
        $unit = Unit::getUnitData();
        if(!$unit){
            throw new MissException([
                'msg' => '分校信息查询失败',
                'errorCode'=>50000
            ]);
        }
        return json([
            'mobile_bind'=>$userInfo->mobile_bind,
            'data'=>$unit
        ]);
    }

    /**
     * 查询分校下各个班级下打卡人数
     * @param $id      分校id
     * @return \think\response\Json
     * @throws MissException
     */
    public function getUnitClass($id)
    {
        (new IDMustBePositiveInt())->goCheck();
        //查询分校下的班级信息
        $unitClass = Cls::getUnitClass($id);
        if(!$unitClass){
            throw new MissException([
                'msg'=>'分校下班级信息查询失败',
                'errorCode'=>50000
            ]);
        }
        //查询今日发帖信息和发帖人对应的班级
        $post = Post::getPostByToday();
        $res = $this->postCountByClass($unitClass->toArray(), $post->toArray());
        return json($res);
    }


    /**
     * 获取全部今日打卡信息（分页）
     * @param string $token
     * @param int $page
     * @param int $size
     * @return \think\response\Json
     * @throws MissException
     * @throws TokenException
     */
    public function getPunchCardToday($id = 1, $page = 1, $size = 20)
    {
        $uid = Token::getCurrentUid();
        (new PagingParameter())->goCheck();
        (new IDMustBePositiveInt())->goCheck();
        //查询今日发帖信息
        $pagingPosts = Post::getSummaryByPage($id,$page,$size);
        if ($pagingPosts->isEmpty())
        {
            return json([
                'current_page' => $pagingPosts->currentPage(),
                'data' => []
            ]);
        }
        //获取今日打卡的班级另外三个头像,以及评论回复和点赞
        $data = $this->getClassImg($pagingPosts->toArray(),$uid);

        return json([
            'current_page' => $pagingPosts->currentPage(),
            'data' => $data
        ]);
    }

    /**
     * 排行榜
     * @param string $token
     * @throws TokenException
     */
    public function getRankingList($token = '')
    {
        $res = Token::verifyToken($token);
        if(!$res){
            throw new TokenException();
        }

        //进行查询排行榜信息，先从缓存中读取，两小时更新一次
        $rankingData = cache('home_ranking');
        if($rankingData){
            return json($rankingData);
        }
        //进行查询排行榜
        $pagingData = User::getSummaryByPage();
        $pagingData = $pagingData->toArray();
        foreach ($pagingData['data'] as $key=>&$val){
            $val['nick_name'] = urlDecodeNickName($val['nick_name']);
        }
        cache('home_ranking',$pagingData['data'],7200);
        return json($pagingData['data']);
    }

    /**
     * 获取班级下今日发帖人数
     * @param $data 班级信息
     * @param $arr  今日发帖班级信息
     */
    private function postCountByClass($data, $arr)
    {
        $i= 1;
        foreach ($data as $key=>&$val){
            $val['class_id'] = $val['id'];
            foreach ($arr as $k=>&$v){
                if($val['class_id']==$v['class_id']){
                    $val['post_count'] = $i++;
                }
                unset($val['id']);
                continue;
            }
            $i = 1;
            if(empty($val['post_count'])){
                $val['post_count'] = 0;
            }
        }
        return $data;
    }


    /**
     * 获取班级头像
     * 评论回复和点赞数量
     * @param $data
     * @return mixed
     * @throws MissException
     */
    private function getClassImg($data,$uid)
    {
        $data = $data['data'];
        foreach ($data as $key=>&$val){
            $classImgData = UserClass::getClassImgByID($val['class_id']);
            $classImgData = $classImgData->toArray();
            foreach ($classImgData as $k=>$v){
                if (empty($v['avatar_url'])){
                    unset($classImgData[$k]);
                    continue;
                }
            }
            $data[$key]['images'] = array_values($classImgData);
        }

        //进行查找帖子的评论和回复功能
        $comment = new CommentService();
        $data = $comment->getCommentInfo($data,$uid);
        return $data;
    }

    /**
     * 进行 评论、回复
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function respondComment()
    {
        $uid = Token::getCurrentUid();
        (new CommentValidate())->goCheck();
        $data = input('post.');
        if(empty($data['parent_id'])){
            $data['parent_id'] = 0;
        }
        $userInfo = User::getByUid($uid);
        $userInfo = $userInfo->visible(['nick_name','avatar_url'])
            ->toArray();
        $data['nick_name'] = $userInfo['nick_name'];
        $data['avatar_url'] = $userInfo['avatar_url'];
        $data['user_id'] = $uid;
        $data['content'] = json_encode($data['content']);
        $res = Comment::create($data);
        if(!$res){
           throw new MissException([
               'msg'=>'帖子评论失败',
               'errorCode'=>50000
           ]);
        }
        $comment = Comment::get($res->id)->toArray();
        $comment['content'] = json_decode($comment['content']);
        return json($comment);
    }

    /**
     * 帖子点赞
     * @param string $id
     * @throws MissException
     * @throws SuccessMessage
     */
    public function clickZan($id = '')
    {
        $uid = Token::getCurrentUid();
        (new IDMustBePositiveInt())->goCheck();
        $zan = Zan::where(['user_id' => $uid, 'post_id' => $id])->find();
        //如果没有点过赞，则新增记录，否则进行修改点赞状态
        if (empty($zan)) {
            $userInfo            = User::getByUid($uid);
            $userInfo            = $userInfo->visible(['nick_name', 'avatar_url'])
                ->toArray();
            $userInfo['post_id'] = $id;
            $userInfo['user_id'] = $uid;
            $userInfo['status']  = ZanStatusEnum::VALID;
            $res                 = Zan::create($userInfo);
        } else {
            if ($zan->status == ZanStatusEnum::VALID) {
                $res = Zan::where(['user_id' => $uid, 'post_id' => $id])
                    ->update(['status' => ZanStatusEnum::CANCEL]);
            } else {
                $res = Zan::where(['user_id' => $uid, 'post_id' => $id])
                    ->update(['status' => ZanStatusEnum::VALID]);
            }
        }
        if (!$res) {
            throw new MissException([
                'msg'       => '帖子点赞失败',
                'errorCode' => 50000
            ]);
        }
        throw new SuccessMessage();
    }
}