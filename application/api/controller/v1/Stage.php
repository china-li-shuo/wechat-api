<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/2
 * Time: 10:35
 */

namespace app\api\controller\v1;

use app\api\model\Stage AS StageModel;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\MissException;

class Stage
{

    public function getStages()
    {
        $stages = StageModel::getStages();
        if(empty($stages)){
            throw new MissException([
                'msg' => '还没有任何阶段',
                'errorCode' => 50000
            ]);
        }
        return json(['code'=>200,'msg'=>'查询成功','data'=>$stages]);
    }


    public function getAllStage()
    {
        $stages = StageModel::getAllStage();
        if(empty($stages)){
            throw new MissException([
                'msg' => '查询失败',
                'errorCode' => 50000
            ]);
        }
        return json(['code'=>200,'msg'=>'查询成功','data'=>$stages]);
    }

    public function getDetail($id)
    {
        $validate = new IDMustBePositiveInt();
        $validate->goCheck();
        StageModel::findStage($id);
    }

}