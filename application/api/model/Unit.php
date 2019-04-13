<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/10
 * Time: 19:06
 */

namespace app\api\model;


use think\Db;

class Unit
{
    public static function selectUnitData()
    {
        return Db::name('unit')
            ->field('unid,unitname')
            ->select();
    }
}