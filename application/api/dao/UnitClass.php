<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/11
 * Time: 10:40
 */

namespace app\api\dao;


use think\Db;

class UnitClass
{
    public static function selectUnidClass($unid)
    {
        return Db::name('unid_class')
            ->alias('uc')
            ->join('unit u','uc.unid=u.unid')
            ->join('class c','uc.class_id=c.id')
            ->field('u.unid,u.unitname,uc.class_id,c.class_name')
            ->order('c.sort')
            ->where('uc.unid',$unid)
            ->select();
    }

}