<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 11:16
 */

namespace app\api\model;


use think\Db;

class ErrorBook
{
    public static function addErrorBook($uid, $data)
    {
        $errorData = Db::table('yx_error_book')
            ->where('user_id', $uid)
            ->where('word_id', $data['word_id'])
            ->where('group', $data['group'])
            ->where('stage', $data['stage'])
            ->find();

        if (empty($errorData)) {

            $arr = [
                'user_id'     => $uid,
                'group'       => $data['group'],
                'stage'       => $data['stage'],
                'word_id'     => $data['word_id'],
                'user_opt'    => $data['useropt'],
                'create_time' => time()
            ];

            return Db::table('yx_error_book')->insert($arr);
        }
        $arr = ['user_opt' => $data['useropt'], 'create_time' => time()];
        return Db::table('yx_error_book')
            ->where('user_id', $uid)
            ->where('word_id', $data['word_id'])
            ->update($arr);
    }

    public static function deleteErrorBook($uid, $data)
    {

        $errorData = Db::table('yx_error_book')
            ->where('user_id', $uid)
            ->where('word_id', $data['word_id'])
            ->where('group', $data['group'])
            ->where('stage', $data['stage'])
            ->find();

        if (!empty($errorData)) {

            return Db::table('yx_error_book')->delete($errorData['id']);

        }

        return false;
    }

    /**
     * 用户错题本阶段和分组信息
     * @param $uid
     */
    public static function errorInfo($uid)
    {
        $data = Db::table('yx_error_book')
            ->where('user_id', $uid)
            ->group('stage')
            ->field('stage')
            ->select();
        foreach ($data as $key => $val) {

            $stage = Db::table(YX_QUESTION . 'stage')
                ->where('id', $val['stage'])
                ->field('stage_name')
                ->find();

            $data[$key]['stage_name'] = &$stage['stage_name'];
        }

        //获取阶段下所有组
        foreach ($data as $k => $v) {
            $group = Db::table('yx_error_book')
                ->where('user_id', $uid)
                ->where('stage', $v['stage'])
                ->group('group')
                ->field('group,stage')
                ->select();

            $data[$k]['group'] = $group;
            foreach ($group as $i => $j) {
                $group_name  = Db::table(YX_QUESTION . 'group')
                    ->where('id', $j['group'])
                    ->field('group_name')
                    ->find();

                $data[$k]['group'][$i]['group_name'] = $group_name['group_name'];
            }
        }

        return $data;
    }
}