<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/11
 * Time: 14:23
 */

namespace app\api\dao;


use app\lib\exception\MissException;
use think\Db;
use think\Model;

class Post extends Model
{

    public static function getSummaryByPage($page=1, $size=20){
        return Db::table('yx_post')
            ->alias('p')
            ->join('yx_class c','p.class_id = c.id')
            ->join('yx_user u','p.user_id = u.id')
            ->json(['p.content'])
            ->field('p.id,u.nick_name,u.user_name,u.punch_days,u.avatar_url,p.content,p.create_time,p.class_id,c.class_name')
            ->where(todayWhere())
            ->order('p.create_time desc')
            ->paginate($size, true, ['page' => $page]);
    }

    /**
     * 查询今日所有发帖的人数
     * @return array|\PDOStatement|string|\think\Collection
     */
    public static function selectPost()
    {
        return Db::table('yx_post')
            ->alias('p')
            ->join('yx_class c','p.class_id = c.id')
            ->field('p.user_id,p.create_time,p.class_id')
            ->where(todayWhere())
            ->group('p.user_id,c.id')
            ->select();
    }

    /**
     * 查询今日最新发帖的20个
     * @param $limit 发帖个数
     */
    public static function limitPost($limit)
    {

        return Db::table('yx_post')
            ->alias('p')
            ->join('yx_class c','p.class_id = c.id')
            ->join('yx_user u','p.user_id = u.id')
            ->json(['p.content'])
            ->field('u.nick_name,u.user_name,u.punch_days,u.avatar_url,p.content,p.create_time,p.class_id,c.class_name')
            ->where(todayWhere())
            ->order('p.create_time desc')
            ->limit($limit)
            ->select();
    }

    /**
     * 查询此班级下今日最新发帖的20个
     * @param $class_id
     * @param $limit
     */
    public static function classLimitPost($class_id,$limit)
    {
        return Db::table('yx_post')
            ->alias('p')
            ->join('yx_class c','p.class_id = c.id')
            ->join('yx_user u','p.user_id = u.id')
            ->json(['p.content'])
            ->field('u.nick_name,u.user_name,u.punch_days,u.avatar_url,p.class_id,p.content,p.create_time,c.class_name')
            ->where(todayWhere())
            ->where('p.class_id',$class_id)
            ->order('create_time desc')
            ->limit($limit)
            ->select();

    }
    public static function findPost($uid,$arr)
    {
       return Db::name('learned_child')
            ->where('class_id',$arr['class_id'])
            ->where('stage',$arr['stage'])
            ->where('group',$arr['group'])
            ->where('user_id',$uid)
            ->field('clock_status')
            ->find();
    }
    /**
     * 进行记录发帖，并记录发帖天数
     * @param $data
     */
    public static function addPost($uid,$arr)
    {
         //先进行判断此阶段组的发帖状态
        $clockStatusData = self::findPost($uid,$arr);
        if(empty($clockStatusData)){
            $learnedChildData = [
                'user_id'=>$uid,
                'class_id'=>$arr['class_id'],
                'stage'=>$arr['stage'],
                'group'=>$arr['group'],
            ];
            $res = Db::name('learned_child')->insert($learnedChildData);
            if ($res){
                $clockStatusData = self::findPost($uid,$arr);
            }
        }
        //如果没有打卡记录，或者打卡状态为0，能够进行发帖子
        if($clockStatusData['clock_status'] === 0){
            Db::startTrans();
            try{
                $data = [
                    'user_id'=>$uid,
                    'class_id'=>&$arr['class_id'],
                    'stage'=>&$arr['stage'],
                    'group'=>&$arr['group'],
                    'content'=>json_encode($arr['content']),
                    'create_time'=>time()
                ];
                //修改用户表打卡天数
                //如果发帖成功，并且关于今天发帖纪录没有查到
                //如果不是同一个班级下学习此阶段此分组，可以发帖子，修改状态，但是不能更改打卡天数
                $postCount = Db::name('post')
                    ->where('user_id',$uid)
                    ->where(whereTime())
                    ->count();
                if($postCount!=0){
                    Db::name('post')->json(['content'])->insert($data);
                    Db::name('learned_child')
                        ->where('class_id',$arr['class_id'])
                        ->where('stage',$arr['stage'])
                        ->where('group',$arr['group'])
                        ->where('user_id',$uid)
                        ->update(['clock_status'=>1]);
                    Db::commit();
                    return true;
                }

                Db::name('post')->insert($data);
                Db::name('learned_child')
                    ->where('class_id',$arr['class_id'])
                    ->where('stage',$arr['stage'])
                    ->where('group',$arr['group'])
                    ->where('user_id',$uid)
                    ->update(['clock_status'=>1]);
                $userInfo = Db::name('user')
                    ->where('id',$uid)
                    ->field('punch_days')
                    ->find();
                Db::name('user')
                    ->where('id',$uid)
                    ->update(['punch_days'=>$userInfo['punch_days']+1]);
                Db::commit();
                return true;
            }catch (\Exception $e){
                Db::rollback();
                throw new MissException([
                    'msg'=>$e->getMessage(),
                    'errorCode'=>5000
                ]);
            }
        }

         return false;
    }

    /**
     * 我的打卡
     * @param $uid
     */
    public static function getMyPost($uid)
    {
        return Db::table('yx_post')
            ->alias('p')
            ->join('yx_class c','p.class_id = c.id')
            ->join('yx_user u','p.user_id = u.id')
            ->field('u.nick_name,u.user_name,u.punch_days,u.avatar_url,p.content,p.create_time,p.class_id,c.class_name')
            ->order('p.create_time desc')
            ->where('p.user_id',$uid)
            ->limit(20)
            ->select();
    }
}