<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/10
 * Time: 19:06
 */

namespace app\api\model;

class Unit extends BaseModel
{
    protected $pk = 'unid';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'edittime';
    protected $hidden = ['createtime', 'edittime', 'seq'];


    public static function getUnitData()
    {
        $unit = self::order('status','desc')
            ->order('unid','asc')
            ->all();
        return $unit;
    }
}