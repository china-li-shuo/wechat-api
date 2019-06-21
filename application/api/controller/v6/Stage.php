<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v6;

use app\api\service\Stage as StageService;
use app\api\service\Token;
use app\api\validate\ClassID;
use app\api\validate\StageID;
use app\lib\exception\MissException;

class Stage
{
    /**
     * 根据班级查询对应的阶段
     * @return \think\response\Json
     * @throws \app\lib\exception\ParameterException
     */
    public function getAllStage()
    {
        $uid = Token::getCurrentUid();
        (new ClassID()) -> goCheck();
        (new StageID()) -> goCheck();
        //根据班级获取此班级下所有的阶段
        $stage = new StageService();
        $stages = $stage->getCpStage(input('post.class_id/d'), input('post.stage/d'));
        if(empty($stages)){
            throw new MissException([
                'msg'=>'没有查到此班级下对应的权限',
                'errorCode'=>50000
            ]);
        }
        //判断用户某一阶段已学了多少个单词
        $stages = $stage->getAlreadyNumberByStage($uid, $stages);
        //根据前端需求，进行切割字符串
        foreach ($stages[0]['son'] as $key => &$val) {
            $val['stageName'] = mb_substr($val['stage_name'], 0, 2);
        }
        return json(['code' => 200, 'msg' => '查询成功', 'data' => $stages]);
    }

    /**
     * 阶段详情
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function getDetail()
    {
        $uid = Token::getCurrentUid();
        (new ClassID()) -> goCheck();
        (new StageID()) -> goCheck();
        $stage_id = input('post.stage/d');
        $class_id = input('post.class_id/d');
        $stage = new StageService();
        $data = $stage->detail($uid, $stage_id, $class_id);
        return json($data);
    }
}