<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 17:36
 */

namespace app\api\model;


use think\Db;
use think\Model;

class Group extends Model
{
    const PREFIX = 'yx_question.yx_';

    public static function getGroupName($historyData)
    {
        foreach ($historyData as $key=>$val){
            $data = Db::table(self::PREFIX.'group')->where('id',$val['group'])->field('id,group_name')->select();
            foreach ($data as $k=>$v){
                if($val['group'] == $v['id']){
                    $historyData[$key]['group_name'] = $v['group_name'];
                }
            }
        }

        return $historyData;
    }
}