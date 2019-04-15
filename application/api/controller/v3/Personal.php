<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/15
 * Time: 15:34
 */

namespace app\api\controller\v3;

use app\api\model\Post;
use app\api\service\Token;
use app\lib\exception\MissException;

class Personal
{
    /**
     * 我的打卡
     */
    public function getMyPunchCard(){
        $uid = Token::getCurrentTokenVar('uid');
        $data = Post::getMyPost($uid);
        if(empty($data)){
            throw new MissException([
                'msg'=>'你还暂时没有打卡记录，赶快去打卡吧！',
                'errorCode'=>50000
            ]);
        }
        foreach ($data as $key=>$val){
            $data[$key]['content'] = json_decode($val['content']);
            $data[$key]['nick_name'] = urlDecodeNickName($val['nick_name']);
        }
        return json($data);
    }
}