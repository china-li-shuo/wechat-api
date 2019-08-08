<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/6
 * Time: 19:14
 */


namespace app\article\controller\v1;


class Token
{
    public function getToken()
    {
        $data = input('post.');
        $data['nick_name'] = $this->filterNickname($data['nick_name']);
        print_r($data);die;
    }

    /**
     * 微信昵称过滤特殊表情
     * @param $nick_name
     * @return string
     */
    function filterNickname($nick_name)
    {
        $nick_name = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $nick_name);
        $nick_name = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $nick_name);
        $nick_name = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $nick_name);
        $nick_name = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $nick_name);
        $nick_name = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $nick_name);
        $nick_name = str_replace(array('"','\''), '', $nick_name);
        return addslashes(trim($nick_name));
    }
}