<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/11
 * Time: 11:45
 */


namespace app\api\model;


class Cls extends BaseModel
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'yx_class';
    protected $hidden = ['is_pay', 'sort','create_time', 'update_time'];

    public function uclass()
    {
        return $this->hasOne('Unit_class','class_id','id');
    }

    public static function getByID($id)
    {
        $class = self::get($id);
        if(empty($class)){
            return false;
        }
        $class->class_src = config('setting.img_prefix').$class->class_src;
        return $class;
    }

    public static function getUnitClass($id)
    {
        $unitClass = self::hasWhere('uclass', ['unid'=>$id])
            ->order('sort')
            ->select();
        return $unitClass;
    }
}