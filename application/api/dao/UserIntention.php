<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/15
 * Time: 14:58
 */

namespace app\api\dao;


use app\lib\exception\MissException;
use think\Db;

class UserIntention
{
    public static function addUserIntention($data)
    {
        $arr = Db::name('user_intention')
            ->where('user_id',$data['user_id'])
            ->where('class_id',$data['class_id'])
            ->find();
        if(!empty($arr) &&$arr['status']==2){
            throw new MissException([
                'msg'=>'你已经申请过此班级了,待管理员审核',
                'errorCode'=>50000
            ]);
        }
        $res = Db::name('user_intention')
            ->insert($data);
        if($res){
            $lastID = Db::name('user_intention')
                ->getLastInsID();
            return Db::name('user_intention')
                ->where('id',$lastID)
                ->field('status')
                ->find();
        }
        return false;
    }
}