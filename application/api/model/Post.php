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

    public static function getPostByToday()
    {
        $post = self::where(whereTime())
            ->group('user_id')
            ->select();
        return $post;
    }

    public static function getSummaryByPage($page=1, $size=20){
        $pagingData = self::with('cls,user')
            ->where(whereTime())
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

    public static function getSummaryByUser($uid, $page=1, $size=20)
    {
        $pagingData = self::with('cls,user')
            ->where('user_id', '=', $uid)
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

    public static function getSummaryByClass($class_id, $page=1, $size=20)
    {
        $pagingData = self::with('cls,user')
            ->where(whereTime())
            ->where('class_id', '=', $class_id)
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }
}