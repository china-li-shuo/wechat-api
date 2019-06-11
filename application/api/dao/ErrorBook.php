<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 11:16
 */

namespace app\api\dao;


use think\Db;
use think\Model;

class ErrorBook extends Model
{

    public static function getSummaryByUser($uid, $page=1, $size=15,$type=1)
    {
        if($type == 1){
            //取出词汇类型为普通类型的id
            $arr = Group::where('type',1)->field('id')->select()->toArray();
            $tableName = 'english';
        }else{
            $arr = Group::where('type','<>',1)->field('id')->select()->toArray();
            $tableName = 'englishS';
        }


        //取出所有的普通词汇id集合数组
        $IDS = array_map(function ($item){
            return $item['id'];
        },$arr);

        $pagingData = self::with($tableName)
            ->where('user_id', '=', $uid)
            ->order('create_time')
            ->whereIn('group',$IDS)
            ->paginate($size, true, ['page' => $page]);

        return $pagingData ;
    }

    /**
     * 添加用户错题本
     * @param $uid
     * @param $data
     * @return int|string
     */
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
                'user_opt'    => implode(',',$data['useropt']),
                'create_time' => time()
            ];

            return Db::table('yx_error_book')->insert($arr);
        }
        $arr = ['user_opt' =>  implode(',',$data['useropt']), 'create_time' => time()];
        return Db::table('yx_error_book')
            ->where('user_id', $uid)
            ->where('word_id', $data['word_id'])
            ->update($arr);
    }

    /**
     * 删除用户错题本
     * @param $uid
     * @param $data
     * @return int|string
     */
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

    public function english()
    {
        return $this->belongsTo('EnglishWord', 'word_id', 'id');
    }

    public function englishS()
    {
        return $this->belongsTo('EnglishWordS', 'word_id', 'id');
    }
}