<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/12
 * Time: 9:56
 */

namespace app\api\dao;


use think\Db;

class Collection
{
    /**
     * 添加
     * @param $uid
     * @param $data
     * @return bool|string
     */
    public static function addCollection($uid, $data)
    {
        $res = Db::table('yx_collection')
            ->where('user_id', $uid)
            ->where('stage', $data['stage'])
            ->where('group', $data['group'])
            ->where('word_id', $data['word_id'])
            ->find();

        if (empty($res)) {
            $data['user_id']     = $uid;
            $data['create_time'] = time();
            unset($data['is_collection']);

            Db::table('yx_collection')->insert($data);
            return Db::table('yx_collect')->getLastInsID();
        }

        return false;
    }

    /**
     * 删除
     * @param $uid
     * @param $data
     * @return bool|int
     */
    public static function deleteCollection($uid, $data)
    {
        $res = Db::table('yx_collection')
            ->where('user_id', $uid)
            ->where('stage', $data['stage'])
            ->where('group', $data['group'])
            ->where('word_id', $data['word_id'])
            ->find();
        if (!empty($res)) {
            return Db::table('yx_collection')->delete($res['id']);
        }
        return false;
    }

    public static function collectionInfo($uid)
    {
        $data = Db::table('yx_collection')
            ->where('user_id', $uid)
            ->group('stage')
            ->field('stage')
            ->select();
        foreach ($data as $key => $val) {

            $stage  = Db::table(YX_QUESTION . 'stage')
                ->where('id', $val['stage'])
                ->field('stage_name')
                ->find();
            $data[$key]['stage_name'] = &$stage['stage_name'];
        }
        //print_r($data);
        //获取阶段下所有组
        foreach ($data as $k => $v) {
            $group  = Db::table('yx_collection')
                ->where('user_id', $uid)
                ->where('stage', $v['stage'])
                ->group('group')
                ->field('group,stage')
                ->select();
            $data[$k]['group'] = $group;
            foreach ($group as $i => $j) {
                $group_name = Db::table(YX_QUESTION . 'group')
                    ->where('id', $j['group'])
                    ->field('group_name')
                    ->find();
                $data[$k]['group'][$i]['group_name'] = $group_name['group_name'];
            }
        }

        return $data;
    }

    /**
     * 判断当前用户是否收藏过该单词
     * @param $wordDetail
     */
    public static function isCollection($uid, $wordDetail)
    {
        foreach ($wordDetail as $key => $val) {
            $collection = Db::table('yx_collection')
                ->where('user_id', $uid)
                ->where('stage', $val['stage'])
                ->where('group', $val['group'])
                ->where('word_id', $val['wid'])
                ->find();
            if (!empty($collection)) {
                $wordDetail[$key]['is_collection'] = 1;
            }
        }

        return $wordDetail;
    }
}