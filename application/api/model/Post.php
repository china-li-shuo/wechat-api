<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/11
 * Time: 11:45
 */

namespace app\api\model;


class Post extends BaseModel
{
    protected $hidden = ['stage','group','status','img_url','audio_url'];
    public function cls()
    {
        return $this->hasOne('Cls','id','class_id')->bind([
            'class_name'
        ]);

    }

    public function user()
    {
        return $this->hasOne('User','id','user_id')->bind([
            'nick_name',
            'user_name',
            'punch_days',
            'avatar_url'
        ]);
    }

    public function comment()
    {
        return $this->hasMany('Comment','post_id','id');
    }

    public static function getPostByToday()
    {
        $post = self::where(whereTime())
            ->group('user_id')
            ->select();
        return $post;
    }

    public static function getSummaryByPage($id, $page=1, $size=20){
        //查询地区下的班级
        $unit = UnitClass::where('unid',$id)
            ->select();
        if(empty($unit)){
            return false;
        }
        $classIDS = array_column($unit->toArray(),'class_id');
        //此地区下的今日打卡情况
        $pagingData = self::with('cls,user,comment')
            ->where(whereTime())
            ->where('class_id','in',$classIDS)
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

    public static function getSummaryByUser($uid, $page=1, $size=20)
    {
        $pagingData = self::with('cls,user,comment')
            ->where('user_id', '=', $uid)
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

    public static function getSummaryByClass($class_id, $page=1, $size=20)
    {
        $pagingData = self::with('cls,user,comment')
            ->where(whereTime())
            ->where('class_id', '=', $class_id)
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

    /**
     * 发帖子的状态
     */
    public static function postStatus($uid, $data)
    {
        $status = self::where([
            'class_id'=>$data['class_id'],
            'user_id'=>$uid,
            'stage'=>$data['stage'],
            'group'=>$data['group'],
        ])->count();
        return $status;
    }

    /**
     * 进行记录发帖，并记录发帖天数
     * @param $data
     */
    public static function addPost($uid, $data)
    {
        //先进行判断此阶段组的发帖状态
        $clockStatusData = self::postStatus($uid, $data);
        if($clockStatusData === 0){
            $learnedChildData = [
                'user_id'=>$uid,
                'class_id'=>$data['class_id'],
                'stage'=>$data['stage'],
                'group'=>$data['group'],
            ];
            $res = LearnedChild::create($learnedChildData);
            if ($res){
                $clockStatusData = self::postStatus($uid, $data);
            }
        }
        //如果已经发过帖子
        if($clockStatusData >= 1){
            return false;
        }
        //如果没有打卡记录，或者打卡状态为0，能够进行发帖子

        $data = [
            'user_id'=>$uid,
            'class_id'=>&$data['class_id'],
            'stage'=>&$data['stage'],
            'group'=>&$data['group'],
            'content'=>json_encode($data['content']),
            'create_time'=>time()
        ];
        //修改用户表打卡天数
        self::create($data);
        LearnedChild::where([
            'class_id'=>$data['class_id'],
            'stage'=>$data['stage'],
            'group'=>$data['group'],
            'user_id'=>$uid,
        ])->update(['clock_status'=>1]);

        //如果今天打卡天数小于1进行修改
        $num = self::where([
            'class_id'=>$data['class_id'],
            'user_id'=>$uid,
        ])->where(whereTime())->count();

        if($num > 1){
            return true;
        }

        $userInfo = User::field('punch_days')->get($uid);
        User::where('id',$uid)->update(['punch_days'=>$userInfo['punch_days']+1]);
        return true;
    }

}