<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/10/8
 * Time: 16:57
 */


namespace app\api_dream\controller\v1;


class Book
{
    public function hotList()
    {
        $data = curl_get('http://bl.7yue.pro/v1/book/hot_list',['appkey:4x2WjKUkopwFodLP']);
        print_r($data);die;
    }
}