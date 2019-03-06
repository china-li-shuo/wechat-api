<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 16:45
 */

namespace app\api\controller\v1;
use app\api\model\EnglishWord;
use app\api\model\Group;
use app\api\model\LearnedHistory;
use app\api\service\Token;
use app\api\model\Stage;

class Activity
{
    public function alreadyStudied()
    {
        //根据token获取用户已学习了哪个阶段，哪个阶段下的第几组名称，每个组下面的单词
        $uid = Token::getCurrentTokenVar('uid');
        $historyData = LearnedHistory::LearnedAll($uid);
        //获取阶段id对应的阶段名
        $historyData = Stage::getStageName($historyData);
        $historyData = Group::getGroupName($historyData);
        $historyData = EnglishWord::getWordDetail($historyData);

        $new_arr = [];
//        foreach ($historyData as $key=>$val){
//            array_push($new_arr,[
//                'create_time'=>&$val['create_time'],
//                'stage_name'=>&$val['stage_name'],
//                'group_name'=>&$val['group_name'],
//                'son'=>&$val['son'],
//            ]);
//        }

       return  json(['msg'=>'查询成功','error_code'=>0,'data'=>$historyData]);
    }
}