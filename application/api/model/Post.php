<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/11
 * Time: 14:23
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
            ->order('create_time desc')
            ->where(whereTime())
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }
}