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
use app\api\model\UnitClass;
use app\api\model\User;
use app\api\model\Cls;
use app\api\model\Post;
use app\api\model\Unit;
use app\api\model\UserClass;
use app\api\model\Zan;
use app\api\service\Token;
use app\api\validate\IDMustBePositiveInt;
use app\api\validate\PagingParameter;
use app\lib\enum\ClassEnum;
use app\lib\enum\UnitEnum;
use app\lib\enum\ZanStatusEnum;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\TokenException;
use app\api\validate\Comment as CommentValidate;
use app\api\service\Comment as CommentService;
use think\Db;

class Home
{
    /**
     *  获取所有分校的信息
     * @param string $id  分校id
     * @return \think\response\Json
     * @throws MissException
     */
    public function getBranchSchool($id = '')
    {
        $uid = Token::getCurrentUid();
        $userInfo = User::field('mobile_bind')->get($uid);
        $unid = $this->checkUnit($uid, $id);
        $userClass = UserClass::where(['user_id'=>$uid])
            ->where('class_id','<>',ClassEnum::MiniKnifeClass)
            ->find();
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
            'unid'=>$unid,
            'class_id'=>empty($userClass) ? ClassEnum::MiniKnifeClass : $userClass->class_id,
            'data'=>$unit
        ]);
    }

    /**
     * 查询分校下各个班级下打卡人数
     * @param $id      分校id
     * @return \think\response\Json
     * @throws MissException
     */
    public function getUnitClass($id = '')
    {
        $uid = Token::getCurrentUid();
        //进行查看用户属于哪个分校
        $unid = $this->checkUnit($uid,$id);
        //查询分校下的班级信息
        $unitClass = Cls::getUnitClass($unid);
        if(!$unitClass){
            throw new MissException([
                'msg'=>'分校下班级信息查询失败',
                'errorCode'=>50000
            ]);
        }
        //查询今日发帖信息和发帖人对应的班级
        //$post = Post::getPostByToday();
        //$res = $this->postCountByClass($unitClass->toArray(), $post->toArray());
        //跟着需求改代码
        $unitClass = $unitClass->toArray();
        foreach ($unitClass as &$val){
            $val['count'] = UserClass::where(['class_id'=>$val['id'],'status'=>1])
                ->count();
        }
        return json($unitClass);
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
    public function getPunchCardToday($id = '', $page = 1, $size = 20)
    {
        $uid = Token::getCurrentUid();
        (new PagingParameter())->goCheck();
        $unid = $this->checkUnit($uid,$id);
        //查询今日发帖信息
        $pagingPosts = Post::getSummaryByPage($unid,$page,$size);
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
     * 分校下  排行榜
     * @param string $id
     * @return \think\response\Json
     * @throws MissException
     */
    public function getRankingList($id = '')
    {
        $uid = Token::getCurrentUid();
        $unid = $this->checkUnit($uid,$id);
        //进行查询排行榜信息，先从缓存中读取，两小时更新一次
        $rankingData = cache('home_ranking'.$unid);
        if($rankingData){
            return json($rankingData);
        }
        //进行查询排行榜
        $pagingData = $this->unitRanking($unid);
        //$pagingData = User::getSummaryByPage();
        if(empty($pagingData)){
            throw new MissException([
                'msg'=>'分校排行榜查询失败',
                'errorCode'=>50000
            ]);
        }
        $pagingData = $pagingData->toArray();
        foreach ($pagingData['data'] as $key=>&$val){
            $val['nick_name'] = urlDecodeNickName($val['nick_name']);
        }
        cache('home_ranking'.$unid,$pagingData['data'],7200);
        return json($pagingData['data']);
    }

    /**
     * 根据地区查询个地区的排行榜
     * 模型的多表联查，没整明白，先用Db吧
     * @param $unid     分校id
     * @param int $page
     * @param int $size
     * @return \think\Paginator
     */
    private function unitRanking($unid, $page=1, $size=20)
    {
        $data = Db::table('yx_unit_class')
            ->alias('unitc')
            ->join('yx_user_class userc','userc.class_id=unitc.class_id')
            ->join('yx_user u','userc.user_id=u.id')
            ->field('u.user_name,u.nick_name,u.avatar_url,u.already_number,u.sentence_number,u.punch_days')
            ->order('u.punch_days','desc')
            ->group('u.id')
            ->where('userc.status','=',1)
            ->where('userc.class_id','<>',ClassEnum::MiniKnifeClass)
            ->where('unitc.unid','=',$unid)
            ->paginate($size, true, ['page' => $page]);
        return $data;
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
     * @param $uid 用户id
     * @param $id  分校地区id
     * @return int|mixed
     */
    private function checkUnit($uid, $id)
    {
        if(empty($id)){
            $userClass = UserClass::where(['user_id'=>$uid])
                ->where('class_id','<>',ClassEnum::MiniKnifeClass)
                ->find();
            if(empty($userClass)){
                $id = UnitEnum::Beijing;
            }else{
                $unitClass = UnitClass::where('class_id',$userClass->class_id)->find();
                $id = $unitClass->unid;
            }
        }else{
            (new IDMustBePositiveInt()) -> goCheck();
        }
        return $id;
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
        $this->setCommentCache($uid, $data['post_id'],$res->id);
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
            $userInfo = User::getByUid($uid);
            $userInfo = $userInfo->visible(['nick_name', 'avatar_url'])
                ->toArray();
            $userInfo['post_id'] = $id;
            $this->setZanCache($uid, $id);
            $userInfo['user_id'] = $uid;
            $userInfo['status']  = ZanStatusEnum::VALID;
            $res = Zan::create($userInfo);
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

    /**
     * 缓存这篇帖子作者的最新点赞动态
     * @param $uid
     * @param $post_id
     * @return bool|mixed
     */
    private function setZanCache($uid, $post_id)
    {
        //先进行查看这个发帖人的id
        $post = Post::get($post_id);
        if(empty($post)){
            return true;
        }
        $data = cache($post->user_id.'message');
        //如果有人点赞信息存入了缓存
        if($data){
            //如果是同一个人点赞
            foreach ($data as $val){
                if(array_key_exists('zan_user_id',$val)) {
                    if ($val['post_id'] == $post_id && $val['zan_user_id'] == $uid) {
                        return true;
                    }
                }
            }
            //并且不是同一个人
            //进行追加新的点赞信息
            array_push($data,['post_id'=>$post_id,'zan_user_id'=>$uid]);
            return cache($post->user_id.'message',$data);
        }
        //如果首次进行点赞
        $arr = [];
        array_push($arr,['post_id'=>$post_id,'zan_user_id'=>$uid]);
        //缓存发帖人 的点赞信息
        return cache($post->user_id.'message',$arr);
    }

    /**
     * 缓存这篇帖子作者的最新评论动态
     * @param $uid
     * @param $post_id
     * @return bool|mixed
     */
    private function setCommentCache($uid, $post_id,$comment_id)
    {
        //先进行查看这个发帖人的id
        $post = Post::get($post_id);
        if(empty($post)){
            return true;
        }
        $data = cache($post->user_id.'message');
        //如果有人评论信息存入了缓存
        if($data){
            //如果是同一个人评论,但是不是同一条评论
            foreach ($data as $val){
                if(array_key_exists('comment_user_id',$val)){
                    if( array_key_exists('comment_id',$val)){
                        if($val['post_id'] == $post_id && $val['comment_user_id'] == $uid && $val['comment_id'] == $comment_id){
                            return true;
                        }
                    }
                }
            }
            //是同一个人但是不是同一个评论
            //进行追加新的评论信息
            array_push($data,['post_id'=>$post_id,'comment_user_id'=>$uid,'comment_id'=>$comment_id]);
            return cache($post->user_id.'message',$data);
        }
        //如果首次进行评论
        $arr = [];
        array_push($arr,['post_id'=>$post_id,'comment_user_id'=>$uid,'comment_id'=>$comment_id]);
        //缓存发帖人 的点赞信息
        return cache($post->user_id.'message',$arr);
    }
}