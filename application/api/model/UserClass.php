<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/7
 * Time: 16:42
 */

namespace app\api\model;


use think\Db;

class UserClass extends BaseModel
{

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
}