<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/11
 * Time: 11:45
 */

namespace app\api\model;


class UserClass extends BaseModel
{

    public function cls()
    {
        return $this->hasOne('Cls','id','class_id')->bind([
            'class_name'
        ]);

    }

    public function user()
    {
        return $this->hasOne('User','id','user_id')->bind([
            'nick_name', 'user_name', 'punch_days', 'avatar_url', 'already_number', 'sentence_number'
        ]);
    }

    public function imgUrl()
    {
        return $this->hasOne('User','id','user_id')->bind([
            'avatar_url'
        ]);
    }

    public static function getClassImgByID($class_id)
    {
        $classImg = self::with('imgUrl')
            ->visible(['avatar_url'])
            ->limit(5)
            ->where('class_id',$class_id)
            ->where('status',1)
            ->select();
        return $classImg;
    }

    public static function getUserClass($uid)
    {
        $userClass = self::with('cls')
            ->where(['user_id'=>$uid,'status'=>1])
            ->select();
        return $userClass;
    }

    public static function getUserClassByUCid($uid,$class_id)
    {
        $userClass = self::where([
            'user_id'=>$uid,
            'class_id'=>$class_id,
            'status'=>1])
            ->find();
        return $userClass;
    }

    public static function allUserClassByClassID($class_id)
    {
        $userClass = self::with('user')
            ->where(['class_id'=>$class_id,'status'=>1])
            ->orderRand('user_id')
            ->select();
        return $userClass;
    }

    /**
     *根据已掌握单词的数量倒叙排序
     */
    public static function alreadyNumDescByID($class_id,$limit = 20)
    {
        $userClass = self::withJoin([
                'user'=>['nick_name', 'user_name', 'punch_days', 'avatar_url', 'already_number', 'sentence_number']
            ])
            ->where(['class_id'=>$class_id,'status'=>1])
            ->order('already_number desc')
            ->limit($limit)
            ->select();
        return $userClass;
    }
}