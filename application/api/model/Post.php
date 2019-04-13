<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/11
 * Time: 14:23
 */

namespace app\api\model;


use app\lib\exception\MissException;
use think\Db;

class Post
{
    /**
     * 查询今日所有发帖的人数
     * @return array|\PDOStatement|string|\think\Collection
     */
    public static function selectPost()
    {
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $where[]    = ['p.create_time', 'between time', [$beginToday, $endToday]];

        return Db::table('yx_post')
            ->alias('p')
            ->join('yx_user_class uc','p.user_id = uc.user_id')
            ->field('p.user_id,uc.class_id,p.create_time')
            ->where($where)
            ->where('status',1)
            ->group('uc.user_id')
            ->select();
    }

    /**
     * 查询今日最新发帖的20个
     * @param $limit 发帖个数
     */
    public static function limitPost($limit)
    {
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $where[]    = ['p.create_time', 'between time', [$beginToday, $endToday]];

        return Db::table('yx_post')
            ->alias('p')
            ->join('yx_user_class uc','p.user_id = uc.user_id')
            ->join('yx_class c','uc.class_id = c.id')
            ->join('yx_user u','p.user_id = u.id')
            ->field('u.nick_name,u.user_name,u.punch_days,u.avatar_url,uc.class_id,p.content,p.create_time,c.class_name')
            ->where($where)
            ->where('status',1)
            ->group('uc.user_id')
            ->order('create_time desc')
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
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $where[]    = ['p.create_time', 'between time', [$beginToday, $endToday]];
        return Db::table('yx_post')
            ->alias('p')
            ->join('yx_user_class uc','p.user_id = uc.user_id')
            ->join('yx_class c','uc.class_id = c.id')
            ->join('yx_user u','p.user_id = u.id')
            ->field('u.nick_name,u.user_name,u.punch_days,u.avatar_url,uc.class_id,p.content,p.create_time,c.class_name')
            ->where($where)
            ->where('status',1)
            ->where('uc.class_id',$class_id)
            ->group('uc.user_id')
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
                $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                $where[]    = ['create_time', 'between time', [$beginToday, $endToday]];
                //如果不是同一个班级下学习此阶段此分组，可以发帖子，修改状态，但是不能更改打卡天数
                $postCount = Db::name('post')
                    ->where('user_id',$uid)
                    ->where($where)
                    ->count();
                if($postCount!=0){
                    Db::name('post')->insert($data);
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
}