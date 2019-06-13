<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/11
 * Time: 11:45
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